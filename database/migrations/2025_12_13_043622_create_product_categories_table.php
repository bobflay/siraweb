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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('code')->unique();
            $table->string('name');

            // Hierarchy
            $table->unsignedBigInteger('parent_id')->nullable();

            // Status & audit
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('parent_id');

            // Foreign keys
            $table->foreign('parent_id')->references('id')->on('product_categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
