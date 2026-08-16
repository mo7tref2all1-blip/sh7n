<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/** Roles + permissions mirroring docs/04-Permissions-Matrix.md. */
class RolePermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'users.manage', 'roles.manage',
        'branches.manage', 'branches.view', 'branches.transfer',
        'merchants.manage', 'merchants.view',
        'shipments.create', 'shipments.bulk_import', 'shipments.update', 'shipments.delete',
        'shipments.change_status', 'shipments.view_all', 'shipments.view_own',
        'custody.transfer', 'custody.view',
        'pod.record', 'pod.configure',
        'collection.record', 'collection.adjust_price',
        'cash.view_own', 'cash.view_all', 'cash.handover', 'cash.confirm_handover',
        'wallet.view_own', 'wallet.view_all', 'wallet.manual_adjust',
        'pricing.manage_general', 'pricing.manage_merchant',
        'settlements.create', 'settlements.approve', 'settlements.upload_proof', 'settlements.view',
        'agents.manage', 'agents.manage_drivers',
        'tickets.create', 'tickets.assign', 'tickets.close',
        'reports.view_all', 'reports.view_branch', 'reports.view_merchant',
        'settings.manage', 'integrations.manage',
        'audit.view_all', 'audit.view_financial',
        'system.update', // the ZIP self-update module — super_admin only, docs discussion.
    ];

    /** @var array<string, string[]> */
    private const ROLE_PERMISSIONS = [
        User::TYPE_SUPER_ADMIN => ['*'],
        User::TYPE_COMPANY_OWNER => ['*'],
        User::TYPE_OPS_MANAGER => [
            'branches.manage', 'branches.view', 'branches.transfer',
            'merchants.manage', 'merchants.view',
            'shipments.create', 'shipments.bulk_import', 'shipments.update', 'shipments.change_status',
            'shipments.view_all', 'custody.transfer', 'custody.view', 'pod.record', 'pod.configure',
            'collection.record', 'collection.adjust_price', 'cash.view_all', 'cash.handover', 'cash.confirm_handover',
            'pricing.manage_general', 'pricing.manage_merchant', 'agents.manage', 'agents.manage_drivers',
            'tickets.create', 'tickets.assign', 'tickets.close', 'reports.view_all',
        ],
        User::TYPE_BRANCH_MANAGER => [
            'branches.view', 'branches.transfer', 'users.manage',
            'shipments.create', 'shipments.update', 'shipments.change_status', 'shipments.view_own',
            'custody.transfer', 'custody.view', 'pod.record', 'collection.record', 'collection.adjust_price',
            'cash.view_own', 'cash.handover', 'cash.confirm_handover', 'reports.view_branch',
        ],
        User::TYPE_CUSTOMER_SERVICE => [
            'shipments.create', 'shipments.view_all', 'tickets.create', 'tickets.assign', 'tickets.close',
            'reports.view_branch',
        ],
        User::TYPE_ACCOUNTANT => [
            'cash.view_all', 'cash.confirm_handover', 'wallet.view_all', 'wallet.manual_adjust',
            'settlements.create', 'settlements.approve', 'settlements.upload_proof', 'settlements.view',
            'reports.view_all', 'audit.view_financial',
        ],
        User::TYPE_MERCHANT => [
            'shipments.create', 'shipments.bulk_import', 'shipments.view_own',
            'wallet.view_own', 'settlements.view', 'reports.view_merchant',
            'users.manage', // manages their own merchant_employee sub-accounts
        ],
        User::TYPE_MERCHANT_EMPLOYEE => [
            'shipments.create', 'shipments.bulk_import', 'shipments.view_own',
        ],
        User::TYPE_AGENT => [
            'shipments.view_own', 'shipments.change_status', 'custody.transfer', 'custody.view',
            'pod.record', 'collection.record', 'cash.view_own', 'cash.handover',
            'agents.manage_drivers', 'wallet.view_own',
        ],
        User::TYPE_DRIVER => [
            'shipments.view_own', 'shipments.change_status', 'custody.transfer',
            'pod.record', 'collection.record', 'cash.view_own', 'cash.handover',
        ],
        User::TYPE_WAREHOUSE_EMPLOYEE => [
            'branches.view', 'custody.transfer', 'custody.view', 'shipments.view_own',
        ],
    ];

    public function run(): void
    {
        Cache::forget('spatie.permission.cache');

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            $role->syncPermissions($permissions === ['*'] ? self::PERMISSIONS : $permissions);
        }
    }
}
