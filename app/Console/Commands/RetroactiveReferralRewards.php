<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserNode;
use App\Models\UserAVIPProduct;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class RetroactiveReferralRewards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parrainage:retro-rewards {--dry-run : Exécuter la commande sans modifier la base de données}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scanne et attribue rétroactivement les récompenses de parrainage manquantes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? 'Mode DRY-RUN activé (aucune modification ne sera enregistrée).' : 'Début de l\'attribution des récompenses rétroactives...');

        $usersWithSponsors = User::whereNotNull('referrer_id')->get();
        $this->info("Nombre d'utilisateurs parrainés trouvés : " . $usersWithSponsors->count());

        $rewardsGivenCount = 0;
        $totalRewardAmount = 0;

        foreach ($usersWithSponsors as $user) {
            $sponsor = User::find($user->referrer_id);
            if (!$sponsor) {
                continue;
            }

            // 1. Traitement des locations de serveurs (UserNode)
            $userNodes = UserNode::where('user_id', $user->id)->with('node')->get();
            foreach ($userNodes as $userNode) {
                $node = $userNode->node;
                if (!$node) {
                    continue;
                }

                $referralReward = (float)($node->referral_reward ?? 0);
                if ($referralReward <= 0) {
                    continue;
                }

                // Générer une référence unique déterministe pour ce nœud loué par ce filleul
                $reference = 'COM-RET-N-' . $user->id . '-' . $userNode->id;

                // Vérifier si une commission existe déjà (soit via la référence rétroactive, soit une commission équivalente reçue à la même date à ± 2 heures de l'activation)
                $alreadyRewarded = Transaction::where('user_id', $sponsor->id)
                    ->where(function($q) use ($reference, $referralReward, $userNode) {
                        $q->where('reference', $reference)
                          ->orWhere(function($sq) use ($referralReward, $userNode) {
                              $sq->whereIn('type', ['commission', 'earnings'])
                                 ->where('reference', 'like', 'COM-%')
                                 ->where('amount', $referralReward)
                                 ->whereBetween('created_at', [
                                     $userNode->created_at->copy()->subHours(2),
                                     $userNode->created_at->copy()->addHours(2)
                                 ]);
                          });
                    })
                    ->exists();

                if (!$alreadyRewarded) {
                    $this->info("Récompense manquante détectée : Filleul #{$user->id} a loué le nœud #{$node->id} ({$node->name}). Parrain #{$sponsor->id} doit recevoir {$referralReward} XAF.");
                    
                    if (!$dryRun) {
                        DB::transaction(function() use ($sponsor, $referralReward, $reference, $userNode) {
                            $lockedSponsor = User::where('id', $sponsor->id)->lockForUpdate()->firstOrFail();
                            $lockedSponsor->balance += $referralReward;
                            $lockedSponsor->save();

                            Transaction::create([
                                'user_id' => $lockedSponsor->id,
                                'amount' => $referralReward,
                                'type' => 'commission',
                                'status' => 'completed',
                                'reference' => $reference,
                                'created_at' => $userNode->created_at,
                                'updated_at' => $userNode->created_at,
                            ]);

                            $lockedSponsor->recalculateVipAndAvipStatus();
                        });
                    }

                    $rewardsGivenCount++;
                    $totalRewardAmount += $referralReward;
                }
            }

            // 2. Traitement des achats AVIP (UserAVIPProduct)
            $userAvipProducts = UserAVIPProduct::where('user_id', $user->id)->with('avipProduct')->get();
            foreach ($userAvipProducts as $userAvip) {
                $product = $userAvip->avipProduct;
                if (!$product) {
                    continue;
                }

                $referralReward = (float)($product->referral_reward ?? 0);
                if ($referralReward <= 0) {
                    continue;
                }

                // Générer une référence unique déterministe
                $reference = 'COM-RET-A-' . $user->id . '-' . $userAvip->id;

                // Vérifier si déjà récompensé
                $alreadyRewarded = Transaction::where('user_id', $sponsor->id)
                    ->where(function($q) use ($reference, $referralReward, $userAvip) {
                        $q->where('reference', $reference)
                          ->orWhere(function($sq) use ($referralReward, $userAvip) {
                              $sq->whereIn('type', ['commission', 'earnings'])
                                 ->where('reference', 'like', 'COM-%')
                                 ->where('amount', $referralReward)
                                 ->whereBetween('created_at', [
                                     $userAvip->created_at->copy()->subHours(2),
                                     $userAvip->created_at->copy()->addHours(2)
                                 ]);
                          });
                    })
                    ->exists();

                if (!$alreadyRewarded) {
                    $this->info("Récompense manquante détectée : Filleul #{$user->id} a acheté le produit AVIP #{$product->id} (Niveau {$product->avip_level}). Parrain #{$sponsor->id} doit recevoir {$referralReward} XAF.");

                    if (!$dryRun) {
                        DB::transaction(function() use ($sponsor, $referralReward, $reference, $userAvip) {
                            $lockedSponsor = User::where('id', $sponsor->id)->lockForUpdate()->firstOrFail();
                            $lockedSponsor->balance += $referralReward;
                            $lockedSponsor->save();

                            Transaction::create([
                                'user_id' => $lockedSponsor->id,
                                'amount' => $referralReward,
                                'type' => 'commission',
                                'status' => 'completed',
                                'reference' => $reference,
                                'created_at' => $userAvip->created_at,
                                'updated_at' => $userAvip->created_at,
                            ]);

                            $lockedSponsor->recalculateVipAndAvipStatus();
                        });
                    }

                    $rewardsGivenCount++;
                    $totalRewardAmount += $referralReward;
                }
            }
        }

        $this->info("--- Fin du traitement ---");
        $this->info("Total récompenses attribuées : {$rewardsGivenCount}");
        $this->info("Montant total distribué : {$totalRewardAmount} XAF");
    }
}
