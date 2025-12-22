<?php

namespace Database\Seeders;

use App\Models\Routing;
use App\Models\User;
use App\Models\BaseCommerciale;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RoutingSeeder extends Seeder
{
    public function run(): void
    {
        $commercials = User::whereHas('roles', function ($query) {
            $query->where('code', 'ROLE_AGENT');
        })->get();

        if ($commercials->isEmpty()) {
            $this->command->warn('No ROLE_AGENT users found. Skipping RoutingSeeder.');
            return;
        }

        $bases = BaseCommerciale::all();
        $zones = Zone::all();

        if ($bases->isEmpty() || $zones->isEmpty()) {
            $this->command->warn('No bases or zones found. Skipping RoutingSeeder.');
            return;
        }

        $admin = User::whereHas('roles', function ($query) {
            $query->where('code', 'ROLE_SUPER_ADMIN');
        })->first();

        if (!$admin) {
            $admin = User::first();
        }

        // Create routings for last 3 days, today, and next 3 days
        for ($i = -3; $i <= 3; $i++) {
            $date = Carbon::now()->addDays($i);

            foreach ($commercials->take(3) as $commercial) {
                $base = $bases->random();
                $zone = $zones->where('base_commerciale_id', $base->id)->first() ?? $zones->random();

                $status = match (true) {
                    $i < 0 => 'completed',
                    $i === 0 => 'in_progress',
                    default => 'planned',
                };

                Routing::create([
                    'user_id' => $commercial->id,
                    'base_commerciale_id' => $base->id,
                    'zone_id' => $zone->id,
                    'route_date' => $date->format('Y-m-d'),
                    'start_time' => '08:00:00',
                    'status' => $status,
                    'created_by' => $admin->id,
                ]);
            }
        }

        $this->command->info('Routings seeded successfully.');
    }
}
