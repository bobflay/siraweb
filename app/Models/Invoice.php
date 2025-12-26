<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'status',
        'delivered_at',
        'source_image_path',
        'visit_photo_id',
        'supplier',
        'document_type',
        'invoice_number',
        'invoice_date',
        'print_time',
        'operator',
        'client_name',
        'client_code',
        'client_reference',
        'total_ht',
        'total_tax',
        'total_ttc',
        'port_ht',
        'net_to_pay',
        'net_to_pay_words',
        'packages_count',
        'total_weight',
        'taxes',
        'raw_ocr_data',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'delivered_at' => 'datetime',
        'total_ht' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_ttc' => 'decimal:2',
        'port_ht' => 'decimal:2',
        'net_to_pay' => 'decimal:2',
        'total_weight' => 'decimal:2',
        'taxes' => 'array',
        'raw_ocr_data' => 'array',
    ];

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visitPhoto(): BelongsTo
    {
        return $this->belongsTo(VisitPhoto::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function photos()
    {
        return $this->morphMany(VisitPhoto::class, 'photoable');
    }

    /**
     * Create an Invoice from OCR data
     */
    public static function createFromOcrData(array $ocrData, int $userId, ?string $imagePath = null, ?int $visitPhotoId = null): self
    {
        $invoice = self::create([
            'user_id' => $userId,
            'source_image_path' => $imagePath,
            'visit_photo_id' => $visitPhotoId,
            'supplier' => $ocrData['invoice']['supplier'] ?? null,
            'document_type' => $ocrData['invoice']['document_type'] ?? null,
            'invoice_number' => $ocrData['invoice']['invoice_number'] ?? null,
            'invoice_date' => $ocrData['invoice']['date'] ?? null,
            'print_time' => $ocrData['invoice']['print_time'] ?? null,
            'operator' => $ocrData['invoice']['operator'] ?? null,
            'client_name' => $ocrData['client']['name'] ?? null,
            'client_code' => $ocrData['client']['code'] ?? null,
            'client_reference' => $ocrData['client']['reference'] ?? null,
            'total_ht' => $ocrData['totals']['total_ht'] ?? null,
            'total_tax' => $ocrData['totals']['total_tax'] ?? null,
            'total_ttc' => $ocrData['totals']['total_ttc'] ?? null,
            'port_ht' => $ocrData['totals']['port_ht'] ?? null,
            'net_to_pay' => $ocrData['totals']['net_to_pay'] ?? null,
            'net_to_pay_words' => $ocrData['totals']['net_to_pay_words'] ?? null,
            'packages_count' => $ocrData['logistics']['packages_count'] ?? null,
            'total_weight' => $ocrData['logistics']['total_weight'] ?? null,
            'taxes' => $ocrData['taxes'] ?? null,
            'raw_ocr_data' => $ocrData,
        ]);

        // Create invoice items
        if (!empty($ocrData['items'])) {
            foreach ($ocrData['items'] as $item) {
                $invoice->items()->create([
                    'reference' => $item['reference'] ?? null,
                    'designation' => $item['designation'] ?? null,
                    'quantity' => $item['quantity'] ?? null,
                    'unit_price_ttc' => $item['unit_price_ttc'] ?? null,
                    'total_ttc' => $item['total_ttc'] ?? null,
                    'depot' => $item['depot'] ?? null,
                ]);
            }
        }

        return $invoice->load('items');
    }
}
