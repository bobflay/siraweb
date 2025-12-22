<?php

namespace Database\Seeders;

use App\Models\BaseCommerciale;
use App\Models\Client;
use App\Models\User;
use App\Models\Visit;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class VisitSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::all();
        $users = User::all();
        $bases = BaseCommerciale::all();
        $zones = Zone::all();

        if ($clients->isEmpty() || $users->isEmpty() || $bases->isEmpty() || $zones->isEmpty()) {
            $this->command->warn('No clients, users, bases, or zones found. Skipping visit seeder.');
            return;
        }

        $totalVisits = 60;

        // Create 50% completed visits (30 visits)
        for ($i = 0; $i < 30; $i++) {
            $startedAt = now()->subDays(rand(0, 7))->subHours(rand(8, 18))->subMinutes(rand(0, 59));
            $endedAt = (clone $startedAt)->addMinutes(rand(15, 45));
            $durationSeconds = $startedAt->diffInSeconds($endedAt);

            Visit::create([
                'client_id' => $clients->random()->id,
                'user_id' => $users->random()->id,
                'base_commerciale_id' => $bases->random()->id,
                'zone_id' => $zones->random()->id,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_seconds' => $durationSeconds,
                'status' => 'completed',
            ]);
        }

        // Create 20% started visits (12 visits)
        for ($i = 0; $i < 12; $i++) {
            $startedAt = now()->subDays(rand(0, 1))->subHours(rand(0, 8))->subMinutes(rand(0, 59));

            Visit::create([
                'client_id' => $clients->random()->id,
                'user_id' => $users->random()->id,
                'base_commerciale_id' => $bases->random()->id,
                'zone_id' => $zones->random()->id,
                'started_at' => $startedAt,
                'ended_at' => null,
                'duration_seconds' => null,
                'status' => 'started',
            ]);
        }

        // Create 15% aborted visits (9 visits)
        for ($i = 0; $i < 9; $i++) {
            $startedAt = now()->subDays(rand(0, 7))->subHours(rand(8, 18))->subMinutes(rand(0, 59));
            $endedAt = (clone $startedAt)->addMinutes(rand(5, 15));
            $durationSeconds = $startedAt->diffInSeconds($endedAt);

            Visit::create([
                'client_id' => $clients->random()->id,
                'user_id' => $users->random()->id,
                'base_commerciale_id' => $bases->random()->id,
                'zone_id' => $zones->random()->id,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_seconds' => $durationSeconds,
                'status' => 'aborted',
            ]);
        }

        // Create 15% visits with null status (9 visits - planned/scheduled)
        for ($i = 0; $i < 9; $i++) {
            $startedAt = now()->addDays(rand(1, 7))->setHour(rand(8, 17))->setMinute(rand(0, 59));

            Visit::create([
                'client_id' => $clients->random()->id,
                'user_id' => $users->random()->id,
                'base_commerciale_id' => $bases->random()->id,
                'zone_id' => $zones->random()->id,
                'started_at' => $startedAt,
                'ended_at' => null,
                'duration_seconds' => null,
                'status' => null,
            ]);
        }

        $this->command->info("Created {$totalVisits} visits: 30 completed, 12 started, 9 aborted, 9 planned (null status)");
    }
}
