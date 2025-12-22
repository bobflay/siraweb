<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Client permissions
            ['code' => 'client.create', 'name' => 'Créer un client', 'description' => 'Créer de nouveaux clients'],
            ['code' => 'client.update', 'name' => 'Modifier un client', 'description' => 'Modifier les informations clients'],
            ['code' => 'client.view', 'name' => 'Voir un client', 'description' => 'Voir les détails d\'un client'],
            ['code' => 'client.view_all', 'name' => 'Voir tous les clients', 'description' => 'Accéder à tous les clients du système'],

            // Visit permissions
            ['code' => 'visit.start', 'name' => 'Démarrer une visite', 'description' => 'Démarrer une visite client'],
            ['code' => 'visit.validate', 'name' => 'Valider une visite', 'description' => 'Valider et compléter une visite'],
            ['code' => 'visit.view_own', 'name' => 'Voir ses visites', 'description' => 'Voir ses propres visites'],
            ['code' => 'visit.view_all', 'name' => 'Voir toutes les visites', 'description' => 'Voir toutes les visites du système'],

            // Routing permissions
            ['code' => 'routing.view_own', 'name' => 'Voir sa tournée', 'description' => 'Voir sa propre tournée'],
            ['code' => 'routing.view_all', 'name' => 'Voir toutes les tournées', 'description' => 'Voir toutes les tournées'],
            ['code' => 'routing.generate', 'name' => 'Générer des tournées', 'description' => 'Générer et planifier des tournées'],
            ['code' => 'routing.override', 'name' => 'Modifier les tournées', 'description' => 'Modifier manuellement les tournées'],

            // Photo permissions
            ['code' => 'photo.capture', 'name' => 'Capturer des photos', 'description' => 'Prendre des photos lors des visites'],
            ['code' => 'photo.view', 'name' => 'Voir les photos', 'description' => 'Consulter les photos'],
            ['code' => 'photo.view_all', 'name' => 'Voir toutes les photos', 'description' => 'Accéder à toutes les photos du système'],

            // Alert permissions
            ['code' => 'alert.create', 'name' => 'Créer une alerte', 'description' => 'Créer des alertes terrain'],
            ['code' => 'alert.view', 'name' => 'Voir les alertes', 'description' => 'Consulter les alertes'],
            ['code' => 'alert.manage', 'name' => 'Gérer les alertes', 'description' => 'Gérer et traiter les alertes'],

            // Order permissions
            ['code' => 'order.create', 'name' => 'Créer une commande', 'description' => 'Créer de nouvelles commandes'],
            ['code' => 'order.view_own', 'name' => 'Voir ses commandes', 'description' => 'Voir ses propres commandes'],
            ['code' => 'order.view_all', 'name' => 'Voir toutes les commandes', 'description' => 'Voir toutes les commandes'],
            ['code' => 'order.update', 'name' => 'Modifier une commande', 'description' => 'Modifier les commandes'],
            ['code' => 'order.validate', 'name' => 'Valider une commande', 'description' => 'Valider les commandes'],
            ['code' => 'order.cancel', 'name' => 'Annuler une commande', 'description' => 'Annuler des commandes'],

            // Product permissions
            ['code' => 'product.view', 'name' => 'Voir les produits', 'description' => 'Consulter le catalogue produits'],
            ['code' => 'product.manage', 'name' => 'Gérer les produits', 'description' => 'Gérer le catalogue produits'],

            // Pricing permissions
            ['code' => 'pricing.manage', 'name' => 'Gérer les tarifs', 'description' => 'Gérer les tarifs et promotions'],

            // Dashboard permissions
            ['code' => 'dashboard.sales', 'name' => 'Tableau de bord commercial', 'description' => 'Accès au tableau de bord commercial'],
            ['code' => 'dashboard.finance', 'name' => 'Tableau de bord financier', 'description' => 'Accès au tableau de bord financier'],

            // Export permissions
            ['code' => 'export.data', 'name' => 'Exporter les données', 'description' => 'Exporter les données du système'],

            // User management permissions
            ['code' => 'user.manage', 'name' => 'Gérer les utilisateurs', 'description' => 'Gérer les utilisateurs et leurs rôles'],

            // System permissions
            ['code' => 'system.admin_pass', 'name' => 'Accès administration système', 'description' => 'Accès complet au système'],
            ['code' => 'system.log_view', 'name' => 'Voir les logs', 'description' => 'Consulter les logs système'],
        ];

        foreach ($permissions as &$permission) {
            $permission['created_at'] = now();
            $permission['updated_at'] = now();
        }

        DB::table('permissions')->insert($permissions);
    }
}
