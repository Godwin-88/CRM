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
            'contacts.view',
            'contacts.create',
            'contacts.edit',
            'contacts.delete',
            'contacts.export',

            // Accounts
            'accounts.view',
            'accounts.create',
            'accounts.edit',
            'accounts.delete',

            // Deals
            'deals.view',
            'deals.create',
            'deals.edit',
            'deals.delete',

            // Quotes
            'quotes.view',
            'quotes.create',

            // Pipelines
            'pipelines.manage',

            // Win/Loss Reasons
            'win_loss_reasons.manage',

            // Quote Templates
            'quote_templates.manage',

            // Scoring
            'scoring_rules.manage',

            // Segments
            'segments.view',
            'segments.manage',

            // Custom Fields
            'custom_fields.manage',

            // Import/Export
            'contacts.import',

            // Contracts
            'contracts.view',
            'contracts.create',
            'contracts.edit',
            'contracts.delete',
            'contracts.sign',
            'contract_templates.manage',

            // Legal
            'legal_matters.view',
            'legal_matters.create',
            'legal_matters.edit',
            'legal_matters.delete',
            'legal.manage',

            // Opportunities
            'opportunities.view',
            'opportunities.create',
            'opportunities.edit',
            'opportunities.delete',

            // Invoices
            'invoices.view',
            'invoices.manage',
            'invoices.payments',

            // Procurement
            'procurement.create',
            'procurement.approve',

            // Vendors
            'vendors.view',
            'vendors.manage',
            'vendors.financials',

            // Assets
            'assets.view',
            'assets.manage',

            // HR
            'hr.view',
            'hr.manage',
            'hr.documents',

            // Banking
            'banking.view',
            'banking.manage',
            'banking.financials',
            'banking.documents',

            // Finance Analytics
            'analytics.finance',

            // Loyalty
            'loyalty.adjust',

            // Data Classification
            'data.view_pii',
            'data.view_financial',
            'data.view_confidential',

            // DSR
            'dsr.manage',

            // Compliance
            'compliance.field_audit',

            // Security
            'security.events',

            // API Client
            'api_client',

            // Integrations
            'integrations.manage',

            // Teams
            'teams.manage',

// Comments
             'comments.view',
             'attachments.sign',
             'docs.manage',
             ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ─── Roles ────────────────────────────────────────────────
        // Admin — all permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // Manager — most permissions except scoring/custom fields management
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete',
            'accounts.view', 'accounts.create', 'accounts.edit', 'accounts.delete',
            'deals.view', 'deals.create', 'deals.edit', 'deals.delete',
            'quotes.view', 'quotes.create',
            'segments.view', 'segments.manage',
            'contacts.import', 'contacts.export',
            'pipelines.manage',
            'win_loss_reasons.manage',
            'quote_templates.manage',
            'scoring_rules.manage',
            'contracts.view', 'contracts.create', 'contracts.edit',
            'contract_templates.manage',
            'legal_matters.view', 'legal_matters.create', 'legal_matters.edit',
            // Finance
            'invoices.view', 'invoices.manage', 'invoices.payments',
            'procurement.create', 'procurement.approve',
            'vendors.view', 'vendors.manage',
            'analytics.finance',
            'loyalty.adjust',
        ]);

        // Finance Manager — all finance and procurement permissions
        $financeManager = Role::firstOrCreate(['name' => 'finance-manager']);
        $financeManager->syncPermissions([
            'invoices.view', 'invoices.manage', 'invoices.payments',
            'procurement.create', 'procurement.approve',
            'vendors.view', 'vendors.manage', 'vendors.financials',
            'banking.view', 'banking.financials', 'banking.documents',
            'analytics.finance',
            'banking.manage',
        ]);

        // Operations Manager — assets and HR view
        $operationsManager = Role::firstOrCreate(['name' => 'operations-manager']);
        $operationsManager->syncPermissions([
            'assets.view', 'assets.manage',
            'hr.view', 'hr.manage',
            'hr.documents',
        ]);

        // Agent — create and edit, but not delete
        $agent = Role::firstOrCreate(['name' => 'agent']);
        $agent->syncPermissions([
            'contacts.view', 'contacts.create', 'contacts.edit',
            'accounts.view', 'accounts.create', 'accounts.edit',
            'deals.view', 'deals.create', 'deals.edit',
            'quotes.view', 'quotes.create',
            'segments.view',
            'contacts.export',
            'pipelines.manage',
        ]);

        // Read-only — can only view
        $readOnly = Role::firstOrCreate(['name' => 'read-only']);
        $readOnly->syncPermissions([
            'contacts.view',
            'accounts.view',
            'segments.view',
        ]);

        // Documentation
        $admin->givePermissionTo('docs.manage');

        // Assign admin role to the first user (if exists)
        if ($user = User::first()) {
            $user->assignRole('admin');
        }
    }
}
