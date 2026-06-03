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
        Schema::table('generation_sessions', function (Blueprint $table) {
            $table->foreignId('user_node_id')->nullable()->after('user_id')->constrained('user_nodes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generation_sessions', function (Blueprint $table) {
            $table->dropForeign(['user_node_id']);
            $table->dropColumn('user_node_id');
        });
    }
};
