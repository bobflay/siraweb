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
            // Drop the foreign key first
            $table->dropForeign(['visit_id']);

            // Make visit_id nullable
            $table->unsignedBigInteger('visit_id')->nullable()->change();

            // Re-add the foreign key
            $table->foreign('visit_id')->references('id')->on('visits')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_photos', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['visit_id']);

            // Make visit_id not nullable again
            $table->unsignedBigInteger('visit_id')->nullable(false)->change();

            // Re-add the foreign key with cascade
            $table->foreign('visit_id')->references('id')->on('visits')->cascadeOnDelete();
        });
    }
};
