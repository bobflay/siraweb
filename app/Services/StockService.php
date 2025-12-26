<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\StockCommercial;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockService
{
    /**
     * Add stock when invoice is scanned/created
     * (Products are loaded into agent's vehicle)
     */
    public function addFromInvoice(Invoice $invoice): void
    {
        $userId = $invoice->user_id;

        DB::transaction(function () use ($invoice, $userId) {
            foreach ($invoice->items as $item) {
                // Skip items without product_id
                if (!$item->product_id) {
                    continue;
                }

                // Get or create stock record for this user/product
                $stock = StockCommercial::firstOrCreate(
                    [
                        'user_id' => $userId,
                        'product_id' => $item->product_id,
                    ],
                    ['quantity' => 0]
                );

                // Add quantity to stock
                $stock->quantity += $item->quantity;
                $stock->save();

                // Record the movement
                StockMovement::create([
                    'stock_commercial_id' => $stock->id,
                    'user_id' => $userId,
                    'product_id' => $item->product_id,
                    'movement_type' => 'in',
                    'quantity' => $item->quantity,
                    'reference_type' => 'invoice',
                    'reference_id' => $invoice->id,
                    'notes' => "Loaded in vehicle - Invoice #{$invoice->invoice_number}",
                ]);
            }

            Log::info('Stock added from invoice scan', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'user_id' => $userId,
                'items_count' => $invoice->items->count(),
            ]);
        });
    }

    /**
     * Deduct stock when invoice is delivered to store
     * (Agent delivers products from vehicle to client store)
     */
    public function deductFromInvoice(Invoice $invoice): void
    {
        $userId = $invoice->user_id;

        DB::transaction(function () use ($invoice, $userId) {
            foreach ($invoice->items as $item) {
                // Skip items without product_id
                if (!$item->product_id) {
                    continue;
                }

                // Get or create stock record for this user/product
                $stock = StockCommercial::firstOrCreate(
                    [
                        'user_id' => $userId,
                        'product_id' => $item->product_id,
                    ],
                    ['quantity' => 0]
                );

                // Deduct quantity from stock (can go negative)
                $stock->quantity -= $item->quantity;
                $stock->save();

                // Record the movement
                StockMovement::create([
                    'stock_commercial_id' => $stock->id,
                    'user_id' => $userId,
                    'product_id' => $item->product_id,
                    'movement_type' => 'out',
                    'quantity' => $item->quantity,
                    'reference_type' => 'invoice',
                    'reference_id' => $invoice->id,
                    'notes' => "Delivered to store - Invoice #{$invoice->invoice_number}",
                ]);
            }

            Log::info('Stock deducted from invoice delivery', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'user_id' => $userId,
                'items_count' => $invoice->items->count(),
            ]);
        });
    }

    /**
     * Get current stock for an agent
     */
    public function getAgentStock(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return StockCommercial::with('product')
            ->where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get stock movements for an agent
     */
    public function getAgentMovements(User $user, ?int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return StockMovement::with('product')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Manually adjust stock (for corrections)
     */
    public function adjustStock(User $user, int $productId, float $quantity, string $notes = null): StockCommercial
    {
        return DB::transaction(function () use ($user, $productId, $quantity, $notes) {
            $stock = StockCommercial::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                ],
                ['quantity' => 0]
            );

            $previousQty = $stock->quantity;
            $stock->quantity = $quantity;
            $stock->save();

            // Record adjustment movement
            $adjustmentQty = $quantity - $previousQty;
            StockMovement::create([
                'stock_commercial_id' => $stock->id,
                'user_id' => $user->id,
                'product_id' => $productId,
                'movement_type' => 'adjustment',
                'quantity' => abs($adjustmentQty),
                'reference_type' => null,
                'reference_id' => null,
                'notes' => $notes ?? "Manual adjustment: {$previousQty} → {$quantity}",
            ]);

            return $stock;
        });
    }
}
