<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'ALIM_RIZ' => ProductCategory::where('code', 'ALIM_RIZ')->first(),
            'ALIM_HUILE' => ProductCategory::where('code', 'ALIM_HUILE')->first(),
            'ALIM_SUCRE' => ProductCategory::where('code', 'ALIM_SUCRE')->first(),
            'ALIM_FARINE' => ProductCategory::where('code', 'ALIM_FARINE')->first(),
            'ALIM_PATES' => ProductCategory::where('code', 'ALIM_PATES')->first(),
            'BOISSONS_SOFT' => ProductCategory::where('code', 'BOISSONS_SOFT')->first(),
            'BOISSONS_JUS' => ProductCategory::where('code', 'BOISSONS_JUS')->first(),
            'BOISSONS_EAU' => ProductCategory::where('code', 'BOISSONS_EAU')->first(),
            'LAIT_POUDRE' => ProductCategory::where('code', 'LAIT_POUDRE')->first(),
            'LAIT_CONCENTRE' => ProductCategory::where('code', 'LAIT_CONCENTRE')->first(),
        ];

        $products = [
            // Rice products
            [
                'sku_global' => 'CD-RIZ-25',
                'name' => 'Riz Carre d Or 25kg',
                'category_code' => 'ALIM_RIZ',
                'unit' => 'sac',
                'packaging' => '25kg',
            ],
            [
                'sku_global' => 'CD-RIZ-50',
                'name' => 'Riz Carre d Or 50kg',
                'category_code' => 'ALIM_RIZ',
                'unit' => 'sac',
                'packaging' => '50kg',
            ],
            [
                'sku_global' => 'CD-RIZ-PARFUME-25',
                'name' => 'Riz Parfume Carre d Or 25kg',
                'category_code' => 'ALIM_RIZ',
                'unit' => 'sac',
                'packaging' => '25kg',
            ],

            // Oil products
            [
                'sku_global' => 'CD-HUILE-5L',
                'name' => 'Huile vegetale Carre d Or 5L',
                'category_code' => 'ALIM_HUILE',
                'unit' => 'bidon',
                'packaging' => '5L',
            ],
            [
                'sku_global' => 'CD-HUILE-20L',
                'name' => 'Huile vegetale Carre d Or 20L',
                'category_code' => 'ALIM_HUILE',
                'unit' => 'bidon',
                'packaging' => '20L',
            ],
            [
                'sku_global' => 'CD-HUILE-25L',
                'name' => 'Huile vegetale Carre d Or 25L',
                'category_code' => 'ALIM_HUILE',
                'unit' => 'bidon',
                'packaging' => '25L',
            ],

            // Sugar products
            [
                'sku_global' => 'CD-SUCRE-50',
                'name' => 'Sucre Carre d Or 50kg',
                'category_code' => 'ALIM_SUCRE',
                'unit' => 'sac',
                'packaging' => '50kg',
            ],
            [
                'sku_global' => 'CD-SUCRE-25',
                'name' => 'Sucre Carre d Or 25kg',
                'category_code' => 'ALIM_SUCRE',
                'unit' => 'sac',
                'packaging' => '25kg',
            ],

            // Flour products
            [
                'sku_global' => 'CD-FARINE-25',
                'name' => 'Farine de ble Carre d Or 25kg',
                'category_code' => 'ALIM_FARINE',
                'unit' => 'sac',
                'packaging' => '25kg',
            ],
            [
                'sku_global' => 'CD-FARINE-50',
                'name' => 'Farine de ble Carre d Or 50kg',
                'category_code' => 'ALIM_FARINE',
                'unit' => 'sac',
                'packaging' => '50kg',
            ],

            // Pasta products
            [
                'sku_global' => 'CD-PATES-5KG',
                'name' => 'Pates alimentaires Carre d Or 5kg',
                'category_code' => 'ALIM_PATES',
                'unit' => 'carton',
                'packaging' => '5kg',
            ],
            [
                'sku_global' => 'CD-PATES-10KG',
                'name' => 'Pates alimentaires Carre d Or 10kg',
                'category_code' => 'ALIM_PATES',
                'unit' => 'carton',
                'packaging' => '10kg',
            ],

            // Beverages - Soft drinks
            [
                'sku_global' => 'CD-COLA-12X1L',
                'name' => 'Boisson gazeuse Cola 12x1L',
                'category_code' => 'BOISSONS_SOFT',
                'unit' => 'pack',
                'packaging' => '12x1L',
            ],
            [
                'sku_global' => 'CD-ORANGE-12X1L',
                'name' => 'Boisson gazeuse Orange 12x1L',
                'category_code' => 'BOISSONS_SOFT',
                'unit' => 'pack',
                'packaging' => '12x1L',
            ],

            // Juice products
            [
                'sku_global' => 'CD-JUS-ORANGE-1L',
                'name' => 'Jus d Orange Carre d Or 1L',
                'category_code' => 'BOISSONS_JUS',
                'unit' => 'bouteille',
                'packaging' => '1L',
            ],
            [
                'sku_global' => 'CD-JUS-ANANAS-1L',
                'name' => 'Jus d Ananas Carre d Or 1L',
                'category_code' => 'BOISSONS_JUS',
                'unit' => 'bouteille',
                'packaging' => '1L',
            ],
            [
                'sku_global' => 'CD-JUS-MANGUE-1L',
                'name' => 'Jus de Mangue Carre d Or 1L',
                'category_code' => 'BOISSONS_JUS',
                'unit' => 'bouteille',
                'packaging' => '1L',
            ],

            // Water products
            [
                'sku_global' => 'CD-EAU-0.5L-24',
                'name' => 'Eau minerale 0.5L pack de 24',
                'category_code' => 'BOISSONS_EAU',
                'unit' => 'pack',
                'packaging' => '24x0.5L',
            ],
            [
                'sku_global' => 'CD-EAU-1.5L-12',
                'name' => 'Eau minerale 1.5L pack de 12',
                'category_code' => 'BOISSONS_EAU',
                'unit' => 'pack',
                'packaging' => '12x1.5L',
            ],

            // Milk products
            [
                'sku_global' => 'CD-LAIT-POUDRE-400G',
                'name' => 'Lait en poudre Carre d Or 400g',
                'category_code' => 'LAIT_POUDRE',
                'unit' => 'boite',
                'packaging' => '400g',
            ],
            [
                'sku_global' => 'CD-LAIT-POUDRE-900G',
                'name' => 'Lait en poudre Carre d Or 900g',
                'category_code' => 'LAIT_POUDRE',
                'unit' => 'boite',
                'packaging' => '900g',
            ],
            [
                'sku_global' => 'CD-LAIT-CONCENTRE-400G',
                'name' => 'Lait concentre Carre d Or 400g',
                'category_code' => 'LAIT_CONCENTRE',
                'unit' => 'boite',
                'packaging' => '400g',
            ],
            [
                'sku_global' => 'CD-LAIT-CONCENTRE-170G',
                'name' => 'Lait concentre Carre d Or 170g',
                'category_code' => 'LAIT_CONCENTRE',
                'unit' => 'boite',
                'packaging' => '170g',
            ],
        ];

        foreach ($products as $productData) {
            $category = $categories[$productData['category_code']] ?? null;

            if (!$category) {
                $this->command->warn("Category {$productData['category_code']} not found. Skipping product {$productData['name']}");
                continue;
            }

            Product::create([
                'sku_global' => $productData['sku_global'],
                'name' => $productData['name'],
                'product_category_id' => $category->id,
                'unit' => $productData['unit'],
                'packaging' => $productData['packaging'],
                'is_active' => true,
            ]);

            $this->command->info("Created product: {$productData['name']}");
        }

        $this->command->info('Global products seeded successfully!');
    }
}
