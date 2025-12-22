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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('base_commerciale_id');
            $table->unsignedBigInteger('zone_id');

            // Timing
            $table->datetime('started_at');
            $table->datetime('ended_at')->nullable();
            $table->integer('duration_seconds')->nullable();

            // Status
            $table->enum('status', ['started', 'completed', 'aborted'])->nullable();

            // Audit
            $table->timestamps();

            // Foreign keys
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('base_commerciale_id')->references('id')->on('bases_commerciales')->cascadeOnDelete();
            $table->foreign('zone_id')->references('id')->on('zones')->cascadeOnDelete();

            // Indexes
            $table->index('client_id');
            $table->index('user_id');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
