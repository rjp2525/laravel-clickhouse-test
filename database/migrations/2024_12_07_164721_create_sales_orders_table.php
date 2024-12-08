<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('shipping_address_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignUlid('billing_address_id')->constrained('addresses')->cascadeOnDelete();
            $table->integer('total')->default(0);
            $table->integer('discount')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
