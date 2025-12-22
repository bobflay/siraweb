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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('base_product_id')->constrained('base_products')->cascadeOnDelete();

            // SNAPSHOT fields (IMMUTABLE)
            $table->string('product_name_snapshot');
            $table->string('sku_snapshot');
            $table->string('unit_snapshot');
            $table->string('packaging_snapshot')->nullable();

            // Pricing snapshot
            $table->decimal('unit_price_snapshot', 12, 2);
            $table->integer('quantity');
            $table->decimal('line_total', 14, 2);

            // Audit
            $table->timestamps();

            // Indexes
            $table->index('order_id');
            $table->index('base_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
