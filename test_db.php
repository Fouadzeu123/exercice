<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== NODES ===\n";
print_r(\App\Models\Node::pluck('image')->toArray());

echo "=== AVIP PRODUCTS ===\n";
print_r(\App\Models\AVIPProduct::pluck('image')->toArray());

echo "=== ANNOUNCEMENTS ===\n";
print_r(\App\Models\Announcement::pluck('image_url')->toArray());

echo "=== VAULT PLANS ===\n";
print_r(\App\Models\VaultPlan::pluck('image')->toArray());
?>
