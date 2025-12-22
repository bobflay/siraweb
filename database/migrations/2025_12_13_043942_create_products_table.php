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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('sku_global')->unique();
            $table->string('name');

            // Relationships
            $table->unsignedBigInteger('product_category_id');

            // Product details
            $table->string('unit');
            $table->string('packaging')->nullable();

            // Status & audit
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('product_category_id');

            // Foreign keys
            $table->foreign('product_category_id')->references('id')->on('product_categories')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
