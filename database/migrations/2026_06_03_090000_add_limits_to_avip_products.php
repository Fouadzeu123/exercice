<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('avip_products', function (Blueprint $table) {
            $table->integer('stock_quantity')->nullable()->after('image');
            $table->integer('limited_purchase_count')->nullable()->after('stock_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avip_products', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'limited_purchase_count']);
        });
    }
};
