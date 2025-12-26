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
        DB::statement("ALTER TABLE clients MODIFY COLUMN type ENUM('Boutique', 'Supermarché', 'Demi-grossiste', 'Grossiste', 'Distributeur', 'Autre', 'Mamie marché', 'Etalage', 'Boulangerie')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE clients MODIFY COLUMN type ENUM('Boutique', 'Supermarché', 'Demi-grossiste', 'Grossiste', 'Distributeur', 'Autre')");
    }
};
