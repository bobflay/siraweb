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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');

            $table->string('reference')->nullable();
            $table->string('designation')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('unit_price_ttc', 15, 2)->nullable();
            $table->decimal('total_ttc', 15, 2)->nullable();
            $table->string('depot')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
