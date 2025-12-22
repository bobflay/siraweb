<?php

namespace Database\Seeders;

use App\Models\RoutingItem;
use App\Models\Routing;
use App\Models\Client;
use App\Models\Visit;
use Illuminate\Database\Seeder;

class RoutingItemSeeder extends Seeder
{
    public function run(): void
    {
        $routings = Routing::all();

        if ($routings->isEmpty()) {
            $this->command->warn('No routings found. Skipping RoutingItemSeeder.');
            return;
        }

        foreach ($routings as $routing) {
            // Get clients from the same base/zone
            $clients = Client::where('base_commerciale_id', $routing->base_commerciale_id)
                ->when($routing->zone_id, function ($query) use ($routing) {
                    $query->where('zone_id', $routing->zone_id);
                })
                ->get();

            if ($clients->isEmpty()) {
                $clients = Client::inRandomOrder()->take(10)->get();
            }

            $itemCount = rand(5, 10);
            $clientsToUse = $clients->random(min($itemCount, $clients->count()));

            foreach ($clientsToUse as $index => $client) {
                $status = $this->determineStatus($routing->status);

                // Try to find an existing visit for this client and routing user
                $visit = null;
                if ($status === 'visited') {
                    $visit = Visit::where('client_id', $client->id)
                        ->where('user_id', $routing->user_id)
                        ->whereDate('started_at', $routing->route_date)
                        ->first();
                }

                RoutingItem::create([
                    'routing_id' => $routing->id,
                    'client_id' => $client->id,
                    'zone_id' => $client->zone_id ?? $routing->zone_id,
                    'sequence_order' => $index + 1,
                    'planned_at' => $routing->route_date->addHours(8 + $index),
                    'visit_id' => $visit?->id,
                    'status' => $status,
                    'overridden' => rand(0, 10) > 8,
                    'override_reason' => rand(0, 10) > 8 ? 'Client requested schedule change' : null,
                ]);
            }
        }

        $this->command->info('RoutingItems seeded successfully.');
    }

    private function determineStatus(string $routingStatus): string
    {
        if ($routingStatus === 'completed') {
            $rand = rand(1, 10);
            if ($rand <= 7) return 'visited';
            if ($rand <= 9) return 'skipped';
            return 'pending';
        }

        if ($routingStatus === 'in_progress') {
            $rand = rand(1, 10);
            if ($rand <= 4) return 'visited';
            if ($rand <= 6) return 'skipped';
            return 'pending';
        }

        return 'pending';
    }
}
