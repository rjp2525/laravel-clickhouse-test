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

        Schema::create('product_variants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('upc', 11)->nullable();
            $table->integer('price')->nullable();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
