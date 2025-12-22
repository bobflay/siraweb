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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('reference')->unique();

            // Relationships
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->cascadeOnDelete();
            $table->foreignId('base_commerciale_id')->constrained('bases_commerciales')->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();

            // Order data
            $table->decimal('total_amount', 14, 2);
            $table->string('currency')->default('XOF');

            // Status & workflow
            $table->enum('status', [
                'draft',
                'submitted',
                'validated',
                'prepared',
                'delivered',
                'cancelled'
            ])->default('draft');

            // Audit & timestamps
            $table->datetime('ordered_at');
            $table->datetime('validated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('client_id');
            $table->index('user_id');
            $table->index('visit_id');
            $table->index('base_commerciale_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
