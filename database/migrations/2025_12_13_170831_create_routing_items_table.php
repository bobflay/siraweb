<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routing_items', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->unsignedBigInteger('routing_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('zone_id');

            // Planned visit info
            $table->integer('sequence_order');
            $table->datetime('planned_at')->nullable();

            // Execution tracking
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->enum('status', ['pending', 'visited', 'skipped'])->default('pending');

            // Admin override tracking
            $table->boolean('overridden')->default(false);
            $table->text('override_reason')->nullable();
            $table->unsignedBigInteger('overridden_by')->nullable();
            $table->datetime('overridden_at')->nullable();

            // Audit
            $table->timestamps();

            // Constraints
            $table->unique(['routing_id', 'sequence_order']);

            // Foreign keys
            $table->foreign('routing_id')->references('id')->on('routings')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('zone_id')->references('id')->on('zones')->cascadeOnDelete();
            $table->foreign('visit_id')->references('id')->on('visits')->cascadeOnDelete();
            $table->foreign('overridden_by')->references('id')->on('users')->cascadeOnDelete();

            // Indexes
            $table->index('routing_id');
            $table->index('client_id');
            $table->index('visit_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_items');
    }
};
