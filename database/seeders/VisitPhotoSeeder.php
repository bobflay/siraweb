<?php

namespace Database\Seeders;

use App\Models\VisitReport;
use App\Models\VisitAlert;
use App\Models\VisitPhoto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class VisitPhotoSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure storage directory exists
        Storage::disk('public')->makeDirectory('visit_photos');

        $createdCount = 0;

        // Sample placeholder images
        $placeholders = [
            'facade' => $this->createPlaceholderImage('facade', [66, 135, 245]), // Blue
            'shelves' => $this->createPlaceholderImage('shelves', [76, 175, 80]), // Green
            'stock' => $this->createPlaceholderImage('stock', [255, 152, 0]), // Orange
            'anomaly' => $this->createPlaceholderImage('anomaly', [244, 67, 54]), // Red
            'other' => $this->createPlaceholderImage('other', [158, 158, 158]), // Gray
        ];

        // Create photos for visit reports
        $visitReports = VisitReport::with('visit')->get();
        if (!$visitReports->isEmpty()) {
            foreach ($visitReports as $report) {
                // MANDATORY: 1 facade + 1 shelves
                $this->createPhoto($report, 'facade', $placeholders['facade']);
                $this->createPhoto($report, 'shelves', $placeholders['shelves']);
                $createdCount += 2;

                // OPTIONAL: 60% chance of additional photos
                if (rand(1, 100) <= 60) {
                    $additionalTypes = ['stock', 'anomaly', 'other'];
                    $numAdditional = rand(1, 3);

                    for ($i = 0; $i < $numAdditional; $i++) {
                        $type = $additionalTypes[array_rand($additionalTypes)];
                        $this->createPhoto($report, $type, $placeholders[$type]);
                        $createdCount++;
                    }
                }
            }
            $this->command->info("Created photos for {$visitReports->count()} visit reports");
        }

        // Create photos for visit alerts (anomaly photos to document the alert)
        $visitAlerts = VisitAlert::with('visit')->get();
        if (!$visitAlerts->isEmpty()) {
            $alertPhotoCount = 0;
            foreach ($visitAlerts as $alert) {
                // 70% of alerts have photos
                if (rand(1, 100) <= 70) {
                    // Alerts typically have 1-2 anomaly/other photos
                    $numPhotos = rand(1, 2);
                    for ($i = 0; $i < $numPhotos; $i++) {
                        $type = rand(0, 1) ? 'anomaly' : 'other';
                        $this->createPhoto($alert, $type, $placeholders[$type]);
                        $alertPhotoCount++;
                        $createdCount++;
                    }
                }
            }
            $this->command->info("Created {$alertPhotoCount} photos for {$visitAlerts->count()} visit alerts");
        }

        $this->command->info("Total: Created {$createdCount} visit photos");
    }

    private function createPhoto($photoable, $type, $fileName)
    {
        // GPS near the photoable location with small variation
        $latitude = $photoable->latitude + (rand(-100, 100) / 100000);
        $longitude = $photoable->longitude + (rand(-100, 100) / 100000);

        // Determine taken_at based on photoable type
        if ($photoable instanceof \App\Models\VisitReport) {
            $takenAt = $photoable->visit->ended_at
                ? $photoable->visit->ended_at->subMinutes(rand(5, 30))
                : now();
        } elseif ($photoable instanceof \App\Models\VisitAlert) {
            $takenAt = $photoable->alerted_at
                ? $photoable->alerted_at->addMinutes(rand(1, 5))
                : now();
        } else {
            $takenAt = now();
        }

        VisitPhoto::create([
            'visit_id' => $photoable->visit_id,
            'photoable_type' => get_class($photoable),
            'photoable_id' => $photoable->id,
            'file_path' => 'visit_photos/' . $fileName,
            'file_name' => $fileName,
            'mime_type' => 'image/png',
            'file_size' => 100, // Small placeholder
            'type' => $type,
            'title' => $this->getPhotoTitle($type),
            'description' => $this->getPhotoDescription($type),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'taken_at' => $takenAt,
        ]);
    }

    private function createPlaceholderImage($type, $rgb)
    {
        $fileName = $type . '_' . uniqid() . '.png';
        $filePath = storage_path('app/public/visit_photos/' . $fileName);

        // Create directory if it doesn't exist
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        // Create a simple colored 100x100 placeholder image
        $image = imagecreatetruecolor(100, 100);
        $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        imagefill($image, 0, 0, $color);

        // Add text label
        $white = imagecolorallocate($image, 255, 255, 255);
        $text = strtoupper(substr($type, 0, 3));
        imagestring($image, 5, 35, 45, $text, $white);

        imagepng($image, $filePath);
        imagedestroy($image);

        return $fileName;
    }

    private function getPhotoTitle($type)
    {
        $titles = [
            'facade' => [
                'Façade du magasin',
                'Devanture du point de vente',
                'Vue extérieure',
            ],
            'shelves' => [
                'Rayon produits',
                'Étagères merchandising',
                'Présentation linéaire',
            ],
            'stock' => [
                'Niveau de stock',
                'Inventaire visuel',
                'Stock en réserve',
            ],
            'anomaly' => [
                'Anomalie détectée',
                'Problème constaté',
                'Non-conformité',
            ],
            'other' => [
                'Photo complémentaire',
                'Vue générale',
                'Autre vue',
            ],
        ];

        return $titles[$type][array_rand($titles[$type])];
    }

    private function getPhotoDescription($type)
    {
        $descriptions = [
            'facade' => [
                'Photo de la façade du point de vente',
                'Vue d\'ensemble de la devanture',
                'Enseigne et vitrine du magasin',
            ],
            'shelves' => [
                'Photo des rayons avec nos produits',
                'Présentation des produits en linéaire',
                'Merchandising et facing des produits',
            ],
            'stock' => [
                'Niveau de stock observé lors de la visite',
                'Photo de l\'état du stock disponible',
                'Inventaire visuel des produits',
            ],
            'anomaly' => [
                'Anomalie relevée nécessitant attention',
                'Problème constaté à corriger',
                'Situation non conforme aux standards',
            ],
            'other' => [
                'Photo complémentaire de la visite',
                'Documentation additionnelle',
                'Autre élément à signaler',
            ],
        ];

        return $descriptions[$type][array_rand($descriptions[$type])];
    }
}
