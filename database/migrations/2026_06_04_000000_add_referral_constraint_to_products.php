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
            $table->integer('required_active_referrals')->default(0)->after('is_limited');
        });

        Schema::table('avip_products', function (Blueprint $table) {
            $table->integer('required_active_referrals')->default(0)->after('is_limited');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('required_active_referrals');
        });

        Schema::table('avip_products', function (Blueprint $table) {
            $table->dropColumn('required_active_referrals');
        });
    }
};
