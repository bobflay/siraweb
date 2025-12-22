<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionRoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $roleAgent = DB::table('roles')->where('code', 'ROLE_AGENT')->first();
        $roleBaseManager = DB::table('roles')->where('code', 'ROLE_BASE_MANAGER')->first();
        $roleCommercialAdmin = DB::table('roles')->where('code', 'ROLE_COMMERCIAL_ADMIN')->first();
        $roleFinance = DB::table('roles')->where('code', 'ROLE_FINANCE')->first();
        $roleSuperAdmin = DB::table('roles')->where('code', 'ROLE_SUPER_ADMIN')->first();

        // Get all permissions
        $permissions = DB::table('permissions')->get()->keyBy('code');

        // ROLE_AGENT - Field actions only
        $agentPermissions = [
            'client.create',
            'client.update',
            'client.view',
            'visit.start',
            'visit.validate',
            'visit.view_own',
            'routing.view_own',
            'photo.capture',
            'photo.view',
            'alert.create',
            'alert.view',
            'order.create',
            'order.view_own',
            'product.view',
        ];

        foreach ($agentPermissions as $permCode) {
            if (isset($permissions[$permCode])) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permissions[$permCode]->id,
                    'role_id' => $roleAgent->id,
                ]);
            }
        }

        // ROLE_BASE_MANAGER - Products, orders, dashboards
        $baseManagerPermissions = [
            'client.view',
            'client.view_all',
            'visit.view_all',
            'routing.view_all',
            'photo.view',
            'photo.view_all',
            'alert.view',
            'order.view_all',
            'order.update',
            'order.validate',
            'product.view',
            'product.manage',
            'dashboard.sales',
            'export.data',
        ];

        foreach ($baseManagerPermissions as $permCode) {
            if (isset($permissions[$permCode])) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permissions[$permCode]->id,
                    'role_id' => $roleBaseManager->id,
                ]);
            }
        }

        // ROLE_COMMERCIAL_ADMIN - Routing, alerts, full visibility
        $commercialAdminPermissions = [
            'client.create',
            'client.update',
            'client.view',
            'client.view_all',
            'visit.view_all',
            'visit.validate',
            'routing.view_all',
            'routing.generate',
            'routing.override',
            'photo.view_all',
            'alert.view',
            'alert.manage',
            'order.view_all',
            'order.update',
            'order.validate',
            'order.cancel',
            'product.view',
            'product.manage',
            'pricing.manage',
            'dashboard.sales',
            'export.data',
            'user.manage',
        ];

        foreach ($commercialAdminPermissions as $permCode) {
            if (isset($permissions[$permCode])) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permissions[$permCode]->id,
                    'role_id' => $roleCommercialAdmin->id,
                ]);
            }
        }

        // ROLE_FINANCE - Financial dashboards + orders read
        $financePermissions = [
            'client.view',
            'client.view_all',
            'order.view_all',
            'product.view',
            'dashboard.finance',
            'export.data',
        ];

        foreach ($financePermissions as $permCode) {
            if (isset($permissions[$permCode])) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permissions[$permCode]->id,
                    'role_id' => $roleFinance->id,
                ]);
            }
        }

        // ROLE_SUPER_ADMIN - ALL permissions
        foreach ($permissions as $permission) {
            DB::table('permission_role')->insert([
                'permission_id' => $permission->id,
                'role_id' => $roleSuperAdmin->id,
            ]);
        }
    }
}
