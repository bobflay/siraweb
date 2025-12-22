<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visit_photos', function (Blueprint $table) {
            // Add polymorphic columns
            $table->string('photoable_type')->nullable()->after('visit_id');
            $table->unsignedBigInteger('photoable_id')->nullable()->after('photoable_type');

            // Add index for polymorphic relationship
            $table->index(['photoable_type', 'photoable_id']);
        });

        // Migrate existing data: visit_report_id → photoable
        DB::statement("
            UPDATE visit_photos
            SET photoable_type = 'App\\\\Models\\\\VisitReport',
                photoable_id = visit_report_id
            WHERE visit_report_id IS NOT NULL
        ");

        Schema::table('visit_photos', function (Blueprint $table) {
            // Drop old foreign key and column
            $table->dropForeign(['visit_report_id']);
            $table->dropColumn('visit_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_photos', function (Blueprint $table) {
            // Restore old column
            $table->unsignedBigInteger('visit_report_id')->nullable()->after('visit_id');
        });

        // Migrate data back
        DB::statement("
            UPDATE visit_photos
            SET visit_report_id = photoable_id
            WHERE photoable_type = 'App\\\\Models\\\\VisitReport'
        ");

        Schema::table('visit_photos', function (Blueprint $table) {
            // Restore foreign key
            $table->foreign('visit_report_id')->references('id')->on('visit_reports')->cascadeOnDelete();

            // Drop polymorphic columns
            $table->dropIndex(['photoable_type', 'photoable_id']);
            $table->dropColumn(['photoable_type', 'photoable_id']);
        });
    }
};
