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
        Schema::create('zone_user', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('zone_id');
            $table->unsignedBigInteger('user_id');

            $table->foreign('zone_id')->references('id')->on('zones')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['zone_id', 'user_id']);
            $table->index('zone_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zone_user');
    }
};
