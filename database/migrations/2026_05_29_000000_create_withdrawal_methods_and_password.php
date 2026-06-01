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
        // 1. Add withdrawal_password to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('withdrawal_password')->nullable()->after('password');
        });

        // 2. Create withdrawal_methods table
        Schema::create('withdrawal_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('operator'); // mtn, orange
            $table->string('full_name');
            $table->string('phone')->unique(); // Unique across all users!
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_methods');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('withdrawal_password');
        });
    }
};
