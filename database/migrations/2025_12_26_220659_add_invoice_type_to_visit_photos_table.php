<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE visit_photos MODIFY COLUMN type ENUM('facade','shelves','stock','anomaly','other','invoice') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE visit_photos MODIFY COLUMN type ENUM('facade','shelves','stock','anomaly','other') NOT NULL");
    }
};
