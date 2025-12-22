<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BaseCommercialeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bases = [
            [
                'code' => 'BASE_ABJ_NORD',
                'name' => 'Abidjan Nord',
                'description' => 'Base commerciale couvrant le nord d\'Abidjan',
                'city' => 'Abidjan',
                'region' => 'Abidjan Nord',
                'latitude' => 5.3599517,
                'longitude' => -4.0082563,
                'default_currency' => 'XOF',
                'default_tax_rate' => 18.00,
                'allow_discount' => true,
                'max_discount_percent' => 15.00,
                'order_cutoff_time' => '18:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BASE_ABJ_SUD',
                'name' => 'Abidjan Sud',
                'description' => 'Base commerciale couvrant le sud d\'Abidjan',
                'city' => 'Abidjan',
                'region' => 'Abidjan Sud',
                'latitude' => 5.3099732,
                'longitude' => -4.0127235,
                'default_currency' => 'XOF',
                'default_tax_rate' => 18.00,
                'allow_discount' => true,
                'max_discount_percent' => 15.00,
                'order_cutoff_time' => '18:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BASE_BOUAKE',
                'name' => 'Bouaké',
                'description' => 'Base commerciale de Bouaké - centre du pays',
                'city' => 'Bouaké',
                'region' => 'Gbêkê',
                'latitude' => 7.6899924,
                'longitude' => -5.0299905,
                'default_currency' => 'XOF',
                'default_tax_rate' => 18.00,
                'allow_discount' => true,
                'max_discount_percent' => 10.00,
                'order_cutoff_time' => '17:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BASE_SAN_PEDRO',
                'name' => 'San Pedro',
                'description' => 'Base commerciale du port de San Pedro',
                'city' => 'San Pedro',
                'region' => 'Bas-Sassandra',
                'latitude' => 4.7485989,
                'longitude' => -6.6363122,
                'default_currency' => 'XOF',
                'default_tax_rate' => 18.00,
                'allow_discount' => true,
                'max_discount_percent' => 12.00,
                'order_cutoff_time' => '17:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BASE_YAMOUSSOUKRO',
                'name' => 'Yamoussoukro',
                'description' => 'Base commerciale de la capitale politique',
                'city' => 'Yamoussoukro',
                'region' => 'Lacs',
                'latitude' => 6.8276228,
                'longitude' => -5.2893433,
                'default_currency' => 'XOF',
                'default_tax_rate' => 18.00,
                'allow_discount' => true,
                'max_discount_percent' => 10.00,
                'order_cutoff_time' => '17:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('bases_commerciales')->insert($bases);
    }
}
