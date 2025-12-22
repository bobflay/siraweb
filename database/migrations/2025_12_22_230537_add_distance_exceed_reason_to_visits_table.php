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
        Schema::table('visits', function (Blueprint $table) {
            $table->string('distance_exceed_reason')->nullable()->after('terminated_outside_range');
            $table->text('distance_exceed_reason_other')->nullable()->after('distance_exceed_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['distance_exceed_reason', 'distance_exceed_reason_other']);
        });
    }
};
