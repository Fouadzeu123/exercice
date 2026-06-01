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
        Schema::create('avip_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('daily_salary', 15, 2);
            $table->integer('required_vip_level'); // Niveau VIP minimum requis pour acheter
            $table->integer('avip_level'); // Niveau AVIP du produit (1, 2, 3, etc.)
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
        Schema::dropIfExists('avip_products');
    }
};
