<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lookup bases by code
        $baseAbjNord = DB::table('bases_commerciales')->where('code', 'BASE_ABJ_NORD')->first();
        $baseAbjSud = DB::table('bases_commerciales')->where('code', 'BASE_ABJ_SUD')->first();
        $baseBouake = DB::table('bases_commerciales')->where('code', 'BASE_BOUAKE')->first();
        $baseSanPedro = DB::table('bases_commerciales')->where('code', 'BASE_SAN_PEDRO')->first();
        $baseYamoussoukro = DB::table('bases_commerciales')->where('code', 'BASE_YAMOUSSOUKRO')->first();

        $zones = [
            // BASE_ABJ_NORD zones
            [
                'code' => 'ZONE_ABJ_COCODY',
                'name' => 'Cocody',
                'base_commerciale_id' => $baseAbjNord->id,
                'city' => 'Abidjan',
                'latitude' => 5.3536736,
                'longitude' => -3.9870187,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ZONE_ABJ_ADJAME',
                'name' => 'Adjamé',
                'base_commerciale_id' => $baseAbjNord->id,
                'city' => 'Abidjan',
                'latitude' => 5.3599517,
                'longitude' => -4.0207562,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ZONE_ABJ_YOPOUGON_NORD',
                'name' => 'Yopougon Nord',
                'base_commerciale_id' => $baseAbjNord->id,
                'city' => 'Abidjan',
                'latitude' => 5.3454538,
                'longitude' => -4.0520179,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ZONE_ABJ_ABOBO',
                'name' => 'Abobo',
                'base_commerciale_id' => $baseAbjNord->id,
                'city' => 'Abidjan',
                'latitude' => 5.4286447,
                'longitude' => -4.0203658,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // BASE_ABJ_SUD zones
            [
                'code' => 'ZONE_ABJ_MARCORY',
                'name' => 'Marcory',
                'base_commerciale_id' => $baseAbjSud->id,
                'city' => 'Abidjan',
                'latitude' => 5.2925073,
                'longitude' => -3.9886891,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ZONE_ABJ_TREICHVILLE',
                'name' => 'Treichville',
                'base_commerciale_id' => $baseAbjSud->id,
                'city' => 'Abidjan',
                'latitude' => 5.2879996,
                'longitude' => -4.0087533,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ZONE_ABJ_PORT_BOUET',
                'name' => 'Port-Bouët',
                'base_commerciale_id' => $baseAbjSud->id,
                'city' => 'Abidjan',
                'latitude' => 5.2614441,
                'longitude' => -3.9162067,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ZONE_ABJ_KOUMASSI',
                'name' => 'Koumassi',
                'base_commerciale_id' => $baseAbjSud->id,
                'city' => 'Abidjan',
                'latitude' => 5.2935876,
                'longitude' => -3.9467973,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // BASE_BOUAKE zones
            [
                'code' => 'ZONE_BOUAKE_KOKO',
                'name' => 'Koko',
                'base_commerciale_id' => $baseBouake->id,
                'city' => 'Bouaké',
                'latitude' => 7.6928974,
                'longitude' => -5.0204877,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ZONE_BOUAKE_AIR_FRANCE',
                'name' => 'Air France',
                'base_commerciale_id' => $baseBouake->id,
                'city' => 'Bouaké',
                'latitude' => 7.6845263,
                'longitude' => -5.0389651,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ZONE_BOUAKE_CENTRE',
                'name' => 'Centre-Ville',
                'base_commerciale_id' => $baseBouake->id,
                'city' => 'Bouaké',
                'latitude' => 7.6899924,
                'longitude' => -5.0299905,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // BASE_SAN_PEDRO zones
            [
                'code' => 'ZONE_SAN_PEDRO_CENTRE',
                'name' => 'Centre-Ville',
                'base_commerciale_id' => $baseSanPedro->id,
                'city' => 'San Pedro',
                'latitude' => 4.7485989,
                'longitude' => -6.6363122,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ZONE_SAN_PEDRO_PORT',
                'name' => 'Zone Portuaire',
                'base_commerciale_id' => $baseSanPedro->id,
                'city' => 'San Pedro',
                'latitude' => 4.7557243,
                'longitude' => -6.6487438,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // BASE_YAMOUSSOUKRO zones
            [
                'code' => 'ZONE_YAMOUSSOUKRO_CENTRE',
                'name' => 'Centre-Ville',
                'base_commerciale_id' => $baseYamoussoukro->id,
                'city' => 'Yamoussoukro',
                'latitude' => 6.8276228,
                'longitude' => -5.2893433,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ZONE_YAMOUSSOUKRO_HABITAT',
                'name' => 'Habitat',
                'base_commerciale_id' => $baseYamoussoukro->id,
                'city' => 'Yamoussoukro',
                'latitude' => 6.8185843,
                'longitude' => -5.2768145,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('zones')->insert($zones);
    }
}
