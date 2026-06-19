<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Migrates uploaded images that were stored in public/images/
 * to the new persistent storage path: storage/app/public/uploads/
 *
 * Run once on the production server after deploying this fix:
 *   php artisan images:migrate-to-storage
 */
class MigrateImagesToStorage extends Command
{
    protected $signature = 'images:migrate-to-storage {--dry-run : Preview changes without applying them}';
    protected $description = 'Migrate uploaded images from /images/ path to /storage/uploads/ for persistence across deployments';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN — aucune modification ne sera effectuée.');
        }

        $this->info('🚀 Migration des chemins d\'images vers /storage/uploads/...');
        $this->newLine();

        // Ensure the uploads directory exists
        if (!$dryRun) {
            Storage::disk('public')->makeDirectory('uploads');
        }

        $totalMigrated = 0;
        $totalSkipped  = 0;
        $totalMissing  = 0;

        // ── 1. Nodes (column: image) ───────────────────────────────────────────
        $totalMigrated += $this->migrateColumn('nodes', 'image', $dryRun, $totalSkipped, $totalMissing);

        // ── 2. AVIP Products (column: image) ──────────────────────────────────
        $totalMigrated += $this->migrateColumn('avip_products', 'image', $dryRun, $totalSkipped, $totalMissing);

        // ── 3. Announcements (column: image_url) ──────────────────────────────
        $totalMigrated += $this->migrateColumn('announcements', 'image_url', $dryRun, $totalSkipped, $totalMissing);

        // ── 4. Vault Plans (column: image) ────────────────────────────────────
        $totalMigrated += $this->migrateColumn('vault_plans', 'image', $dryRun, $totalSkipped, $totalMissing);

        $this->newLine();
        $this->info("✅ Migration terminée !");
        $this->table(
            ['Statut', 'Nombre'],
            [
                ['✅ Migrés', $totalMigrated],
                ['⏭  Déjà à jour / externes', $totalSkipped],
                ['❌ Fichier source manquant', $totalMissing],
            ]
        );

        if ($dryRun) {
            $this->warn('ℹ️  Mode dry-run — relancez sans --dry-run pour appliquer les changements.');
        }

        return Command::SUCCESS;
    }

    /**
     * Migrate a single table column from /images/ to /storage/uploads/.
     */
    private function migrateColumn(string $table, string $column, bool $dryRun, int &$skipped, int &$missing): int
    {
        $migrated = 0;

        $rows = DB::table($table)
            ->whereNotNull($column)
            ->where($column, 'like', '/images/%')
            ->get(['id', $column]);

        if ($rows->isEmpty()) {
            $this->line("  <fg=gray>$table.$column → aucun enregistrement à migrer</>");
            return 0;
        }

        $this->line("  <fg=cyan>$table.$column → {$rows->count()} enregistrement(s) trouvé(s)</>");

        foreach ($rows as $row) {
            $oldPath  = $row->$column;                              // e.g. /images/1234_photo.jpg
            $fileName = basename($oldPath);                        // e.g. 1234_photo.jpg
            $srcPath  = public_path('images' . DIRECTORY_SEPARATOR . $fileName);
            $newUrl   = '/storage/uploads/' . $fileName;

            // Check source file exists before copying
            if (!file_exists($srcPath)) {
                $this->warn("    ❌ Fichier source manquant : $srcPath (id={$row->id})");
                $missing++;
                continue;
            }

            if ($dryRun) {
                $this->line("    → [DRY-RUN] id={$row->id}: $oldPath  →  $newUrl");
            } else {
                // Copy file to the new location
                $fileContent = file_get_contents($srcPath);
                Storage::disk('public')->put('uploads/' . $fileName, $fileContent);

                // Update the database record
                DB::table($table)->where('id', $row->id)->update([$column => $newUrl]);

                $this->line("    ✅ id={$row->id}: $oldPath  →  $newUrl");
            }

            $migrated++;
        }

        return $migrated;
    }
}
