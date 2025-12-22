<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\BaseCommerciale;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ivoirianNames = [
            ['name' => 'Kouadio Aman', 'email' => 'kouadio.aman@sira.ci'],
            ['name' => 'Koné Fatou', 'email' => 'kone.fatou@sira.ci'],
            ['name' => 'Yao Kouassi', 'email' => 'yao.kouassi@sira.ci'],
            ['name' => 'Bamba Aminata', 'email' => 'bamba.aminata@sira.ci'],
            ['name' => 'Traoré Ibrahim', 'email' => 'traore.ibrahim@sira.ci'],
            ['name' => 'N\'Guessan Marie', 'email' => 'nguessan.marie@sira.ci'],
            ['name' => 'Diabaté Moussa', 'email' => 'diabate.moussa@sira.ci'],
            ['name' => 'Ouattara Salif', 'email' => 'ouattara.salif@sira.ci'],
            ['name' => 'Kouamé Adjoua', 'email' => 'kouame.adjoua@sira.ci'],
            ['name' => 'Soro Abou', 'email' => 'soro.abou@sira.ci'],
            ['name' => 'Diomandé Mariam', 'email' => 'diomande.mariam@sira.ci'],
            ['name' => 'Kassi Aya', 'email' => 'kassi.aya@sira.ci'],
            ['name' => 'Bakayoko Seydou', 'email' => 'bakayoko.seydou@sira.ci'],
            ['name' => 'Coulibaly Aïcha', 'email' => 'coulibaly.aicha@sira.ci'],
            ['name' => 'Gnabro Pascal', 'email' => 'gnabro.pascal@sira.ci'],
            ['name' => 'Diouf Ndeye', 'email' => 'diouf.ndeye@sira.ci'],
            ['name' => 'Touré Amadou', 'email' => 'toure.amadou@sira.ci'],
            ['name' => 'Beugré Clarisse', 'email' => 'beugre.clarisse@sira.ci'],
            ['name' => 'Silué Lassina', 'email' => 'silue.lassina@sira.ci'],
            ['name' => 'Gbagbo Simone', 'email' => 'gbagbo.simone@sira.ci'],
            ['name' => 'Koffi Yves', 'email' => 'koffi.yves@sira.ci'],
            ['name' => 'Assalé Jean', 'email' => 'assale.jean@sira.ci'],
            ['name' => 'Brou Augustin', 'email' => 'brou.augustin@sira.ci'],
            ['name' => 'Dago Solange', 'email' => 'dago.solange@sira.ci'],
            ['name' => 'Fofana Siaka', 'email' => 'fofana.siaka@sira.ci'],
            ['name' => 'Sanogo Drissa', 'email' => 'sanogo.drissa@sira.ci'],
            ['name' => 'Camara Fatouma', 'email' => 'camara.fatouma@sira.ci'],
            ['name' => 'Doumbia Adama', 'email' => 'doumbia.adama@sira.ci'],
            ['name' => 'Konan Adjoua', 'email' => 'konan.adjoua@sira.ci'],
            ['name' => 'Zadi Ange', 'email' => 'zadi.ange@sira.ci'],
            ['name' => 'Toure Kadiatou', 'email' => 'toure.kadiatou@sira.ci'],
            ['name' => 'Kone Mamadou', 'email' => 'kone.mamadou@sira.ci'],
            ['name' => 'Yapi Bernadette', 'email' => 'yapi.bernadette@sira.ci'],
            ['name' => 'Meite Sekou', 'email' => 'meite.sekou@sira.ci'],
            ['name' => 'Ouedraogo Zenabou', 'email' => 'ouedraogo.zenabou@sira.ci'],
        ];

        // Get all roles
        $roles = Role::all()->keyBy('code');
        $bases = BaseCommerciale::all();
        $zones = Zone::all();

        if ($roles->isEmpty()) {
            $this->command->warn('Please ensure roles are seeded before running UserSeeder.');
            return;
        }

        if ($bases->isEmpty() || $zones->isEmpty()) {
            $this->command->warn('Please ensure bases_commerciales and zones are seeded before running UserSeeder.');
            return;
        }

        // Create Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@sira.ci',
            'password' => Hash::make('password'),
            'phone' => '+225 01 02 03 04 05',
        ]);
        if ($roles->has('ROLE_SUPER_ADMIN')) {
            $superAdmin->roles()->attach($roles['ROLE_SUPER_ADMIN']->id);
        }
        $this->command->info('Created Super Admin');

        // Create Bob (Super Admin)
        $bob = User::create([
            'name' => 'Bob',
            'email' => 'bob.fleifel@gmail.com',
            'password' => Hash::make('12345678'),
            'phone' => '+225 01 00 00 00 01',
        ]);
        if ($roles->has('ROLE_SUPER_ADMIN')) {
            $bob->roles()->attach($roles['ROLE_SUPER_ADMIN']->id);
        }
        // Assign to random base and zone
        $randomBase = $bases->random();
        $bob->basesCommerciales()->attach($randomBase->id);
        $randomZone = $zones->where('base_commerciale_id', $randomBase->id)->random();
        $bob->zones()->attach($randomZone->id);
        $this->command->info("Created Bob (Super Admin) - Base: {$randomBase->name}, Zone: {$randomZone->name}");

        // Create Commercial Admin
        $commercialAdmin = User::create([
            'name' => 'Koné Abou',
            'email' => 'commercial.admin@sira.ci',
            'password' => Hash::make('password'),
            'phone' => '+225 07 11 22 33 44',
        ]);
        if ($roles->has('ROLE_COMMERCIAL_ADMIN')) {
            $commercialAdmin->roles()->attach($roles['ROLE_COMMERCIAL_ADMIN']->id);
        }
        $this->command->info('Created Commercial Admin');

        // Create Kevin Kone (Commercial Admin)
        $kevin = User::create([
            'name' => 'Kevin Kone',
            'email' => 'kone@sira.pro',
            'password' => Hash::make('12345678'),
            'phone' => '+225 01 00 00 00 02',
        ]);
        if ($roles->has('ROLE_COMMERCIAL_ADMIN')) {
            $kevin->roles()->attach($roles['ROLE_COMMERCIAL_ADMIN']->id);
        }
        // Assign to random base and zone
        $randomBase = $bases->random();
        $kevin->basesCommerciales()->attach($randomBase->id);
        $randomZone = $zones->where('base_commerciale_id', $randomBase->id)->random();
        $kevin->zones()->attach($randomZone->id);
        $this->command->info("Created Kevin Kone (Commercial Admin) - Base: {$randomBase->name}, Zone: {$randomZone->name}");

        // Create Finance User
        $finance = User::create([
            'name' => 'Diallo Fatoumata',
            'email' => 'finance@sira.ci',
            'password' => Hash::make('password'),
            'phone' => '+225 05 66 77 88 99',
        ]);
        if ($roles->has('ROLE_FINANCE')) {
            $finance->roles()->attach($roles['ROLE_FINANCE']->id);
        }
        $this->command->info('Created Finance User');

        // Create Base Managers (one per base)
        $nameIndex = 0;
        foreach ($bases as $base) {
            if ($nameIndex >= count($ivoirianNames)) break;

            $userData = $ivoirianNames[$nameIndex++];
            $manager = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'phone' => '+225 ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
            ]);

            if ($roles->has('ROLE_BASE_MANAGER')) {
                $manager->roles()->attach($roles['ROLE_BASE_MANAGER']->id);
            }

            // Assign base to manager
            $manager->basesCommerciales()->attach($base->id);

            $this->command->info("Created Base Manager: {$userData['name']} for base {$base->name}");
        }

        // Create Agents (2-3 per zone, with some agents covering multiple zones)
        foreach ($zones as $zone) {
            $numAgents = rand(2, 3);

            for ($i = 0; $i < $numAgents; $i++) {
                if ($nameIndex >= count($ivoirianNames)) break 2;

                $userData = $ivoirianNames[$nameIndex++];
                $agent = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password'),
                    'phone' => '+225 ' . sprintf('%02d %02d %02d %02d %02d', rand(1, 9), rand(10, 99), rand(10, 99), rand(10, 99), rand(10, 99)),
                ]);

                if ($roles->has('ROLE_AGENT')) {
                    $agent->roles()->attach($roles['ROLE_AGENT']->id);
                }

                // Assign base and primary zone to agent
                $agent->basesCommerciales()->attach($zone->base_commerciale_id);
                $agent->zones()->attach($zone->id);

                // 30% chance to assign agent to additional zone in same base
                if (rand(1, 100) <= 30) {
                    $additionalZones = $zones->where('base_commerciale_id', $zone->base_commerciale_id)
                                            ->where('id', '!=', $zone->id);
                    if ($additionalZones->isNotEmpty()) {
                        $extraZone = $additionalZones->random();
                        $agent->zones()->syncWithoutDetaching($extraZone->id);
                        $this->command->info("Created Agent: {$userData['name']} for zones {$zone->name} + {$extraZone->name}");
                    } else {
                        $this->command->info("Created Agent: {$userData['name']} for zone {$zone->name}");
                    }
                } else {
                    $this->command->info("Created Agent: {$userData['name']} for zone {$zone->name}");
                }
            }
        }

        // Create some additional multi-base managers (commercial admins with base assignments)
        if ($nameIndex < count($ivoirianNames) && $bases->count() >= 2) {
            $userData = $ivoirianNames[$nameIndex++];
            $multiBaseManager = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'phone' => '+225 ' . sprintf('%02d %02d %02d %02d %02d', rand(1, 9), rand(10, 99), rand(10, 99), rand(10, 99), rand(10, 99)),
            ]);

            if ($roles->has('ROLE_BASE_MANAGER')) {
                $multiBaseManager->roles()->attach($roles['ROLE_BASE_MANAGER']->id);
            }

            // Assign to 2 bases
            $selectedBases = $bases->random(min(2, $bases->count()));
            foreach ($selectedBases as $base) {
                $multiBaseManager->basesCommerciales()->attach($base->id);
            }

            $this->command->info("Created Multi-Base Manager: {$userData['name']} for " . $selectedBases->pluck('name')->implode(', '));
        }

        $this->command->info('User seeding completed successfully!');
        $this->command->info('Default password for all users: password');
    }
}
