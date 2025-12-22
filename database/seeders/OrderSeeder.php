<?php

namespace Database\Seeders;

use App\Models\BaseCommerciale;
use App\Models\BaseProduct;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();
        $users = User::all();
        $visits = Visit::all();

        if ($clients->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Please seed clients and users first.');
            return;
        }

        $statuses = ['draft', 'submitted', 'validated', 'prepared', 'delivered', 'cancelled'];

        foreach ($clients->take(50) as $client) {
            $user = $users->random();
            $visit = $visits->isNotEmpty() && rand(0, 1) ? $visits->random() : null;

            // Create 1-3 orders per client
            $orderCount = rand(1, 3);

            for ($i = 0; $i < $orderCount; $i++) {
                $orderedAt = now()->subDays(rand(1, 90));
                $status = $statuses[array_rand($statuses)];

                $order = Order::create([
                    'reference' => 'ORD-' . strtoupper(uniqid()),
                    'client_id' => $client->id,
                    'user_id' => $user->id,
                    'visit_id' => $visit?->id,
                    'base_commerciale_id' => $client->base_commerciale_id,
                    'zone_id' => $client->zone_id,
                    'total_amount' => 0, // Will be calculated after adding items
                    'currency' => 'XOF',
                    'status' => $status,
                    'ordered_at' => $orderedAt,
                    'validated_at' => in_array($status, ['validated', 'prepared', 'delivered'])
                        ? $orderedAt->copy()->addHours(rand(1, 24))
                        : null,
                ]);

                // Add order items
                $this->createOrderItems($order, $client->base_commerciale_id);
            }
        }

        $this->command->info('Orders seeded successfully.');
    }

    private function createOrderItems(Order $order, int $baseCommercialeId): void
    {
        $baseProducts = BaseProduct::where('base_commerciale_id', $baseCommercialeId)
            ->where('is_active', true)
            ->with('product')
            ->get();

        if ($baseProducts->isEmpty()) {
            // Fallback to any active base products
            $baseProducts = BaseProduct::where('is_active', true)
                ->with('product')
                ->inRandomOrder()
                ->take(5)
                ->get();
        }

        if ($baseProducts->isEmpty()) {
            $this->command->warn('No base products available for order items.');
            return;
        }

        // Add 1-5 items per order
        $itemCount = rand(1, 5);
        $selectedProducts = $baseProducts->random(min($itemCount, $baseProducts->count()));

        $totalAmount = 0;

        foreach ($selectedProducts as $baseProduct) {
            $quantity = rand(1, 20);
            $unitPrice = $baseProduct->current_price;
            $lineTotal = $quantity * $unitPrice;

            OrderItem::create([
                'order_id' => $order->id,
                'base_product_id' => $baseProduct->id,
                'product_name_snapshot' => $baseProduct->product->name,
                'sku_snapshot' => $baseProduct->product->sku_global,
                'unit_snapshot' => $baseProduct->product->unit,
                'packaging_snapshot' => $baseProduct->product->packaging,
                'unit_price_snapshot' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
            ]);

            $totalAmount += $lineTotal;
        }

        // Update order total
        $order->update(['total_amount' => $totalAmount]);
    }
}
