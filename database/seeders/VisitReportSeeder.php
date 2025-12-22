<?php

namespace Database\Seeders;

use App\Models\Visit;
use App\Models\VisitReport;
use Illuminate\Database\Seeder;

class VisitReportSeeder extends Seeder
{
    public function run(): void
    {
        // Get completed and aborted visits (these should have reports)
        $completedVisits = Visit::whereIn('status', ['completed', 'aborted'])->get();

        if ($completedVisits->isEmpty()) {
            $this->command->warn('No completed or aborted visits found. Skipping visit report seeder.');
            return;
        }

        $createdCount = 0;

        foreach ($completedVisits as $visit) {
            // 80% of completed/aborted visits have reports
            if (rand(1, 100) <= 80) {
                $hasOrder = (bool) rand(0, 1);
                $managerPresent = (bool) rand(0, 1);

                // GPS coordinates near client location (with small variation)
                $baseLatitude = 5.3599517; // Abidjan approximate
                $baseLongitude = -4.0082563;

                $latitude = $baseLatitude + (rand(-1000, 1000) / 10000);
                $longitude = $baseLongitude + (rand(-1000, 1000) / 10000);

                $report = VisitReport::create([
                    'visit_id' => $visit->id,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'manager_present' => $managerPresent,
                    'order_made' => $hasOrder,
                    'order_reference' => $hasOrder ? 'ORD-' . strtoupper(uniqid()) : null,
                    'order_estimated_amount' => $hasOrder ? rand(50000, 500000) : null,
                    'stock_issues' => rand(0, 1) ? $this->getRandomStockIssue() : null,
                    'competitor_activity' => rand(0, 1) ? $this->getRandomCompetitorActivity() : null,
                    'comments' => rand(0, 1) ? $this->getRandomComment() : null,
                    'validated_at' => $visit->ended_at ?? now(),
                ]);

                $createdCount++;
            }
        }

        $this->command->info("Created {$createdCount} visit reports out of {$completedVisits->count()} completed/aborted visits");
    }

    private function getRandomStockIssue(): string
    {
        $issues = [
            'Rupture de stock sur Riz 25kg',
            'Stock faible sur boissons gazeuses',
            'Produits expirés détectés (lot #12345)',
            'Manque d\'espace de stockage',
            'Stock endommagé suite aux pluies',
            'Rotation lente sur huile 25L',
            'Surstockage de produits laitiers',
        ];

        return $issues[array_rand($issues)];
    }

    private function getRandomCompetitorActivity(): string
    {
        $activities = [
            'Promotion concurrente sur riz (-15%)',
            'Nouveau concurrent installé à 200m',
            'Distribution gratuite d\'échantillons par Nestlé',
            'Affichage publicitaire concurrent très visible',
            'Prix cassés sur huile alimentaire chez le voisin',
            'Visite commercial concurrent ce matin',
        ];

        return $activities[array_rand($activities)];
    }

    private function getRandomComment(): string
    {
        $comments = [
            'Client satisfait, bonne relation commerciale',
            'Demande de délai de paiement supplémentaire',
            'Intéressé par nouveaux produits',
            'Se plaint de la qualité du dernier lot',
            'Souhaite augmenter la fréquence de livraison',
            'Excellent potentiel de croissance',
            'Client fidèle depuis 5 ans',
            'Difficultés financières apparentes',
        ];

        return $comments[array_rand($comments)];
    }
}
