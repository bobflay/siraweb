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
        Schema::create('visit_reports', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->unsignedBigInteger('visit_id')->unique();

            // GPS at validation (MANDATORY)
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Report content
            $table->boolean('manager_present')->default(false);
            $table->boolean('order_made')->default(false);

            // Order information (optional)
            $table->string('order_reference')->nullable();
            $table->decimal('order_estimated_amount', 12, 2)->nullable();

            // Observations
            $table->text('stock_issues')->nullable();
            $table->text('competitor_activity')->nullable();
            $table->text('comments')->nullable();

            // Validation
            $table->datetime('validated_at')->nullable();

            // Audit
            $table->timestamps();

            // Foreign keys
            $table->foreign('visit_id')->references('id')->on('visits')->cascadeOnDelete();

            // Indexes
            $table->index('visit_id');
            $table->index('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_reports');
    }
};
