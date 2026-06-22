<script setup lang="ts">
import { ref } from 'vue';
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home, login } from '@/routes';
import { store } from '@/routes/register';
import { Phone, Lock, UserPlus, CheckCircle, X } from 'lucide-vue-next';

// Parse query params immediately to prefill referral_code
const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
const initialReferral = urlParams ? (urlParams.get('ref') || '') : '';

const acceptTerms = ref(false);
const showTermsModal = ref(false);
</script>

<template>
    <Head title="Inscription | ARM Holding - Créez votre compte d'investissement" />
    <div class="relative min-h-screen flex flex-col bg-black overflow-hidden">
        
        <!-- Background: Vertical Purple Shimmering Lines -->
        <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
            <div class="absolute inset-0 bg-grid opacity-15"></div>
            <div class="vertical-line" style="left: 8%; animation-delay: 0s; animation-duration: 9s;"></div>
            <div class="vertical-line" style="left: 24%; animation-delay: 2.5s; animation-duration: 7s;"></div>
            <div class="vertical-line" style="left: 42%; animation-delay: 4.8s; animation-duration: 10s;"></div>
            <div class="vertical-line" style="left: 58%; animation-delay: 1.2s; animation-duration: 8s;"></div>
            <div class="vertical-line" style="left: 76%; animation-delay: 3.5s; animation-duration: 11s;"></div>
            <div class="vertical-line" style="left: 90%; animation-delay: 5.2s; animation-duration: 8.5s;"></div>
            <div class="absolute top-[20%] left-[20%] w-96 h-96 rounded-full bg-purple-500/5 blur-[120px]"></div>
            <div class="absolute bottom-[20%] right-[20%] w-[350px] h-[350px] rounded-full bg-purple-500/5 blur-[150px]"></div>
        </div>

        <!-- Content -->
        <div class="relative z-10 flex flex-col flex-grow w-full max-w-xl mx-auto px-5 py-6">

            <!-- Server Image Section with Bubbles -->
            <div class="relative w-full h-44 rounded-2xl overflow-hidden bg-slate-950 border border-purple-500/15 shadow-lg">
                <img 
                    src="/images/cyber_server_hero.png" 
                    alt="Infrastructure Serveur ARM" 
                    class="w-full h-full object-cover opacity-90 object-center"
                />
                <!-- Bubbles -->
                <div class="bubbles-container">
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                </div>
                <!-- Scanner -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-purple-400 to-transparent shadow-[0_0_12px_rgba(168,85,247,0.8)] animate-scanner pointer-events-none"></div>
            </div>

            <!-- Brand / Logo -->
            <div class="flex flex-col items-center gap-1.5 mt-6 mb-2">
                <Link :href="home()" class="group">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-purple-500/20 bg-black/40 transition-transform duration-500 group-hover:scale-105 overflow-hidden">
                        <img src="/images/logo.jpg" class="h-full w-full object-cover" alt="ARM Logo" />
                    </div>
                </Link>
                <h1 class="text-base font-bold tracking-wider text-white uppercase">Inscription</h1>
                <p class="text-[10px] text-purple-400/80 font-medium tracking-wide uppercase">Créez votre compte d'investissement</p>
            </div>

            <!-- Verified Sponsor Active Badge -->
            <div v-if="initialReferral" class="mt-4 flex items-center gap-2 p-3 rounded-lg border border-purple-500/30 bg-purple-950/20 text-purple-400 text-xs font-semibold tracking-wide">
                <CheckCircle class="h-4 w-4 shrink-0 text-purple-400" :stroke-width="2.5" />
                <span>Lien de parrainage activé : <span class="font-bold text-white underline">{{ initialReferral }}</span></span>
            </div>

            <!-- Registration Form -->
            <Form
                v-bind="store.form({ phone: '237', password: '', password_confirmation: '', referral_code: initialReferral })"
                :reset-on-success="['password', 'password_confirmation']"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-5 mt-6 flex-grow"
            >
                <!-- Phone -->
                <div class="grid gap-2">
                    <Label class="text-purple-400 text-xs font-bold flex items-center gap-1.5" for="phone">
                        <Phone class="h-3.5 w-3.5" :stroke-width="2.5" />
                        Numéro de téléphone
                    </Label>
                    <Input
                        id="phone"
                        type="tel"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="tel"
                        name="phone"
                        placeholder="Ex: 690000000"
                        class="bg-black/50 border-purple-500/20 text-white focus-visible:ring-purple-400 focus-visible:border-purple-400 transition-all duration-300 neon-border font-mono text-sm pl-4 h-12 rounded-xl"
                    />
                    <InputError :message="errors.phone" class="text-[10px] text-rose-400" />
                </div>

                <!-- Password -->
                <div class="grid gap-2">
                    <Label class="text-purple-400 text-xs font-bold flex items-center gap-1.5" for="password">
                        <Lock class="h-3.5 w-3.5" :stroke-width="2.5" />
                        Mot de passe
                    </Label>
                    <PasswordInput
                        id="password"
                        required
                        :tabindex="2"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Créez votre mot de passe"
                        class="bg-black/50 border-purple-500/20 text-white focus-visible:ring-purple-400 focus-visible:border-purple-400 transition-all duration-300 neon-border text-sm pl-4 h-12 rounded-xl"
                    />
                    <InputError :message="errors.password" class="text-[10px] text-rose-400" />
                </div>

                <!-- Password Confirmation -->
                <div class="grid gap-2">
                    <Label class="text-purple-400 text-xs font-bold flex items-center gap-1.5" for="password_confirmation">
                        <Lock class="h-3.5 w-3.5" :stroke-width="2.5" />
                        Confirmer le mot de passe
                    </Label>
                    <PasswordInput
                        id="password_confirmation"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirmez votre mot de passe"
                        class="bg-black/50 border-purple-500/20 text-white focus-visible:ring-purple-400 focus-visible:border-purple-400 transition-all duration-300 neon-border text-sm pl-4 h-12 rounded-xl"
                    />
                    <InputError :message="errors.password_confirmation" class="text-[10px] text-rose-400" />
                </div>

                <!-- Referral Code -->
                <div class="grid gap-2">
                    <Label class="text-purple-400 text-xs font-bold" for="referral_code">
                        Code de parrainage (Optionnel)
                    </Label>
                    <Input
                        id="referral_code"
                        type="text"
                        :tabindex="4"
                        name="referral_code"
                        placeholder="Ex: AB12CD34"
                        :readonly="!!initialReferral"
                        :class="[
                            'bg-black/50 border-purple-500/20 text-white focus-visible:ring-purple-400 focus-visible:border-purple-400 transition-all duration-300 neon-border font-mono text-sm pl-4 h-12 rounded-xl',
                            initialReferral ? 'opacity-50 cursor-not-allowed select-none bg-purple-950/10' : ''
                        ]"
                    />
                    <InputError :message="errors.referral_code" class="text-[10px] text-rose-400" />
                </div>

                <!-- Checkbox Conditions d'Utilisation -->
                <div class="flex items-start gap-2.5 select-none mt-1">
                    <input 
                        id="accept_terms" 
                        type="checkbox" 
                        v-model="acceptTerms" 
                        required
                        class="mt-0.5 rounded border-purple-500/30 bg-black/50 text-purple-600 focus:ring-purple-500 focus:ring-offset-black size-4 cursor-pointer"
                    />
                    <label for="accept_terms" class="text-[11px] text-slate-400 cursor-pointer font-medium leading-tight">
                        J'accepte sans réserve les 
                        <button 
                            type="button" 
                            @click="showTermsModal = true" 
                            class="text-purple-400 font-extrabold hover:text-white underline underline-offset-2 transition-colors inline-block"
                        >
                            conditions d'utilisation
                        </button>
                        du réseau d'infrastructure ARM.
                    </label>
                </div>

                <!-- Submit -->
                <Button
                    type="submit"
                    class="mt-2 w-full bg-purple-500 text-black hover:bg-purple-400 transition-all duration-300 shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:shadow-[0_0_25px_rgba(168,85,247,0.5)] font-extrabold tracking-wider text-sm uppercase h-12 rounded-xl"
                    tabindex="5"
                    :disabled="processing || !acceptTerms"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" class="text-black" />
                    <span v-else class="flex items-center gap-2">
                        <UserPlus class="h-4 w-4" :stroke-width="2.5" />
                        CRÉER UN COMPTE
                    </span>
                </Button>

                <!-- Link to Login -->
                <div class="text-center text-sm text-slate-400 mt-2 pt-4 border-t border-white/5 font-semibold">
                    Déjà inscrit ?
                    <TextLink
                        :href="login()"
                        class="text-purple-400 underline underline-offset-4 font-extrabold ml-1.5 hover:text-white transition-colors"
                        :tabindex="6"
                    >Se connecter</TextLink>
                </div>
            </Form>
        </div>

        <!-- Modale des Conditions d'Utilisation -->
        <div v-if="showTermsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/95 backdrop-blur-sm animate-fadeIn">
            <div class="w-full max-w-xl bg-[#0e071d] border border-purple-500/25 rounded-2xl overflow-hidden shadow-2xl animate-fadeInUp glow-border">
                <div class="p-6 flex flex-col h-[75vh]">
                    <!-- Header -->
                    <div class="flex justify-between items-center pb-4 border-b border-white/5 shrink-0">
                        <div>
                            <h3 class="text-sm font-black text-white uppercase tracking-wider">Conditions d'Utilisation</h3>
                            <p class="text-[9px] text-purple-400 uppercase tracking-widest font-mono mt-0.5">Protocole Réseau ARM v4.8</p>
                        </div>
                        <button @click="showTermsModal = false" class="hover:rotate-90 transition-transform p-1.5 rounded-lg hover:bg-white/5">
                            <X class="w-5 h-5 text-gray-400" />
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 overflow-y-auto my-4 pr-1 space-y-4 no-scrollbar text-[11px] leading-relaxed text-slate-300 font-sans">
                        <p class="font-bold text-purple-400 uppercase tracking-wide text-[10px]">VEUILLEZ LIRE ATTENTIVEMENT CES CLAUSES AVANT TOUTE INSCRIPTION :</p>
                        
                        <div class="space-y-3.5 divide-y divide-white/5">
                            <div class="pt-2 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">01.</span>
                                <div><span class="font-bold text-white">Acceptation :</span> L'inscription sur la console ARM Holding implique l'acceptation sans réserve de l'ensemble de ces termes techniques et d'exploitation.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">02.</span>
                                <div><span class="font-bold text-white">Nature du service :</span> ARM Holding fournit un service de location de puissance de hachage GPU semi-conductrice brute et d'hébergement. Aucune prestation de conseil financier n'est fournie.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">03.</span>
                                <div><span class="font-bold text-white">Caution technique :</span> Les montants versés pour la location constituent des cautions de réservation matérielle non remboursables par anticipation en dehors des cas de mise à niveau express prévus par la plateforme.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">04.</span>
                                <div><span class="font-bold text-white">Non-responsabilité pour pertes :</span> ARM Holding et ses sous-traitants déclinent toute responsabilité directe ou indirecte en cas de pertes financières, pannes de calcul, ou de défaillances temporaires du réseau blockchain.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">05.</span>
                                <div><span class="font-bold text-white">Risques technologiques :</span> Le rendement des calculs dépend des fluctuations énergétiques mondiales, des temps de réponse des semi-conducteurs et des frais réseau variables.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">06.</span>
                                <div><span class="font-bold text-white">Modifications des conditions :</span> L'administration conserve le droit absolu de modifier, réviser, adapter ou supprimer les tarifs de location, les coefficients de calcul et les rendements à tout moment sans notification préalable.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">07.</span>
                                <div><span class="font-bold text-white">Suspension discrétionnaire :</span> L'accès à la plateforme et les opérations en cours peuvent être suspendues unilatéralement et définitivement en cas d'activités suspectes détectées par nos pare-feux.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">08.</span>
                                <div><span class="font-bold text-white">Rupture de contrat :</span> Toute infraction à l'intégrité de la base de données entraîne la fermeture immédiate du compte utilisateur et la confiscation irréversible des fonds restants.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">09.</span>
                                <div><span class="font-bold text-white">Frais de protocole :</span> Les retraits et dépôts via MTN, Orange ou USDT TRC-20 peuvent subir des retenues de protocole variables pour la maintenance réseau.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">10.</span>
                                <div><span class="font-bold text-white">Propriété intellectuelle :</span> L'utilisateur ne dispose d'aucun droit de propriété sur le matériel physique NVIDIA ou les microprocesseurs hébergés dans nos datacenters.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">11.</span>
                                <div><span class="font-bold text-white">Maintenance système :</span> Les périodes d'arrêt pour maintenance préventive ne donnent droit à aucune compensation financière ou prolongation de contrat de location.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">12.</span>
                                <div><span class="font-bold text-white">Plafonds de paiement :</span> Les limites de transferts quotidiens sont fixées par nos passerelles de paiement partenaires et peuvent être réduites unilatéralement selon les réserves de liquidité.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">13.</span>
                                <div><span class="font-bold text-white">Force majeure :</span> Les cas d'incendies, catastrophes climatiques ou régulations juridiques nationales exonèrent ARM Holding de toute obligation d'exécution.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">14.</span>
                                <div><span class="font-bold text-white">Exactitude des données :</span> L'utilisateur est seul responsable de la conformité de son numéro de paiement mobile et de sa clé USDT. Les erreurs de transfert ne sont pas récupérables.</div>
                            </div>
                            <div class="pt-3 flex gap-2">
                                <span class="text-purple-400 font-black font-mono shrink-0">15.</span>
                                <div><span class="font-bold text-white">Renoncement aux recours :</span> En validant l'inscription, l'utilisateur s'engage à garantir ARM Holding contre toute réclamation ou action collective liée à la variation des performances matérielles.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer actions -->
                    <button 
                        type="button"
                        @click="acceptTerms = true; showTermsModal = false" 
                        class="w-full py-3.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs uppercase tracking-wider shadow-[0_0_15px_rgba(168,85,247,0.4)] transition-all shrink-0"
                    >
                        J'accepte les Conditions
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.vertical-line {
    position: absolute;
    top: -170px;
    width: 1px;
    height: 170px;
    background: linear-gradient(to bottom, transparent, rgba(168, 85, 247, 0.4) 70%, transparent);
    animation: flowDown 9s linear infinite;
    opacity: 0;
}
@keyframes flowDown {
    0% { transform: translateY(0); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(115vh); opacity: 0; }
}
@keyframes scanner {
    0% { top: 0%; opacity: 0.1; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { top: 100%; opacity: 0.1; }
}
.animate-scanner {
    animation: scanner 5s linear infinite;
}
.bubbles-container {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 10;
}
.bubble {
    position: absolute;
    bottom: -20px;
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 50%;
    animation: rise 6s infinite ease-in;
}
.bubble:nth-child(1) { left: 8%; width: 10px; height: 10px; animation-duration: 5.5s; animation-delay: 0s; }
.bubble:nth-child(2) { left: 22%; width: 6px; height: 6px; animation-duration: 7s; animation-delay: 1.5s; }
.bubble:nth-child(3) { left: 38%; width: 14px; height: 14px; animation-duration: 6.2s; animation-delay: 0.4s; }
.bubble:nth-child(4) { left: 52%; width: 8px; height: 8px; animation-duration: 8s; animation-delay: 2s; }
.bubble:nth-child(5) { left: 68%; width: 12px; height: 12px; animation-duration: 5.8s; animation-delay: 0.9s; }
.bubble:nth-child(6) { left: 82%; width: 6px; height: 6px; animation-duration: 6.8s; animation-delay: 3s; }
.bubble:nth-child(7) { left: 28%; width: 9px; height: 9px; animation-duration: 7.6s; animation-delay: 2.5s; }
.bubble:nth-child(8) { left: 74%; width: 8px; height: 8px; animation-duration: 7.1s; animation-delay: 0.7s; }
@keyframes rise {
    0% { transform: translateY(0) scale(1); opacity: 0; }
    10% { opacity: 0.5; }
    90% { opacity: 0.5; }
    100% { transform: translateY(-210px) scale(0.6); opacity: 0; }
}
</style>
