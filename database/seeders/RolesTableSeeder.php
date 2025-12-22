<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'code' => 'ROLE_AGENT',
                'name' => 'Commercial Terrain',
                'description' => 'Agent commercial sur le terrain',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ROLE_BASE_MANAGER',
                'name' => 'Responsable Base',
                'description' => 'Responsable de base logistique',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ROLE_COMMERCIAL_ADMIN',
                'name' => 'Direction Commerciale',
                'description' => 'Direction et administration commerciale',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ROLE_FINANCE',
                'name' => 'Direction Financière',
                'description' => 'Direction financière et comptabilité',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ROLE_SUPER_ADMIN',
                'name' => 'Super Admin',
                'description' => 'Administrateur système avec tous les droits',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('roles')->insert($roles);
    }
}
