<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Permissions ──────────────────────────────────────────
        $permissions = [
            // Contacts
            'view contacts',
            'create contacts',
            'edit contacts',
            'delete contacts',
            'contacts.export',

            // Accounts
            'view accounts',
            'create accounts',
            'edit accounts',
            'delete accounts',

            // Deals
            'view deals',
            'create deals',
            'edit deals',
            'delete deals',

            // Quotes
            'view quotes',
            'create quotes',

            // Pipelines
            'manage pipelines',

            // Win/Loss Reasons
            'manage win_loss_reasons',

            // Quote Templates
            'manage quote_templates',

            // Scoring
            'manage scoring rules',

            // Segments
            'view segments',
            'manage segments',

            // Custom Fields
            'manage custom fields',

            // Import/Export
            'contacts.import',

            // Contracts
            'view contracts',
            'create contracts',
            'edit contracts',
            'delete contracts',
            'manage contract_templates',
            'view legal_matters',
            'create legal_matters',
            'edit legal_matters',
            'delete legal_matters',

            // Opportunities
            'view opportunities',
            'create opportunities',
            'edit opportunities',
            'delete opportunities',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // ─── Roles ────────────────────────────────────────────────
        // Admin — all permissions
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Manager — most permissions except scoring/custom fields management
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view contacts', 'create contacts', 'edit contacts', 'delete contacts',
            'view accounts', 'create accounts', 'edit accounts', 'delete accounts',
            'view deals', 'create deals', 'edit deals', 'delete deals',
            'view quotes', 'create quotes',
            'view segments', 'manage segments',
            'contacts.import', 'contacts.export',
            'manage pipelines',
            'manage win_loss_reasons',
            'manage quote_templates',
            'manage scoring rules',
            'view contracts', 'create contracts', 'edit contracts',
            'manage contract_templates',
            'view legal_matters', 'create legal_matters', 'edit legal_matters',
        ]);

        // Agent — create and edit, but not delete
        $agent = Role::create(['name' => 'agent']);
        $agent->givePermissionTo([
            'view contacts', 'create contacts', 'edit contacts',
            'view accounts', 'create accounts', 'edit accounts',
            'view deals', 'create deals', 'edit deals',
            'view quotes', 'create quotes',
            'view segments',
            'contacts.export',
            'manage pipelines',
        ]);

        // Read-only — can only view
        $readOnly = Role::create(['name' => 'read-only']);
        $readOnly->givePermissionTo([
            'view contacts',
            'view accounts',
            'view segments',
        ]);

        // Assign admin role to the first user (if exists)
        if ($user = User::first()) {
            $user->assignRole('admin');
        }
    }
}
