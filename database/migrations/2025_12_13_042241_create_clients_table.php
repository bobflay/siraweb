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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('code')->unique();
            $table->string('name');

            // Classification
            $table->enum('type', ['Boutique', 'Supermarché', 'Demi-grossiste', 'Grossiste', 'Distributeur', 'Autre']);
            $table->enum('potential', ['A', 'B', 'C']);

            // Ownership & Scope
            $table->unsignedBigInteger('base_commerciale_id');
            $table->unsignedBigInteger('zone_id');
            $table->unsignedBigInteger('created_by');

            // Contact information
            $table->string('manager_name')->nullable();
            $table->string('phone');
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();

            // Address
            $table->string('city');
            $table->string('district')->nullable();
            $table->text('address_description')->nullable();

            // Geolocation (MANDATORY)
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Commercial settings
            $table->enum('visit_frequency', ['weekly', 'biweekly', 'monthly', 'other']);

            // Status & audit
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('base_commerciale_id');
            $table->index('zone_id');
            $table->index('created_by');

            // Foreign keys
            $table->foreign('base_commerciale_id')->references('id')->on('bases_commerciales')->onDelete('restrict');
            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
