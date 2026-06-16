<?php

namespace Database\Seeders;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DocsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            return;
        }

// Create categories for each spec section
        $categories = [
            'getting-started' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'getting-started'],
                [
                    'name' => 'Getting Started',
                    'description' => 'Onboarding and initial setup guides',
                ]
            ),
            'contacts-accounts' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'contacts-accounts'],
                [
                    'name' => 'Contacts & Accounts',
                    'description' => 'Contact and account management documentation',
                ]
            ),
            'deals-pipelines' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'deals-pipelines'],
                [
                    'name' => 'Deals & Pipelines',
                    'description' => 'Deal management and pipeline workflows',
                ]
            ),
            'omnichannel' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'omnichannel'],
                [
                    'name' => 'Omni-Channel',
                    'description' => 'Multi-channel interaction management',
                ]
            ),
            'campaigns' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'campaigns'],
                [
                    'name' => 'Campaigns',
                    'description' => 'Marketing campaigns and templates',
                ]
            ),
            'loyalty' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'loyalty'],
                [
                    'name' => 'Loyalty & CX',
                    'description' => 'Loyalty programs and customer experience',
                ]
            ),
            'support' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'support'],
                [
                    'name' => 'Support',
                    'description' => 'Ticket management and knowledge base',
                ]
            ),
            'analytics' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'analytics'],
                [
                    'name' => 'Analytics',
                    'description' => 'Reporting and analytics dashboards',
                ]
            ),
            'contracts-legal' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'contracts-legal'],
                [
                    'name' => 'Contracts & Legal',
                    'description' => 'Contract and legal matter management',
                ]
            ),
            'finance' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'finance'],
                [
                    'name' => 'Finance & Procurement',
                    'description' => 'Billing, invoices, and vendor management',
                ]
            ),
            'security' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'security'],
                [
                    'name' => 'Security',
                    'description' => 'Security, compliance, and access control',
                ]
            ),
            'integrations' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'integrations'],
                [
                    'name' => 'Integrations',
                    'description' => 'API tokens, webhooks, and third-party integrations',
                ]
            ),
            'calendar-notifications' => KnowledgeBaseCategory::firstOrCreate(
                ['slug' => 'calendar-notifications'],
                [
                    'name' => 'Calendar & Notifications',
                    'description' => 'Calendar, notifications, and collaboration tools',
                ]
            ),
        ];

        // Helper to create articles
        $createArticle = function (string $title, string $categoryKey, string $audience, array $featureRefs, string $body) use ($user, $categories) {
            $slug = Str::slug($title);
            KnowledgeBaseArticle::firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'body' => $body,
                    'category_id' => $categories[$categoryKey]->id,
                    'author_id' => $user->id,
                    'status' => 'published',
                    'audience' => $audience,
                    'feature_refs' => $featureRefs,
                    'published_at' => now(),
                ]
            );
        };

        // 4.1 Contact Management
        $createArticle(
            'Contact Management',
            'contacts-accounts',
            'agent',
            ['4.1.1'],
            '<div><h2>Contact Management Overview</h2><p>This guide covers how to view, search, and manage contacts in the CRM.</p><h3>Viewing Contacts</h3><ol><li>Navigate to the Contacts page from the sidebar.</li><li>Use the search bar to filter by name, email, or company.</li><li>Click on any contact to view their full profile.</li></ol><h3>Key Actions</h3><ul><li>Edit contact details</li><li>Link to accounts</li><li>View timeline and interactions</li></ul></div>'
        );

        $createArticle(
            'Account Management',
            'contacts-accounts',
            'manager',
            ['4.1.2'],
            '<div><h2>Account Management Guide</h2><p>Learn how to manage company accounts and track business relationships.</p><h3>Creating Accounts</h3><ol><li>Go to Accounts from the sidebar menu.</li><li>Click "New Account" and fill in company details.</li><li>Assign an account manager from your team.</li></ol><h3>Linking Contacts</h3><p>Contacts can be linked to accounts to establish the relationship hierarchy.</p></div>'
        );

        $createArticle(
            'Merge Contacts',
            'contacts-accounts',
            'manager',
            ['4.1.3'],
            '<div><h2>Merging Duplicate Contacts</h2><p>When duplicate contacts are detected, use the merge feature to consolidate records.</p><h3>Merge Process</h3><ol><li>Navigate to Admin > Duplicate Merge.</li><li>Select the primary contact to keep.</li><li>Choose which fields to include from the secondary record.</li><li>Confirm the merge to combine records.</li></ol><p><strong>Note:</strong> This action cannot be undone.</p></div>'
        );

        $createArticle(
            'Bulk Import Export',
            'contacts-accounts',
            'admin',
            ['4.1.7'],
            '<div><h2>Bulk Import and Export</h2><p>Efficiently manage large contact and account datasets.</p><h3>Importing</h3><ol><li>Prepare a CSV file with required columns.</li><li>Download the template from Contacts page.</li><li>Use the import wizard to map columns.</li><li>Review and confirm the import.</li></ol><h3>Exporting</h3><p>Select records and choose export format (CSV, Excel).</p></div>'
        );

        $createArticle(
            'Scoring Rules',
            'contacts-accounts',
            'admin',
            ['4.1.8'],
            '<div><h2>Lead Scoring Configuration</h2><p>Configure rules to automatically score and rank leads based on behavior and demographics.</p><h3>Setting Up Rules</h3><ol><li>Navigate to Admin > Scoring Rules.</li><li>Create new rules with conditions and point values.</li><li>Arrange rules by priority order.</li><li>Activate the rule set.</li></ol></div>'
        );

        // 4.2 Deals & Pipelines
        $createArticle(
            'Deal Management',
            'deals-pipelines',
            'agent',
            ['4.2.1'],
            '<div><h2>Managing Deals</h2><p>Create and track sales opportunities through the deal pipeline.</p><h3>Creating a Deal</h3><ol><li>From a contact or deal page, click "Create Deal".</li><li>Select the pipeline and stage.</li><li>Enter deal value and expected close date.</li><li>Assign to an owner.</li></ol></div>'
        );

        $createArticle(
            'Pipeline Kanban Board',
            'deals-pipelines',
            'agent',
            ['4.2.2'],
            '<div><h2>Kanban Board View</h2><p>Visualize your sales pipeline with drag-and-drop functionality.</p><h3>Using the Kanban Board</h3><ol><li>Navigate to Deals > Kanban Board.</li><li>Drag cards between columns to update stages.</li><li>Click cards to view deal details.</li><li>Use filters to focus on specific pipelines.</li></ol></div>'
        );

        $createArticle(
            'Deal Automations',
            'deals-pipelines',
            'manager',
            ['4.2.3'],
            '<div><h2>Deal Automation Workflows</h2><p>Automate repetitive deal-related tasks and notifications.</p><h3>Configuration</h3><ol><li>Go to Admin > Deal Automations.</li><li>Create workflows based on deal stage changes.</li><li>Set up email notifications and task assignments.</li><li>Test and activate workflows.</li></ol></div>'
        );

        $createArticle(
            'Win Loss Reasons',
            'deals-pipelines',
            'manager',
            ['4.2.4'],
            '<div><h2>Win/Loss Reason Tracking</h2><p>Configure and track reasons for won and lost deals to improve forecasting.</p><h3>Setup</h3><ol><li>Navigate to Admin > Win/Loss Reasons.</li><li>Define reasons for wins and losses.</li><li>Apply reasons when closing deals.</li><li>Review analytics in the dashboard.</li></ol></div>'
        );

        // 4.3 Omni-Channel
        $createArticle(
            'Omni-Channel Dashboard',
            'omnichannel',
            'agent',
            ['4.3.1'],
            '<div><h2>Omni-Channel Dashboard</h2><p>Unified view of all multi-channel interactions.</p><h3>Overview</h3><p>The dashboard shows channels status, queue stats, and pending interactions.</p><h3>Channels Monitored</h3><ul><li>Email</li><li>SMS</li><li>Chat</li><li>Social Media</li></ul></div>'
        );

        $createArticle(
            'Interaction Inbox',
            'omnichannel',
            'agent',
            ['4.3.2'],
            '<div><h2>Interaction Inbox Management</h2><p>Manage and respond to all incoming interactions from multiple channels.</p><h3>Features</h3><ul><li>Unified inbox for all channels</li><li>Filter by status and channel type</li><li>Bulk actions for efficiency</li><li>Quick reply templates</li></ul></div>'
        );

        $createArticle(
            'Channels Configuration',
            'omnichannel',
            'admin',
            ['4.3.3'],
            '<div><h2>Channel Configuration</h2><p>Configure external channels for omni-channel routing.</p><h3>Setup Process</h3><ol><li>Go to Admin > Integrations.</li><li>Enable desired channels (email, SMS, chat).</li><li>Configure webhook endpoints and credentials.</li><li>Test connectivity and save settings.</li></ol></div>'
        );

        $createArticle(
            'Kiosk',
            'omnichannel',
            'agent',
            ['4.3.6'],
            '<div><h2>Kiosk Management</h2><p>Manage customer self-service kiosk interactions.</p><h3>Features</h3><p>The kiosk page allows you to view and manage kiosk sessions, track customer check-ins, and monitor queue status.</p></div>'
        );

        $createArticle(
            'Email Composer',
            'omnichannel',
            'agent',
            ['4.3.7'],
            '<div><h2>Email Composer Guide</h2><p>Compose and send emails directly from the CRM.</p><h3>Using the Composer</h3><ol><li>Navigate to Admin > Email Compose.</li><li>Enter recipient email addresses.</li><li>Compose subject and body.</li><li>Use templates or variables for personalization.</li></ol></div>'
        );

        $createArticle(
            'Call Logging',
            'omnichannel',
            'agent',
            ['4.3.8'],
            '<div><h2>Call Logging System</h2><p>Log and track customer phone calls within the CRM.</p><h3>Logging Calls</h3><ol><li>Go to Admin > Call Log.</li><li>Select the contact or create a new record.</li><li>Enter call details including duration and notes.</li><li>Set follow-up reminders if needed.</li></ol></div>'
        );

        // 4.4 Campaigns
        $createArticle(
            'Campaigns',
            'campaigns',
            'manager',
            ['4.4.1'],
            '<div><h2>Marketing Campaigns</h2><p>Create and manage multi-channel marketing campaigns.</p><h3>Campaign Creation</h3><ol><li>Navigate to Admin > Campaigns.</li><li>Click "New Campaign" and select type.</li><li>Configure schedule and target audience.</li><li>Launch and monitor performance.</li></ol></div>'
        );

        $createArticle(
            'Campaign Templates',
            'campaigns',
            'manager',
            ['4.4.2'],
            '<div><h2>Campaign Templates</h2><p>Create reusable templates for marketing campaigns.</p><h3>Managing Templates</h3><ol><li>Go to Admin > Campaign Templates.</li><li>Create templates with predefined content and scheduling.</li><li>Templates can be used when creating new campaigns.</li><li>Edit or duplicate existing templates.</li></ol></div>'
        );

        $createArticle(
            'Email Template Editor',
            'campaigns',
            'manager',
            ['4.4.3'],
            '<div><h2>Email Template Editor</h2><p>Design and edit email templates for campaigns.</p><h3>Editor Features</h3><ul><li>Drag-and-drop content blocks</li><li>Personalization variables</li><li>Preview across devices</li><li>Template versioning</li></ul></div>'
        );

        // 4.5 Loyalty & CX
        $createArticle(
            'Loyalty Program',
            'loyalty',
            'admin',
            ['4.5.1'],
            '<div><h2>Loyalty Program Management</h2><p>Configure and manage customer loyalty programs.</p><h3>Program Setup</h3><ol><li>Go to Admin > Loyalty Program.</li><li>Create earning rules based on purchases or interactions.</li><li>Define tier thresholds and benefits.</li><li>Set up redemption options.</li></ol></div>'
        );

        $createArticle(
            'Points Ledger',
            'loyalty',
            'agent',
            ['4.5.2'],
            '<div><h2>Points Ledger</h2><p>View and manage customer loyalty point transactions.</p><h3>Viewing Transactions</h3><ol><li>Navigate to Admin > Loyalty > Points Ledger.</li><li>Filter by customer or date range.</li><li>View earning and redemption history.</li><li>Export transaction records if needed.</li></ol></div>'
        );

        $createArticle(
            'Surveys',
            'loyalty',
            'manager',
            ['4.5.4'],
            '<div><h2>Survey Management</h2><p>Create and manage customer satisfaction surveys.</p><h3>Creating Surveys</h3><ol><li>Go to Admin > Surveys.</li><li>Select question types and create survey flow.</li><li>Configure delivery triggers and schedules.</li><li>Monitor responses and analyze results.</li></ol></div>'
        );

        $createArticle(
            'Survey Responses',
            'loyalty',
            'agent',
            ['4.5.5'],
            '<div><h2>Survey Responses</h2><p>View and analyze customer survey responses.</p><h3>Response Analysis</h3><ol><li>Navigate to Admin > Surveys > Responses.</li><li>View individual response details.</li><li>Filter by survey, date, or score.</li><li>Export data for deeper analysis.</li></ol></div>'
        );

        $createArticle(
            'Kiosk Interactions',
            'loyalty',
            'agent',
            ['4.5.6'],
            '<div><h2>Kiosk Interactions</h2><p>Track and analyze customer kiosk session data.</p><h3>Interaction Tracking</h3><ol><li>Access via Admin > Loyalty or Omni-Channel sections.</li><li>View session history and duration.</li><li>Monitor common actions and drop-off points.</li><li>Use data to improve kiosk experience.</li></ol></div>'
        );

        // 4.6 Support
        $createArticle(
            'Ticket Management',
            'support',
            'agent',
            ['4.6.1'],
            '<div><h2>Support Ticket Management</h2><p>Efficiently manage customer support tickets.</p><h3>Key Actions</h3><ul><li>Create and assign tickets</li><li>Update status and priority</li><li>Add internal notes and customer replies</li><li>Link relevant knowledge base articles</li></ul></div>'
        );

        $createArticle(
            'Knowledge Base',
            'support',
            'all',
            ['4.6.2'],
            '<div><h2>Knowledge Base Management</h2><p>Search and access the knowledge base for customer support answers.</p><h3>Using Knowledge Base</h3><ol><li>Browse categories or search for articles.</li><li>View article content and rate helpfulness.</li><li>Link articles to tickets for customer reference.</li></ol></div>'
        );

        $createArticle(
            'Canned Responses',
            'support',
            'agent',
            ['4.6.5'],
            '<div><h2>Canned Responses</h2><p>Use pre-written responses for common support queries.</p><h3>Using Canned Responses</h3><ol><li>Access from Admin > Support > Canned Responses.</li><li>Browse or search templates by category tag.</li><li>Insert into ticket replies with one click.</li><li>Mark favorites for quick access.</li></ol></div>'
        );

        $createArticle(
            'CSAT Ratings',
            'support',
            'manager',
            ['4.6.6'],
            '<div><h2>CSAT Rating Management</h2><p>Track customer satisfaction scores for support tickets.</p><h3>Review Process</h3><ol><li>View ratings in the Support Performance dashboard.</li><li>Analyze trends and identify improvement areas.</li><li>Follow up with dissatisfied customers.</li></ol></div>'
        );

        // 4.7 Analytics
        $createArticle(
            'Analytics Dashboard',
            'analytics',
            'manager',
            ['4.7.1'],
            '<div><h2>Analytics Dashboard</h2><p>Comprehensive analytics and reporting overview.</p><h3>Available Reports</h3><ul><li>Customer Analytics</li><li>Growth Analytics</li><li>Finance Analytics</li><li>Predictive Scoring</li></ul></div>'
        );

        $createArticle(
            'Customer Analytics',
            'analytics',
            'manager',
            ['4.7.2'],
            '<div><h2>Customer Analytics</h2><p>Deep insights into customer behavior and metrics.</p><h3>Metrics Available</h3><ul><li>Lifetime value trends</li><li>Engagement scores</li><li>Churn risk indicators</li><li>Retention analysis</li></ul></div>'
        );

        $createArticle(
            'Growth Analytics',
            'analytics',
            'manager',
            ['4.7.3'],
            '<div><h2>Growth Analytics</h2><p>Track business growth metrics and trends.</p><h3>Key Metrics</h3><ul><li>Revenue growth</li><li>Customer acquisition rate</li><li>Conversion funnel analysis</li><li>Market expansion tracking</li></ul></div>'
        );

        $createArticle(
            'Report Builder',
            'analytics',
            'manager',
            ['4.7.7'],
            '<div><h2>Report Builder</h2><p>Create custom reports with advanced filtering and visualization.</p><h3>Building Reports</h3><ol><li>Navigate to Analytics > Report Builder.</li><li>Select data sources and metrics.</li><li>Apply filters and groupings.</li><li>Save and schedule report delivery.</li></ol></div>'
        );

        $createArticle(
            'Churn Risk Analysis',
            'analytics',
            'manager',
            ['4.7.10'],
            '<div><h2>Churn Risk Analysis</h2><p>Identify customers at risk of churning with predictive analytics.</p><h3>Using Churn Risk</h3><ol><li>View Churn Risk dashboard.</li><li>Filter by risk score threshold.</li><li>Create reactivation campaigns for at-risk customers.</li><li>Track improvement over time.</li></ol></div>'
        );

        // 4.8 Contracts & Legal
        $createArticle(
            'Contract Management',
            'contracts-legal',
            'agent',
            ['4.8.1'],
            '<div><h2>Contract Management</h2><p>Manage customer contracts and legal documents.</p><h3>Key Features</h3><ul><li>View contract details and status</li><li>Track milestone dates</li><li>Send for signature</li><li>Download signed copies</li></ul></div>'
        );

        $createArticle(
            'Milestone Tracking',
            'contracts-legal',
            'manager',
            ['4.8.4'],
            '<div><h2>Contract Milestone Tracking</h2><p>Track important dates and obligations in contracts.</p><h3>Milestones</h3><p>Each contract can have multiple milestones with notifications for upcoming dates.</p></div>'
        );

        $createArticle(
            'E-Signature Workflow',
            'contracts-legal',
            'agent',
            ['4.8.6'],
            '<div><h2>E-Signature Integration</h2><p>Send contracts for electronic signature.</p><h3>Process</h3><ol><li>Open a contract and click "Send for Signature".</li><li>Configure signature fields and recipients.</li><li>Track signature status in real-time.</li><li>Download signed document upon completion.</li></ol></div>'
        );

        // 4.9 Finance & Procurement
        $createArticle(
            'Invoice Management',
            'finance',
            'agent',
            ['4.9.1'],
            '<div><h2>Invoice Management</h2><p>Create, view, and manage customer invoices.</p><h3>Invoice Actions</h3><ul><li>Create new invoices</li><li>Record payments</li><li>Send to customers</li><li>Track payment status</li></ul></div>'
        );

        $createArticle(
            'Payment Recording',
            'finance',
            'agent',
            ['4.9.2'],
            '<div><h2>Recording Payments</h2><p>Record customer payments against invoices.</p><h3>Payment Process</h3><ol><li>Open an invoice with outstanding balance.</li><li>Click "Record Payment".</li><li>Enter payment amount and method.</li><li>Save to update invoice status.</li></ol></div>'
        );

        $createArticle(
            'Ledger Summary',
            'finance',
            'manager',
            ['4.9.7'],
            '<div><h2>Ledger Summary</h2><p>View financial summary and account balances.</p><h3>Summary View</h3><p>The ledger provides an overview of accounts receivable, payable, and cash position.</p></div>'
        );

        // 4.10 Security
        $createArticle(
            'MFA Setup',
            'security',
            'all',
            ['4.10.1'],
            '<div><h2>Multi-Factor Authentication Setup</h2><p>Enable MFA for enhanced account security.</p><h3>Setup Process</h3><ol><li>Navigate to your profile settings.</li><li>Click "MFA Setup".</li><li>Scan QR code with authenticator app.</li><li>Enter verification code to confirm.</li></ol></div>'
        );

        $createArticle(
            'Security Events',
            'security',
            'admin',
            ['4.10.2'],
            '<div><h2>Security Events Monitoring</h2><p>Review and audit security-related events in the system.</p><h3>Event Types</h3><ul><li>Login attempts</li><li>Privileged session starts</li><li>Data export requests</li><li>Permission changes</li></ul></div>'
        );

        $createArticle(
            'RBAC Matrix',
            'security',
            'admin',
            ['4.10.4'],
            '<div><h2>RBAC Configuration</h2><p>Configure role-based access control for the platform.</p><h3>Managing Permissions</h3><ol><li>Navigate to Admin > RBAC.</li><li>Define roles and assign permissions.</li><li>Map roles to user groups.</li><li>Test access before deployment.</li></ol></div>'
        );

        // 4.11 Integrations
        $createArticle(
            'Integration Marketplace',
            'integrations',
            'admin',
            ['4.11.1'],
            '<div><h2>Integration Marketplace</h2><p>Browse and connect third-party integrations.</p><h3>Browsing Integrations</h3><ol><li>Navigate to Admin > Integrations > Marketplace.</li><li>Browse available integrations by category.</li><li>Click "Connect" and follow setup instructions.</li><li>Manage connected integrations from settings.</li></ol></div>'
        );

        $createArticle(
            'API Tokens',
            'integrations',
            'admin',
            ['4.11.2'],
            '<div><h2>API Token Management</h2><p>Create and manage API tokens for programmatic access.</p><h3>Token Lifecycle</h3><ol><li>Go to Admin > API Tokens.</li><li>Create tokens with appropriate scopes.</li><li>Copy and securely store token value.</li><li>Rotate tokens periodically.</li></ol></div>'
        );

        $createArticle(
            'Webhooks',
            'integrations',
            'admin',
            ['4.11.3'],
            '<div><h2>Webhook Configuration</h2><p>Set up webhook endpoints for real-time notifications.</p><h3>Setting Up Webhooks</h3><ol><li>Navigate to Admin > Integrations > Webhooks.</li><li>Create new webhook endpoint URL.</li><li>Select events to subscribe to.</li><li>Test and verify delivery.</li></ol></div>'
        );

        $createArticle(
            'Developer Documentation',
            'integrations',
            'admin',
            ['4.11.7'],
            '<div><h2>API Documentation</h2><p>Complete OpenAPI/Swagger documentation for the API.</p><h3>Access</h3><p>View the interactive API documentation at the developer portal.</p></div>'
        );

        // 4.12 Calendar & Notifications
        $createArticle(
            'Calendar',
            'calendar-notifications',
            'all',
            ['4.12.1'],
            '<div><h2>Calendar Management</h2><p>Schedule and track meetings, calls, and deadlines.</p><h3>Features</h3><ul><li>Monthly and weekly views</li><li>Event creation and editing</li><li>Invitations and reminders</li><li>Team calendar sharing</li></ul></div>'
        );

        $createArticle(
            'Notifications',
            'calendar-notifications',
            'all',
            ['4.12.2'],
            '<div><h2>Notifications Center</h2><p>View and manage system notifications.</p><h3>Notification Types</h3><ul><li>Ticket assignments</li><li>Deal updates</li><li>Calendar reminders</li><li>Mentions and comments</li></ul></div>'
        );

        // Getting Started (pinned)
        $createArticle(
            'Getting Started Guide',
            'getting-started',
            'all',
            [],
            '<div><h2>Welcome to the CRM Platform</h2><p>This guide will help you get started with the platform quickly.</p><h3>Quick Start</h3><ol><li>Complete your onboarding checklist.</li><li>Review your contacts and accounts.</li><li>Explore the deals pipeline.</li><li>Check your notification settings.</li></ol><h3>Need Help?</h3><p>Click the Help icon on any page for contextual guidance.</p></div>'
        );
    }
}