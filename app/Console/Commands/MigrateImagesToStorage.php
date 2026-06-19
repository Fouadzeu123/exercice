<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrates uploaded images stored in public/images/ or /storage/uploads/
 * to the new persistent path: public/uploads/
 *
 * Run once on the production server after deploying this fix:
 *   php artisan images:migrate-to-storage
 */
class MigrateImagesToStorage extends Command
{
    protected $signature = 'images:migrate-to-storage {--dry-run : Preview changes without applying them}';
    protected $description = 'Migrate uploaded images from /images/ or /storage/uploads/ to /uploads/ for persistence across deployments';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — aucune modification ne sera effectuee.');
        }

        $this->info("Migration des chemins d'images vers /uploads/...");
        $this->newLine();

        // Ensure the uploads directory exists
        if (!$dryRun) {
            $uploadDir = public_path('uploads');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
        }

        $totalMigrated = 0;
        $totalSkipped  = 0;
        $totalMissing  = 0;

        // Nodes (column: image)
        $totalMigrated += $this->migrateColumn('nodes', 'image', $dryRun, $totalSkipped, $totalMissing);

        // AVIP Products (column: image)
        $totalMigrated += $this->migrateColumn('avip_products', 'image', $dryRun, $totalSkipped, $totalMissing);

        // Announcements (column: image_url)
        $totalMigrated += $this->migrateColumn('announcements', 'image_url', $dryRun, $totalSkipped, $totalMissing);

        // Vault Plans (column: image)
        $totalMigrated += $this->migrateColumn('vault_plans', 'image', $dryRun, $totalSkipped, $totalMissing);

        $this->newLine();
        $this->info("Migration terminee !");
        $this->table(
            ['Statut', 'Nombre'],
            [
                ['OK Migres', $totalMigrated],
                ['Deja a jour / externes', $totalSkipped],
                ['Fichier source manquant', $totalMissing],
            ]
        );

        if ($dryRun) {
            $this->warn("Mode dry-run — relancez sans --dry-run pour appliquer les changements.");
        }

        return Command::SUCCESS;
    }

    /**
     * Migrate a single table column from /images/ or /storage/uploads/ to /uploads/.
     */
    private function migrateColumn(string $table, string $column, bool $dryRun, int &$skipped, int &$missing): int
    {
        $migrated = 0;

        $rows = DB::table($table)
            ->whereNotNull($column)
            ->where(function ($q) use ($column) {
                $q->where($column, 'like', '/images/%')
                  ->orWhere($column, 'like', '/storage/uploads/%');
            })
            ->get(['id', $column]);

        if ($rows->isEmpty()) {
            $this->line("  $table.$column -> aucun enregistrement a migrer");
            return 0;
        }

        $this->line("  $table.$column -> {$rows->count()} enregistrement(s) trouves");

        foreach ($rows as $row) {
            $oldPath  = $row->$column;
            $fileName = basename($oldPath);
            $newUrl   = '/uploads/' . $fileName;

            // Determine source file based on old path prefix
            if (str_starts_with($oldPath, '/images/')) {
                $srcPath = public_path('images' . DIRECTORY_SEPARATOR . $fileName);
            } else {
                $srcPath = storage_path('app/public/uploads/' . $fileName);
            }

            if (!file_exists($srcPath)) {
                $this->warn("    MANQUANT: $srcPath (id={$row->id})");
                $missing++;
                continue;
            }

            if ($dryRun) {
                $this->line("    [DRY-RUN] id={$row->id}: $oldPath -> $newUrl");
            } else {
                $destPath = public_path('uploads' . DIRECTORY_SEPARATOR . $fileName);
                if (!file_exists($destPath)) {
                    copy($srcPath, $destPath);
                }
                DB::table($table)->where('id', $row->id)->update([$column => $newUrl]);
                $this->line("    OK id={$row->id}: $oldPath -> $newUrl");
            }

            $migrated++;
        }

        return $migrated;
    }
}
