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
        Schema::create('visit_alerts', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->unsignedBigInteger('visit_id');
            $table->unsignedBigInteger('visit_report_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('base_commerciale_id');
            $table->unsignedBigInteger('zone_id');

            // Alert classification
            $table->enum('type', [
                'rupture_grave',
                'litige_paiement',
                'probleme_rayon',
                'risque_perte_client',
                'demande_speciale',
                'nouvelle_opportunite',
                'autre'
            ]);

            // Alert content
            $table->text('comment');
            $table->string('custom_type')->nullable();

            // Geolocation & time (MANDATORY)
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->datetime('alerted_at');

            // Status & processing
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'closed'])->default('pending');
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->datetime('handled_at')->nullable();
            $table->text('handling_comment')->nullable();

            // Audit
            $table->timestamps();

            // Foreign keys
            $table->foreign('visit_id')->references('id')->on('visits')->cascadeOnDelete();
            $table->foreign('visit_report_id')->references('id')->on('visit_reports')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('base_commerciale_id')->references('id')->on('bases_commerciales')->cascadeOnDelete();
            $table->foreign('zone_id')->references('id')->on('zones')->cascadeOnDelete();
            $table->foreign('handled_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index('visit_id');
            $table->index('client_id');
            $table->index('type');
            $table->index('status');
            $table->index('alerted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_alerts');
    }
};
