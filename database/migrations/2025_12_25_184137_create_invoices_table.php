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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // User relationship
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Source image reference
            $table->string('source_image_path')->nullable();
            $table->foreignId('visit_photo_id')->nullable()->constrained('visit_photos')->onDelete('set null');

            // Invoice info
            $table->string('supplier')->nullable();
            $table->string('document_type')->nullable();
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->time('print_time')->nullable();
            $table->string('operator')->nullable();

            // Client info
            $table->string('client_name')->nullable();
            $table->string('client_code')->nullable();
            $table->string('client_reference')->nullable();

            // Totals
            $table->decimal('total_ht', 15, 2)->nullable();
            $table->decimal('total_tax', 15, 2)->nullable();
            $table->decimal('total_ttc', 15, 2)->nullable();
            $table->decimal('port_ht', 15, 2)->nullable();
            $table->decimal('net_to_pay', 15, 2)->nullable();
            $table->string('net_to_pay_words')->nullable();

            // Logistics
            $table->integer('packages_count')->nullable();
            $table->decimal('total_weight', 10, 2)->nullable();

            // Taxes (stored as JSON for flexibility)
            $table->json('taxes')->nullable();

            // Raw OCR data for reference
            $table->json('raw_ocr_data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
