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
        Schema::table('nodes', function (Blueprint $table) {
            $table->decimal('referral_reward', 15, 2)->default(0.00)->after('amount');
        });

        Schema::table('avip_products', function (Blueprint $table) {
            $table->decimal('referral_reward', 15, 2)->default(0.00)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('referral_reward');
        });

        Schema::table('avip_products', function (Blueprint $table) {
            $table->dropColumn('referral_reward');
        });
    }
};
