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
        Schema::table('visit_photos', function (Blueprint $table) {
            $table->json('ocr_data')->nullable()->after('longitude');
            $table->timestamp('ocr_processed_at')->nullable()->after('ocr_data');
            $table->string('ocr_status')->nullable()->after('ocr_processed_at'); // pending, processing, completed, failed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_photos', function (Blueprint $table) {
            $table->dropColumn(['ocr_data', 'ocr_processed_at', 'ocr_status']);
        });
    }
};
