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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique();
            $table->string('password');
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->string('referral_code')->unique()->nullable();
            $table->unsignedBigInteger('referrer_id')->nullable();
            $table->integer('vip_level')->default(0);
            $table->integer('avip_level')->default(0);
            $table->integer('draw_spins')->default(0);
            $table->unsignedBigInteger('active_node_id')->nullable();
            $table->string('role')->default('user');
            $table->rememberToken();
            $table->timestamps();

            // Foreign keys (will be established fully later or here if simple)
            // $table->foreign('referrer_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('phone')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
