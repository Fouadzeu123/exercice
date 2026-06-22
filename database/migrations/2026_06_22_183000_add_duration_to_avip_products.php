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
            $table->integer('duration')->default(7)->after('avip_level'); // default duration to 7 days
        });

        Schema::table('user_avip_products', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('purchased_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avip_products', function (Blueprint $table) {
            $table->dropColumn('duration');
        });

        Schema::table('user_avip_products', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
