<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\BaseCommerciale;
use App\Models\Zone;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ivoirianBusinessNames = [
            'Boutique Adjamé',
            'Supermarché Cocody',
            'Epicerie Yopougon',
            'Magasin Plateau',
            'Boutique Marcory',
            'Commerce Abobo',
            'Superette Treichville',
            'Dépôt Koumassi',
            'Magasin Port-Bouët',
            'Boutique Attécoubé',
            'Epicerie Anyama',
            'Commerce Bingerville',
            'Superette Grand-Bassam',
            'Boutique Songon',
            'Magasin Riviera',
            'Dépôt Deux Plateaux',
            'Commerce Angré',
            'Boutique Niangon',
            'Supermarché Zone 4',
            'Epicerie Williamsville',
            'Magasin San Pedro',
            'Boutique Yamoussoukro',
            'Commerce Bouaké',
            'Superette Daloa',
            'Dépôt Korhogo',
            'Boutique Man',
            'Epicerie Gagnoa',
            'Commerce Divo',
            'Magasin Soubré',
            'Boutique Abengourou',
            'Superette Bondoukou',
            'Commerce Dimbokro',
            'Epicerie Lakota',
            'Boutique Agboville',
            'Magasin Adzopé',
            'Dépôt Ferkessédougou',
            'Commerce Odienné',
            'Boutique Séguéla',
            'Supermarché Danané',
            'Epicerie Duékoué',
            'Magasin Issia',
            'Boutique Sinfra',
            'Commerce Tiassalé',
            'Superette Dabou',
            'Dépôt Jacqueville',
            'Boutique Grand-Lahou',
            'Epicerie Sassandra',
            'Commerce Tabou',
            'Magasin Guiglo',
            'Boutique Toulepleu',
        ];

        $cities = [
            'Abidjan', 'Yamoussoukro', 'Bouaké', 'Daloa', 'San Pedro',
            'Korhogo', 'Man', 'Gagnoa', 'Divo', 'Soubré',
            'Abengourou', 'Bondoukou', 'Grand-Bassam', 'Anyama', 'Bingerville',
        ];

        $districts = [
            'Adjamé', 'Cocody', 'Yopougon', 'Plateau', 'Marcory',
            'Abobo', 'Treichville', 'Koumassi', 'Port-Bouët', 'Attécoubé',
            'Zone 4', 'Riviera', 'Deux Plateaux', 'Angré', 'Niangon',
        ];

        $managerNames = [
            'Kouadio Aman', 'Koné Fatou', 'Yao Kouassi', 'Bamba Aminata',
            'Traoré Ibrahim', 'N\'Guessan Marie', 'Diabaté Moussa', 'Ouattara Salif',
            'Kouamé Adjoua', 'Soro Abou', 'Diomandé Mariam', 'Kassi Aya',
            'Bakayoko Seydou', 'Coulibaly Aïcha', 'Gnabro Pascal', 'Diouf Ndeye',
            'Touré Amadou', 'Beugré Clarisse', 'Silué Lassina', 'Gbagbo Simone',
        ];

        $types = ['Boutique', 'Supermarché', 'Demi-grossiste', 'Grossiste', 'Distributeur', 'Autre'];
        $potentials = ['A', 'B', 'C'];
        $visitFrequencies = ['weekly', 'biweekly', 'monthly', 'other'];

        // Get available bases, zones, and users
        $bases = BaseCommerciale::all();
        $zones = Zone::all();
        $users = User::all();

        if ($bases->isEmpty() || $zones->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Please ensure you have bases_commerciales, zones, and users seeded before running this seeder.');
            return;
        }

        // Coordinates for major Ivorian cities (latitude, longitude)
        $coordinates = [
            ['lat' => 5.3600, 'lng' => -4.0083],  // Abidjan
            ['lat' => 6.8276, 'lng' => -5.2893],  // Yamoussoukro
            ['lat' => 7.6944, 'lng' => -5.0300],  // Bouaké
            ['lat' => 6.8770, 'lng' => -6.4503],  // Daloa
            ['lat' => 4.7471, 'lng' => -6.6363],  // San Pedro
            ['lat' => 9.4580, 'lng' => -5.6294],  // Korhogo
            ['lat' => 7.4125, 'lng' => -7.5547],  // Man
            ['lat' => 6.1319, 'lng' => -5.9506],  // Gagnoa
            ['lat' => 5.8394, 'lng' => -5.3578],  // Divo
            ['lat' => 5.7858, 'lng' => -6.5897],  // Soubré
        ];

        foreach ($ivoirianBusinessNames as $index => $businessName) {
            $base = $bases->random();
            $zone = $zones->where('base_commerciale_id', $base->id)->random();

            if (!$zone) {
                $zone = $zones->random();
            }

            $coord = $coordinates[array_rand($coordinates)];
            // Add small random offset for variation
            $latOffset = (rand(-1000, 1000) / 10000);
            $lngOffset = (rand(-1000, 1000) / 10000);

            Client::create([
                'code' => 'CLI' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'name' => $businessName,
                'type' => $types[array_rand($types)],
                'potential' => $potentials[array_rand($potentials)],
                'base_commerciale_id' => $base->id,
                'zone_id' => $zone->id,
                'created_by' => $users->random()->id,
                'manager_name' => $managerNames[array_rand($managerNames)],
                'phone' => '+225 ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'whatsapp' => rand(0, 1) ? '+225 ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) : null,
                'email' => rand(0, 1) ? strtolower(str_replace(' ', '', $businessName)) . '@example.ci' : null,
                'city' => $cities[array_rand($cities)],
                'district' => rand(0, 1) ? $districts[array_rand($districts)] : null,
                'address_description' => 'Près de ' . ['la mairie', 'le marché', 'la gare', 'l\'église', 'la mosquée'][array_rand(['la mairie', 'le marché', 'la gare', 'l\'église', 'la mosquée'])],
                'latitude' => round($coord['lat'] + $latOffset, 7),
                'longitude' => round($coord['lng'] + $lngOffset, 7),
                'visit_frequency' => $visitFrequencies[array_rand($visitFrequencies)],
                'is_active' => rand(0, 10) > 1, // 90% active
            ]);
        }

        $this->command->info('Created ' . count($ivoirianBusinessNames) . ' clients successfully.');
    }
}
