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
        Schema::table('vault_plans', function (Blueprint $table) {
            $table->string('payout_type')->default('on_expiration'); // 'daily' or 'on_expiration'
        });

        Schema::table('vault_investments', function (Blueprint $table) {
            $table->integer('payouts_claimed')->default(0);
            $table->timestamp('last_payout_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vault_plans', function (Blueprint $table) {
            $table->dropColumn('payout_type');
        });

        Schema::table('vault_investments', function (Blueprint $table) {
            $table->dropColumn(['payouts_claimed', 'last_payout_at']);
        });
    }
};
