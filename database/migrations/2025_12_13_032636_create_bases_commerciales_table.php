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
        Schema::create('bases_commerciales', function (Blueprint $table) {
            $table->id();

            // Identity & Business Keys
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // Geographical Context
            $table->string('city');
            $table->string('region')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Commercial Rules
            $table->string('default_currency')->default('XOF');
            $table->decimal('default_tax_rate', 5, 2)->default(0.00);
            $table->boolean('allow_discount')->default(true);
            $table->decimal('max_discount_percent', 5, 2)->default(0.00);
            $table->time('order_cutoff_time')->nullable();

            // System & Status
            $table->boolean('is_active')->default(true);

            // Audit
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('city');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bases_commerciales');
    }
};
