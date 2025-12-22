<?php

namespace Database\Seeders;

use App\Models\Visit;
use App\Models\VisitAlert;
use App\Models\VisitReport;
use Illuminate\Database\Seeder;

class VisitAlertSeeder extends Seeder
{
    public function run(): void
    {
        // Get visits with reports (alerts happen during visits)
        $visits = Visit::with(['report', 'client', 'user', 'baseCommerciale', 'zone'])->get();

        if ($visits->isEmpty()) {
            $this->command->warn('No visits found. Skipping visit alert seeder.');
            return;
        }

        $createdCount = 0;
        $alertTypes = [
            'rupture_grave',
            'litige_paiement',
            'probleme_rayon',
            'risque_perte_client',
            'demande_speciale',
            'nouvelle_opportunite',
            'autre'
        ];

        foreach ($visits as $visit) {
            // 30% chance a visit has an alert
            if (rand(1, 100) <= 30) {
                $type = $alertTypes[array_rand($alertTypes)];

                // GPS near the visit location
                $baseLatitude = 5.3599517; // Abidjan approximate
                $baseLongitude = -4.0082563;
                $latitude = $baseLatitude + (rand(-1000, 1000) / 10000);
                $longitude = $baseLongitude + (rand(-1000, 1000) / 10000);

                // Alerted during the visit
                $alertedAt = $visit->started_at
                    ? $visit->started_at->addMinutes(rand(5, 20))
                    : now();

                // Determine status (60% pending, 20% in_progress, 15% resolved, 5% closed)
                $statusRand = rand(1, 100);
                if ($statusRand <= 60) {
                    $status = 'pending';
                    $handledBy = null;
                    $handledAt = null;
                    $handlingComment = null;
                } elseif ($statusRand <= 80) {
                    $status = 'in_progress';
                    $handledBy = rand(1, 5); // One of the first 5 users (managers/admins)
                    $handledAt = $alertedAt->copy()->addHours(rand(1, 24));
                    $handlingComment = $this->getHandlingComment($status);
                } elseif ($statusRand <= 95) {
                    $status = 'resolved';
                    $handledBy = rand(1, 5);
                    $handledAt = $alertedAt->copy()->addHours(rand(24, 72));
                    $handlingComment = $this->getHandlingComment($status);
                } else {
                    $status = 'closed';
                    $handledBy = rand(1, 5);
                    $handledAt = $alertedAt->copy()->addDays(rand(3, 7));
                    $handlingComment = $this->getHandlingComment($status);
                }

                VisitAlert::create([
                    'visit_id' => $visit->id,
                    'visit_report_id' => $visit->report?->id,
                    'user_id' => $visit->user_id,
                    'client_id' => $visit->client_id,
                    'base_commerciale_id' => $visit->base_commerciale_id,
                    'zone_id' => $visit->zone_id,
                    'type' => $type,
                    'comment' => $this->getAlertComment($type),
                    'custom_type' => $type === 'autre' ? $this->getCustomType() : null,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'alerted_at' => $alertedAt,
                    'status' => $status,
                    'handled_by' => $handledBy,
                    'handled_at' => $handledAt,
                    'handling_comment' => $handlingComment,
                ]);

                $createdCount++;
            }
        }

        $this->command->info("Created {$createdCount} visit alerts for {$visits->count()} visits");
    }

    private function getAlertComment($type)
    {
        $comments = [
            'rupture_grave' => [
                'Rupture totale sur Riz 25kg depuis 3 jours. Client mécontent, risque de perdre le contrat.',
                'Stock épuisé sur toute la gamme boissons gazeuses. Concurrent en profite.',
                'Rupture critique sur huile alimentaire. Client menace de changer de fournisseur.',
            ],
            'litige_paiement' => [
                'Client refuse de payer la facture #12345, conteste la qualité des produits livrés.',
                'Retard de paiement de 60 jours. Client demande un échéancier.',
                'Litige sur montant facturé, écart de 150 000 XOF avec bon de livraison.',
            ],
            'probleme_rayon' => [
                'Nos produits mal positionnés, en bas de rayon. Concurrent a le meilleur emplacement.',
                'Rayon en désordre, produits périmés non retirés, mauvaise image de marque.',
                'Facing insuffisant, seulement 2 produits visibles alors que concurrent a 10.',
            ],
            'risque_perte_client' => [
                'Client très mécontent du service. Concurrent propose 15% de réduction.',
                'Gérant menace de résilier le contrat si pas d\'amélioration sous 15 jours.',
                'Client contacté par concurrent avec offre très agressive. Risque de basculement.',
            ],
            'demande_speciale' => [
                'Client demande nouveau produit lait concentré non au catalogue actuel.',
                'Demande livraison le samedi matin au lieu du jeudi. À valider avec logistique.',
                'Client souhaite packaging personnalisé pour son enseigne. Quantité minimale 500 unités.',
            ],
            'nouvelle_opportunite' => [
                'Nouveau supermarché ouvert à 500m, pas encore de fournisseur. Excellent potentiel.',
                'Client actuel C souhaite passer en B avec commande mensuelle x3.',
                'Gérant intéressé par gamme complète produits laitiers, opportunité 500K XOF/mois.',
            ],
            'autre' => [
                'Problème de qualité constaté sur lot #789, client garde produits en réserve.',
                'Camion de livraison en panne devant le magasin, besoin assistance urgente.',
                'Concurrent installe affichage publicitaire juste devant notre rayon.',
            ],
        ];

        return $comments[$type][array_rand($comments[$type])];
    }

    private function getCustomType()
    {
        $customTypes = [
            'Problème qualité produit',
            'Incident livraison',
            'Concurrence déloyale',
            'Demande formation équipe',
            'Suggestion amélioration',
        ];

        return $customTypes[array_rand($customTypes)];
    }

    private function getHandlingComment($status)
    {
        $comments = [
            'in_progress' => [
                'Alerte prise en compte. Investigation en cours avec le service concerné.',
                'Dossier transmis au responsable commercial. Rendez-vous prévu avec client.',
                'En cours de traitement. Attente retour du service logistique.',
            ],
            'resolved' => [
                'Problème résolu. Livraison exceptionnelle effectuée. Client satisfait.',
                'Situation régularisée après négociation avec client. Nouveau contrat signé.',
                'Alerte traitée. Mesures correctives mises en place. Suivi hebdomadaire prévu.',
            ],
            'closed' => [
                'Dossier clos après résolution complète. Client pleinement satisfait.',
                'Alerte close suite à accord commercial. Situation normalisée.',
                'Traitement terminé. Aucune action supplémentaire requise.',
            ],
        ];

        return $comments[$status][array_rand($comments[$status])];
    }
}
