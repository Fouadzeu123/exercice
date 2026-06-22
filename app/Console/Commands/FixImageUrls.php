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
        // Force HTTPS base URL for WebView compatibility
        $baseUrl = str_replace('http://', 'https://', $baseUrl);
        $count = 0;

        // Fix Nodes
        Node::withTrashed()->whereNotNull('image')
            ->each(function ($node) use ($baseUrl, &$count) {
                $original = $node->image;
                if (str_starts_with($node->image, '/uploads/')) {
                    $node->image = $baseUrl . $node->image;
                } elseif (str_starts_with($node->image, 'http://')) {
                    $node->image = str_replace('http://', 'https://', $node->image);
                }

                if ($node->image !== $original) {
                    $node->save();
                    $count++;
                    $this->line("Fixed Node #{$node->id}: {$node->image}");
                }
            });

        // Fix AVIP Products
        AVIPProduct::withTrashed()->whereNotNull('image')
            ->each(function ($product) use ($baseUrl, &$count) {
                $original = $product->image;
                if (str_starts_with($product->image, '/uploads/')) {
                    $product->image = $baseUrl . $product->image;
                } elseif (str_starts_with($product->image, 'http://')) {
                    $product->image = str_replace('http://', 'https://', $product->image);
                }

                if ($product->image !== $original) {
                    $product->save();
                    $count++;
                    $this->line("Fixed AVIPProduct #{$product->id}: {$product->image}");
                }
            });

        // Fix Vault Plans
        VaultPlan::whereNotNull('image')
            ->each(function ($plan) use ($baseUrl, &$count) {
                $original = $plan->image;
                if (str_starts_with($plan->image, '/uploads/')) {
                    $plan->image = $baseUrl . $plan->image;
                } elseif (str_starts_with($plan->image, 'http://')) {
                    $plan->image = str_replace('http://', 'https://', $plan->image);
                }

                if ($plan->image !== $original) {
                    $plan->save();
                    $count++;
                    $this->line("Fixed VaultPlan #{$plan->id}: {$plan->image}");
                }
            });

        // Fix Announcements
        Announcement::whereNotNull('image_url')
            ->each(function ($ann) use ($baseUrl, &$count) {
                $original = $ann->image_url;
                if (str_starts_with($ann->image_url, '/uploads/')) {
                    $ann->image_url = $baseUrl . $ann->image_url;
                } elseif (str_starts_with($ann->image_url, 'http://')) {
                    $ann->image_url = str_replace('http://', 'https://', $ann->image_url);
                }

                if ($ann->image_url !== $original) {
                    $ann->save();
                    $count++;
                    $this->line("Fixed Announcement #{$ann->id}: {$ann->image_url}");
                }
            });

        $this->info("✅ Done! Fixed {$count} image URL(s) in the database.");

        return 0;
    }
}
