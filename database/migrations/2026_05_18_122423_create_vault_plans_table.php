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
        Schema::create('vault_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('fixed_investment_amount', 15, 2);
            $table->decimal('fixed_return', 15, 2);
            $table->decimal('profit_amount', 15, 2);
            $table->integer('duration'); // in days
            $table->string('image')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vault_plans');
    }
};
