<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routings', function (Blueprint $table) {
            $table->id();

            // Scope
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('base_commerciale_id');
            $table->unsignedBigInteger('zone_id')->nullable();

            // Planning
            $table->date('route_date');
            $table->time('start_time')->nullable();

            // Status
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');

            // Audit
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            // Constraints
            $table->unique(['user_id', 'route_date']);

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('base_commerciale_id')->references('id')->on('bases_commerciales')->cascadeOnDelete();
            $table->foreign('zone_id')->references('id')->on('zones')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();

            // Indexes
            $table->index('user_id');
            $table->index('base_commerciale_id');
            $table->index('route_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routings');
    }
};
