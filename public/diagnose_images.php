<?php
define('LARAVEL_START', microtime(true));

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

// Simple Security Token Check to protect production database info
$secretToken = 'arm_diag_2026';
$providedToken = $_GET['token'] ?? '';

// Check if logged in as admin OR correct token is provided
$isAdmin = false;
try {
    $isAdmin = Auth::check() && Auth::user()->role === 'admin';
} catch (\Exception $e) {
    // Session/Auth table might not be ready
}

if (!$isAdmin && $providedToken !== $secretToken) {
    header('HTTP/1.1 403 Forbidden');
    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Accès Refusé</title>
        <style>
            body { font-family: sans-serif; background: #0f0f15; color: #fff; text-align: center; padding: 50px; }
            .card { background: #1a1a24; padding: 30px; border-radius: 10px; display: inline-block; border: 1px solid #ff4a4a; }
            input { padding: 10px; border-radius: 5px; border: 1px solid #333; background: #111; color: white; width: 250px; margin-top: 10px; }
            button { padding: 10px 20px; background: #7c3aed; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>Diagnostic des Images - ARM Holding</h2>
            <p style="color: #ccc;">Veuillez vous connecter en tant qu\'administrateur ou fournir le jeton de sécurité.</p>
            <form method="GET">
                <input type="password" name="token" placeholder="Jeton de diagnostic" required><br>
                <button type="submit">Valider</button>
            </form>
        </div>
    </body>
    </html>';
    exit;
}

// Gather information
$diagnostics = [];
$diagnostics['server'] = [
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'Non défini',
    'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? 'Non défini',
    'CURRENT_DIR' => __DIR__,
    'PHP_VERSION' => PHP_VERSION,
];

$diagnostics['laravel'] = [
    'APP_URL' => config('app.url'),
    'APP_ENV' => config('app.env'),
    'BASE_PATH' => base_path(),
    'PUBLIC_PATH' => public_path(),
    'STORAGE_PATH' => storage_path(),
];

// Check directories
$dirs = [
    'public/uploads' => public_path('uploads'),
    'storage/app/public/uploads' => storage_path('app/public/uploads'),
    'public/images' => public_path('images'),
];

$dirStatus = [];
foreach ($dirs as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists ? is_writable($path) : false;
    $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
    
    // Count files
    $fileCount = 0;
    $files = [];
    if ($exists) {
        $scan = scandir($path);
        foreach ($scan as $file) {
            if ($file !== '.' && $file !== '..') {
                $fileCount++;
                if (count($files) < 10) {
                    $files[] = $file;
                }
            }
        }
    }
    
    $dirStatus[$name] = [
        'path' => $path,
        'exists' => $exists,
        'writable' => $writable,
        'perms' => $perms,
        'file_count' => $fileCount,
        'sample_files' => $files,
    ];
}

// Database contents
$dbData = [];
$tablesToCheck = [
    'nodes' => ['name_col' => 'name', 'img_col' => 'image'],
    'avip_products' => ['name_col' => 'name', 'img_col' => 'image'],
    'announcements' => ['name_col' => 'title', 'img_col' => 'image_url'],
    'vault_plans' => ['name_col' => 'name', 'img_col' => 'image'],
];

foreach ($tablesToCheck as $table => $cols) {
    $hasTable = false;
    try {
        $hasTable = Schema::hasTable($table);
    } catch (\Exception $e) {
        $dbData[$table] = ['error' => $e->getMessage()];
        continue;
    }

    if ($hasTable) {
        try {
            $rows = DB::table($table)->orderBy('id', 'desc')->limit(5)->get();
            $items = [];
            foreach ($rows as $row) {
                $items[] = [
                    'id' => $row->id,
                    'name' => $row->{$cols['name_col']} ?? 'Sans nom',
                    'image' => $row->{$cols['img_col']} ?? null,
                ];
            }
            $dbData[$table] = [
                'exists' => true,
                'count' => DB::table($table)->count(),
                'items' => $items,
            ];
        } catch (\Exception $e) {
            $dbData[$table] = ['error' => $e->getMessage()];
        }
    } else {
        $dbData[$table] = ['exists' => false];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic de l'Affichage des Images — ARM</title>
    <style>
        :root {
            --bg-color: #0b0b10;
            --card-bg: rgba(22, 22, 33, 0.7);
            --border-color: rgba(124, 58, 237, 0.2);
            --text-color: #e2e8f0;
            --text-muted: #94a3b8;
            --accent: #7c3aed;
            --accent-glow: rgba(124, 58, 237, 0.4);
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
        }

        body {
            background-color: var(--bg-color);
            background-image: radial-gradient(circle at 10% 20%, rgba(124, 58, 237, 0.08) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.08) 0%, transparent 40%);
            color: var(--text-color);
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            margin: 0;
            padding: 30px 15px;
            line-height: 1.5;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 40px;
        }

        h1 {
            font-size: 2.2rem;
            margin: 0;
            background: linear-gradient(135deg, #a78bfa, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 4px 20px rgba(124, 58, 237, 0.15);
        }

        p.subtitle {
            color: var(--text-muted);
            margin-top: 10px;
            font-size: 1.1rem;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .card:hover {
            border-color: rgba(124, 58, 237, 0.4);
            box-shadow: 0 10px 35px rgba(124, 58, 237, 0.1);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 20px;
            border-left: 4px solid var(--accent);
            padding-left: 10px;
            color: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.95rem;
        }

        th {
            color: var(--text-muted);
            font-weight: 500;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .badge-error { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .badge-warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }

        pre {
            background: rgba(0, 0, 0, 0.3);
            padding: 12px;
            border-radius: 6px;
            font-family: 'Consolas', monospace;
            font-size: 0.85rem;
            overflow-x: auto;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin: 5px 0;
            color: #c0caf5;
        }

        .code-inline {
            font-family: 'Consolas', monospace;
            background: rgba(255, 255, 255, 0.08);
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #fca5a5;
        }

        .recommendation {
            border: 1px dashed var(--accent);
            border-radius: 8px;
            background: rgba(124, 58, 237, 0.05);
            padding: 15px;
            margin-top: 15px;
        }

        .recommendation h4 {
            margin-top: 0;
            color: #a78bfa;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-ok { background: var(--success); box-shadow: 0 0 10px var(--success); }
        .status-fail { background: var(--error); box-shadow: 0 0 10px var(--error); }
        .status-warn { background: var(--warning); box-shadow: 0 0 10px var(--warning); }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Diagnostic de l'Affichage des Images</h1>
            <p class="subtitle">Analyseur de configuration, d'URL et de stockage en production</p>
        </header>

        <div class="grid">
            <!-- Server Config -->
            <div class="card">
                <h3 class="card-title">Configuration Serveur & PHP</h3>
                <table>
                    <tr>
                        <td>DOCUMENT_ROOT</td>
                        <td><pre><?php echo htmlspecialchars($diagnostics['server']['DOCUMENT_ROOT']); ?></pre></td>
                    </tr>
                    <tr>
                        <td>Dossier Script</td>
                        <td><pre><?php echo htmlspecialchars($diagnostics['server']['CURRENT_DIR']); ?></pre></td>
                    </tr>
                    <tr>
                        <td>Fichier Diagnostic</td>
                        <td><pre><?php echo htmlspecialchars($diagnostics['server']['SCRIPT_FILENAME']); ?></pre></td>
                    </tr>
                    <tr>
                        <td>Version PHP</td>
                        <td><span class="badge badge-success"><?php echo $diagnostics['server']['PHP_VERSION']; ?></span></td>
                    </tr>
                </table>
            </div>

            <!-- Laravel Config -->
            <div class="card">
                <h3 class="card-title">Configuration Laravel</h3>
                <table>
                    <tr>
                        <td>APP_URL (.env)</td>
                        <td><pre><?php echo htmlspecialchars($diagnostics['laravel']['APP_URL']); ?></pre></td>
                    </tr>
                    <tr>
                        <td>APP_ENV</td>
                        <td><span class="badge badge-warning"><?php echo htmlspecialchars($diagnostics['laravel']['APP_ENV']); ?></span></td>
                    </tr>
                    <tr>
                        <td>base_path()</td>
                        <td><pre><?php echo htmlspecialchars($diagnostics['laravel']['BASE_PATH']); ?></pre></td>
                    </tr>
                    <tr>
                        <td>public_path()</td>
                        <td><pre><?php echo htmlspecialchars($diagnostics['laravel']['PUBLIC_PATH']); ?></pre></td>
                    </tr>
                    <tr>
                        <td>storage_path()</td>
                        <td><pre><?php echo htmlspecialchars($diagnostics['laravel']['STORAGE_PATH']); ?></pre></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Directories Status -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 class="card-title">État des dossiers de stockage et d'images</h3>
            <table>
                <thead>
                    <tr>
                        <th>Dossier Virtuel</th>
                        <th>Chemin Physique Absolu</th>
                        <th>Existe ?</th>
                        <th>Écripture ?</th>
                        <th>Perm.</th>
                        <th>Fichiers</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dirStatus as $name => $status): ?>
                    <tr>
                        <td><strong>/<?php echo $name; ?></strong></td>
                        <td><pre style="margin: 0;"><?php echo htmlspecialchars($status['path']); ?></pre></td>
                        <td>
                            <?php if ($status['exists']): ?>
                                <span class="badge badge-success">Oui</span>
                            <?php else: ?>
                                <span class="badge badge-error">Non</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($status['writable']): ?>
                                <span class="badge badge-success">Oui</span>
                            <?php else: ?>
                                <span class="badge badge-error">Non</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="code-inline"><?php echo $status['perms']; ?></span></td>
                        <td>
                            <span class="badge badge-success"><?php echo $status['file_count']; ?> fichier(s)</span>
                        </td>
                    </tr>
                    <?php if ($status['file_count'] > 0): ?>
                    <tr>
                        <td colspan="6" style="padding-left: 20px; font-size: 0.85rem; color: var(--text-muted);">
                            Échantillon de fichiers: 
                            <?php foreach ($status['sample_files'] as $sf): ?>
                                <span class="code-inline" style="color: #a78bfa; margin-right: 5px;"><?php echo htmlspecialchars($sf); ?></span>
                            <?php endforeach; ?>
                            <?php if ($status['file_count'] > 10) echo '...'; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Database Images Paths -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 class="card-title">Données Images en Base de Données (Production)</h3>
            <div class="grid">
                <?php foreach ($dbData as $table => $data): ?>
                <div style="border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 15px; background: rgba(0,0,0,0.15);">
                    <h4 style="margin-top:0; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px; display:flex; justify-content:space-between;">
                        <span>Table: <span class="code-inline" style="color:#60a5fa;"><?php echo $table; ?></span></span>
                        <?php if (isset($data['error'])): ?>
                            <span class="badge badge-error">Erreur DB</span>
                        <?php elseif (isset($data['exists']) && !$data['exists']): ?>
                            <span class="badge badge-error">Inexistante</span>
                        <?php else: ?>
                            <span class="badge badge-success"><?php echo $data['count'] ?? 0; ?> lignes</span>
                        <?php endif; ?>
                    </h4>
                    
                    <?php if (isset($data['error'])): ?>
                        <pre style="color:var(--error);"><?php echo htmlspecialchars($data['error']); ?></pre>
                    <?php elseif (isset($data['exists']) && !$data['exists']): ?>
                        <p style="color:var(--text-muted); font-size:0.9rem;">La table n'existe pas en base.</p>
                    <?php elseif (empty($data['items'])): ?>
                        <p style="color:var(--text-muted); font-size:0.9rem;">Aucun enregistrement trouvé dans cette table.</p>
                    <?php else: ?>
                        <ul style="padding-left: 20px; margin: 0; font-size:0.85rem;">
                            <?php foreach ($data['items'] as $item): ?>
                                <li style="margin-bottom: 8px;">
                                    <strong>ID <?php echo $item['id']; ?></strong>: <?php echo htmlspecialchars($item['name']); ?><br>
                                    Path: <?php if ($item['image']): ?>
                                        <span class="code-inline" style="color:<?php echo (str_starts_with($item['image'], 'http') ? '#34d399' : '#fbbf24'); ?>; word-break: break-all;"><?php echo htmlspecialchars($item['image']); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-warning" style="padding: 1px 5px; font-size: 0.7rem;">NULL / VIDE</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recommendations and Diagnoses -->
        <div class="card">
            <h3 class="card-title">Analyse et Recommandations</h3>
            
            <div class="recommendation">
                <h4>
                    <span class="status-indicator <?php echo ($dirStatus['public/uploads']['exists'] ? 'status-ok' : 'status-fail'); ?>"></span>
                    1. Emplacement physique de /uploads
                </h4>
                <p>
                    Si le site s'exécute à partir de <span class="code-inline"><?php echo htmlspecialchars($diagnostics['server']['DOCUMENT_ROOT']); ?></span> et que 
                    <span class="code-inline">public_path()</span> pointe vers <span class="code-inline"><?php echo htmlspecialchars($diagnostics['laravel']['PUBLIC_PATH']); ?></span>, 
                    les fichiers uploadés sont stockés physiquement dans <span class="code-inline"><?php echo htmlspecialchars($dirStatus['public/uploads']['path']); ?></span>.
                </p>
                <?php if (!$dirStatus['public/uploads']['exists']): ?>
                    <p style="color:var(--error);"><strong>⚠️ Problème :</strong> Le dossier <span class="code-inline">public/uploads</span> n'existe pas. Vous devez le créer ou lancer une opération d'upload depuis l'administration.</p>
                <?php elseif (!$dirStatus['public/uploads']['writable']): ?>
                    <p style="color:var(--error);"><strong>⚠️ Problème :</strong> Le dossier <span class="code-inline">public/uploads</span> n'est pas inscriptible (Permissions actuelles : <?php echo $dirStatus['public/uploads']['perms']; ?>). Exécutez <span class="code-inline">chmod -R 775 public/uploads</span>.</p>
                <?php else: ?>
                    <p style="color:var(--success);"><strong>✓ Statut :</strong> Le dossier existe et est accessible en écriture.</p>
                <?php endif; ?>
            </div>

            <div class="recommendation">
                <h4>
                    <span class="status-indicator <?php 
                        $docRoot = realpath($diagnostics['server']['DOCUMENT_ROOT']);
                        $pubPath = realpath($diagnostics['laravel']['PUBLIC_PATH']);
                        echo ($docRoot === $pubPath || str_starts_with($pubPath, $docRoot)) ? 'status-ok' : 'status-warn';
                    ?>"></span>
                    2. Alignement Document Root et Laravel public_path()
                </h4>
                <p>
                    Pour que l'URL <span class="code-inline"><?php echo htmlspecialchars(config('app.url')); ?>/uploads/fichier.jpg</span> serve le fichier physique de 
                    <span class="code-inline"><?php echo htmlspecialchars($dirStatus['public/uploads']['path']); ?></span>, 
                    votre Document Root Apache/Hostinger doit correspondre à <span class="code-inline">public_path()</span> ou contenir la structure.
                </p>
                <p>
                    Document Root réel : <span class="code-inline"><?php echo realpath($diagnostics['server']['DOCUMENT_ROOT']); ?></span><br>
                    Laravel public_path() réel : <span class="code-inline"><?php echo realpath($diagnostics['laravel']['PUBLIC_PATH']); ?></span>
                </p>
                <?php if (realpath($diagnostics['server']['DOCUMENT_ROOT']) !== realpath($diagnostics['laravel']['PUBLIC_PATH'])): ?>
                    <p style="color:var(--warning);"><strong>⚠️ Attention :</strong> Votre serveur web sert les fichiers depuis un dossier différent de votre dossier public Laravel. Les fichiers uploadés dans <span class="code-inline">public/uploads</span> ne seront PAS visibles via l'URL Web directe, sauf si vous créez un lien symbolique :<br>
                    <pre>ln -s <?php echo escapeshellarg($dirStatus['public/uploads']['path']); ?> <?php echo escapeshellarg(realpath($diagnostics['server']['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . 'uploads'); ?></pre>
                    </p>
                <?php else: ?>
                    <p style="color:var(--success);"><strong>✓ Statut :</strong> Le dossier Document Root et le dossier public de Laravel coïncident.</p>
                <?php endif; ?>
            </div>
            
            <div class="recommendation" style="border-color: var(--warning); background: rgba(245, 158, 11, 0.03);">
                <h4 style="color:#fbbf24;">💡 Comment Résoudre la Suppression Hostinger ?</h4>
                <p>
                    Si Hostinger supprime les fichiers lors du déploiement Git ou ZIP (car le dossier <span class="code-inline">public/uploads</span> est nettoyé par Git ou par l'outil de déploiement) :
                </p>
                <ol style="margin-top: 5px; padding-left: 20px;">
                    <li>Créez un dossier **persistant** en dehors de votre dossier de déploiement (ex: <span class="code-inline">/home/votre_username/uploads_persistant</span>).</li>
                    <li>Dans votre script de déploiement <span class="code-inline">deploy.sh</span> (ou via SSH après déploiement), supprimez le dossier temporaire et créez un lien symbolique permanent :
                        <pre>rm -rf <?php echo escapeshellarg($dirStatus['public/uploads']['path']); ?>&#10;ln -s /home/votre_username/uploads_persistant <?php echo escapeshellarg($dirStatus['public/uploads']['path']); ?></pre>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>
