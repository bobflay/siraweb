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
        Schema::create('base_products', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->unsignedBigInteger('base_commerciale_id');
            $table->unsignedBigInteger('product_id');

            // Base-specific identity
            $table->string('sku_base');

            // Pricing
            $table->decimal('current_price', 10, 2);
            $table->boolean('allow_discount')->default(true);

            // Status & audit
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('base_commerciale_id');
            $table->index('product_id');

            // Unique constraints
            $table->unique(['base_commerciale_id', 'sku_base']);
            $table->unique(['base_commerciale_id', 'product_id']);

            // Foreign keys
            $table->foreign('base_commerciale_id')->references('id')->on('bases_commerciales')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_products');
    }
};
