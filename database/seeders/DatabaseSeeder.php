<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // RBAC & Core Data
            RolesTableSeeder::class,
            PermissionsTableSeeder::class,
            PermissionRoleTableSeeder::class,

            // Geography & Organization
            BaseCommercialeSeeder::class,
            ZoneSeeder::class,

            // Products
            ProductCategorySeeder::class,
            ProductSeeder::class,
            BaseProductSeeder::class,

            // Users & Clients
            UserSeeder::class,
            ClientSeeder::class,

            // Visits & Reports (grouped together)
            VisitSeeder::class,
            VisitReportSeeder::class,
            VisitPhotoSeeder::class,
            VisitAlertSeeder::class,

            // Routing & Planning
            RoutingSeeder::class,
            RoutingItemSeeder::class,

            // Orders
            OrderSeeder::class,
        ]);
    }
}
