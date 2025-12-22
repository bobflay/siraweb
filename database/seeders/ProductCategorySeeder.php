<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'code' => 'BOISSONS',
                'name' => 'Boissons',
                'subcategories' => [
                    ['code' => 'BOISSONS_ALCOOL', 'name' => 'Boissons Alcoolisées'],
                    ['code' => 'BOISSONS_SOFT', 'name' => 'Boissons Gazeuses'],
                    ['code' => 'BOISSONS_JUS', 'name' => 'Jus de Fruits'],
                    ['code' => 'BOISSONS_EAU', 'name' => 'Eau Minérale'],
                    ['code' => 'BOISSONS_ENERGIE', 'name' => 'Boissons Énergétiques'],
                ],
            ],
            [
                'code' => 'ALIMENTAIRE',
                'name' => 'Produits Alimentaires',
                'subcategories' => [
                    ['code' => 'ALIM_RIZ', 'name' => 'Riz'],
                    ['code' => 'ALIM_PATES', 'name' => 'Pâtes'],
                    ['code' => 'ALIM_HUILE', 'name' => 'Huiles Alimentaires'],
                    ['code' => 'ALIM_CONSERVE', 'name' => 'Conserves'],
                    ['code' => 'ALIM_FARINE', 'name' => 'Farines'],
                    ['code' => 'ALIM_SUCRE', 'name' => 'Sucre et Sel'],
                ],
            ],
            [
                'code' => 'HYGIENE',
                'name' => 'Hygiène et Beauté',
                'subcategories' => [
                    ['code' => 'HYG_SAVON', 'name' => 'Savons'],
                    ['code' => 'HYG_SHAMPOOING', 'name' => 'Shampooings'],
                    ['code' => 'HYG_DENT', 'name' => 'Hygiène Dentaire'],
                    ['code' => 'HYG_COSMETIQUE', 'name' => 'Cosmétiques'],
                    ['code' => 'HYG_PARFUM', 'name' => 'Parfums'],
                ],
            ],
            [
                'code' => 'ENTRETIEN',
                'name' => 'Produits Entretien',
                'subcategories' => [
                    ['code' => 'ENT_LESSIVE', 'name' => 'Lessives'],
                    ['code' => 'ENT_JAVEL', 'name' => 'Eau de Javel'],
                    ['code' => 'ENT_NETTOYANT', 'name' => 'Nettoyants'],
                    ['code' => 'ENT_DESINFECTANT', 'name' => 'Désinfectants'],
                ],
            ],
            [
                'code' => 'LAITIERS',
                'name' => 'Produits Laitiers',
                'subcategories' => [
                    ['code' => 'LAIT_POUDRE', 'name' => 'Lait en Poudre'],
                    ['code' => 'LAIT_LIQUIDE', 'name' => 'Lait Liquide'],
                    ['code' => 'LAIT_CONCENTRE', 'name' => 'Lait Concentré'],
                    ['code' => 'LAIT_YAOURT', 'name' => 'Yaourts'],
                ],
            ],
            [
                'code' => 'SNACKS',
                'name' => 'Snacks et Confiseries',
                'subcategories' => [
                    ['code' => 'SNACKS_CHIPS', 'name' => 'Chips'],
                    ['code' => 'SNACKS_BISCUITS', 'name' => 'Biscuits'],
                    ['code' => 'SNACKS_BONBONS', 'name' => 'Bonbons'],
                    ['code' => 'SNACKS_CHOCOLAT', 'name' => 'Chocolats'],
                ],
            ],
            [
                'code' => 'TELECOM',
                'name' => 'Télécommunications',
                'subcategories' => [
                    ['code' => 'TELECOM_CARTES', 'name' => 'Cartes Prépayées'],
                    ['code' => 'TELECOM_ACCESSOIRES', 'name' => 'Accessoires Téléphone'],
                ],
            ],
            [
                'code' => 'AUTRE',
                'name' => 'Autres Produits',
                'subcategories' => [
                    ['code' => 'AUTRE_PAPETERIE', 'name' => 'Papeterie'],
                    ['code' => 'AUTRE_PILE', 'name' => 'Piles et Batteries'],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $parent = ProductCategory::create([
                'code' => $categoryData['code'],
                'name' => $categoryData['name'],
                'parent_id' => null,
                'is_active' => true,
            ]);

            $this->command->info("Created category: {$parent->name}");

            if (isset($categoryData['subcategories'])) {
                foreach ($categoryData['subcategories'] as $subData) {
                    $sub = ProductCategory::create([
                        'code' => $subData['code'],
                        'name' => $subData['name'],
                        'parent_id' => $parent->id,
                        'is_active' => true,
                    ]);

                    $this->command->info("  └─ Created subcategory: {$sub->name}");
                }
            }
        }

        $this->command->info('Product categories seeded successfully!');
    }
}
