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
        Schema::create('base_user', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('base_commerciale_id');
            $table->unsignedBigInteger('user_id');

            $table->foreign('base_commerciale_id')->references('id')->on('bases_commerciales')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['base_commerciale_id', 'user_id']);
            $table->index('base_commerciale_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_user');
    }
};
