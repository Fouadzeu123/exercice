<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Node;
use App\Models\AVIPProduct;
use App\Models\VaultPlan;
use App\Models\Announcement;

class FixImageUrls extends Command
{
    protected $signature = 'images:fix-urls';
    protected $description = 'Convert relative /uploads/ image paths to absolute URLs in the database';

    public function handle()
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $count = 0;

        // Fix Nodes
        Node::whereNotNull('image')
            ->where('image', 'like', '/uploads/%')
            ->each(function ($node) use ($baseUrl, &$count) {
                $node->image = $baseUrl . $node->image;
                $node->save();
                $count++;
                $this->line("Fixed Node #{$node->id}: {$node->image}");
            });

        // Fix AVIP Products
        AVIPProduct::whereNotNull('image')
            ->where('image', 'like', '/uploads/%')
            ->each(function ($product) use ($baseUrl, &$count) {
                $product->image = $baseUrl . $product->image;
                $product->save();
                $count++;
                $this->line("Fixed AVIPProduct #{$product->id}: {$product->image}");
            });

        // Fix Vault Plans
        VaultPlan::whereNotNull('image')
            ->where('image', 'like', '/uploads/%')
            ->each(function ($plan) use ($baseUrl, &$count) {
                $plan->image = $baseUrl . $plan->image;
                $plan->save();
                $count++;
                $this->line("Fixed VaultPlan #{$plan->id}: {$plan->image}");
            });

        // Fix Announcements
        Announcement::whereNotNull('image_url')
            ->where('image_url', 'like', '/uploads/%')
            ->each(function ($ann) use ($baseUrl, &$count) {
                $ann->image_url = $baseUrl . $ann->image_url;
                $ann->save();
                $count++;
                $this->line("Fixed Announcement #{$ann->id}: {$ann->image_url}");
            });

        $this->info("✅ Done! Fixed {$count} image URL(s) in the database.");

        return 0;
    }
}
