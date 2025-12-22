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
        Schema::table('visit_reports', function (Blueprint $table) {
            // Add new fields matching requirements
            $table->boolean('needs_order')->default(false)->after('order_made');
            $table->boolean('stock_shortage_observed')->default(false)->after('needs_order');
            $table->boolean('competitor_activity_observed')->default(false)->after('stock_shortage_observed');

            // Rename existing fields to match new naming
            // Note: We'll keep the old fields and map them in the model for backward compatibility
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_reports', function (Blueprint $table) {
            $table->dropColumn([
                'needs_order',
                'stock_shortage_observed',
                'competitor_activity_observed',
            ]);
        });
    }
};
