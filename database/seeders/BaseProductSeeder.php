<?php

namespace Database\Seeders;

use App\Models\BaseProduct;
use App\Models\Product;
use App\Models\BaseCommerciale;
use Illuminate\Database\Seeder;

class BaseProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bases = [
            'BASE_ABJ_NORD' => BaseCommerciale::where('code', 'BASE_ABJ_NORD')->first(),
            'BASE_ABJ_SUD' => BaseCommerciale::where('code', 'BASE_ABJ_SUD')->first(),
        ];

        if (!$bases['BASE_ABJ_NORD'] || !$bases['BASE_ABJ_SUD']) {
            $this->command->warn('Base commerciales BASE_ABJ_NORD or BASE_ABJ_SUD not found. Skipping base product seeding.');
            return;
        }

        // Base-specific pricing (in XOF)
        $basePricing = [
            'BASE_ABJ_NORD' => [
                'CD-RIZ-25' => 18500,
                'CD-RIZ-50' => 36000,
                'CD-RIZ-PARFUME-25' => 21000,
                'CD-HUILE-5L' => 9000,
                'CD-HUILE-20L' => 34000,
                'CD-HUILE-25L' => 42000,
                'CD-SUCRE-50' => 32000,
                'CD-SUCRE-25' => 17000,
                'CD-FARINE-25' => 15000,
                'CD-FARINE-50' => 28500,
                'CD-PATES-5KG' => 4500,
                'CD-PATES-10KG' => 8500,
                'CD-COLA-12X1L' => 6000,
                'CD-ORANGE-12X1L' => 6000,
                'CD-JUS-ORANGE-1L' => 800,
                'CD-JUS-ANANAS-1L' => 850,
                'CD-JUS-MANGUE-1L' => 900,
                'CD-EAU-0.5L-24' => 3500,
                'CD-EAU-1.5L-12' => 4000,
                'CD-LAIT-POUDRE-400G' => 3500,
                'CD-LAIT-POUDRE-900G' => 7500,
                'CD-LAIT-CONCENTRE-400G' => 1200,
                'CD-LAIT-CONCENTRE-170G' => 600,
            ],
            'BASE_ABJ_SUD' => [
                'CD-RIZ-25' => 18800,
                'CD-RIZ-50' => 36500,
                'CD-RIZ-PARFUME-25' => 21500,
                'CD-HUILE-5L' => 9200,
                'CD-HUILE-20L' => 34500,
                'CD-HUILE-25L' => 42500,
                'CD-SUCRE-50' => 32500,
                'CD-SUCRE-25' => 17200,
                'CD-FARINE-25' => 15200,
                'CD-FARINE-50' => 29000,
                'CD-PATES-5KG' => 4600,
                'CD-PATES-10KG' => 8700,
                'CD-COLA-12X1L' => 6100,
                'CD-ORANGE-12X1L' => 6100,
                'CD-JUS-ORANGE-1L' => 850,
                'CD-JUS-ANANAS-1L' => 900,
                'CD-JUS-MANGUE-1L' => 950,
                'CD-EAU-0.5L-24' => 3600,
                'CD-EAU-1.5L-12' => 4100,
                'CD-LAIT-POUDRE-400G' => 3600,
                'CD-LAIT-POUDRE-900G' => 7700,
                'CD-LAIT-CONCENTRE-400G' => 1250,
                'CD-LAIT-CONCENTRE-170G' => 650,
            ],
        ];

        foreach ($bases as $baseCode => $base) {
            $pricing = $basePricing[$baseCode];

            foreach ($pricing as $skuGlobal => $price) {
                $product = Product::where('sku_global', $skuGlobal)->first();

                if (!$product) {
                    $this->command->warn("Product {$skuGlobal} not found. Skipping.");
                    continue;
                }

                BaseProduct::create([
                    'base_commerciale_id' => $base->id,
                    'product_id' => $product->id,
                    'sku_base' => $skuGlobal, // Can be customized per base if needed
                    'current_price' => $price,
                    'allow_discount' => true,
                    'is_active' => true,
                ]);

                $this->command->info("Created base product: {$product->name} for {$base->name} at {$price} XOF");
            }
        }

        $this->command->info('Base products seeded successfully!');
    }
}
