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
        Schema::create('visit_photos', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->unsignedBigInteger('visit_report_id');
            $table->unsignedBigInteger('visit_id');

            // Photo file
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();

            // Photo metadata (MANDATORY)
            $table->enum('type', ['facade', 'shelves', 'stock', 'anomaly', 'other']);

            // Optional descriptive data
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            // Geolocation & time (MANDATORY)
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->datetime('taken_at');

            // Audit
            $table->timestamps();

            // Foreign keys
            $table->foreign('visit_report_id')->references('id')->on('visit_reports')->cascadeOnDelete();
            $table->foreign('visit_id')->references('id')->on('visits')->cascadeOnDelete();

            // Indexes
            $table->index('visit_report_id');
            $table->index('visit_id');
            $table->index('type');
            $table->index('taken_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_photos');
    }
};
