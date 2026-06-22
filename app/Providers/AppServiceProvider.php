<?php

namespace App\Providers;

use App\Events\AssistantLowConfidenceRouteDetected;
use App\Events\CaseClosed;
use App\Events\CaseCreated;
use App\Events\CaseEscalated;
use App\Events\CaseSignoffRequired;
use App\Events\CaseStatusChanged;
use App\Events\ServiceRequestClosed;
use App\Events\ServiceRequestCompleted;
use App\Events\ServiceRequestCreated;
use App\Events\ServiceRequestDocumentRequested;
use App\Events\ServiceRequestStatusChanged;
use App\Events\SlaBreachWarning;
use App\Events\TicketAssigned;
use App\Events\WebhookEventOccurred;
use App\Listeners\FlagAssistantLowConfidence;
use App\Listeners\QueueWebhookDeliveries;
use App\Listeners\PushAssistantProactiveSuggestion;
use App\Listeners\ServiceManagementNotificationListener;
use App\Models\Asset;
use App\Models\BankingRelationship;
use App\Models\CaseRecord;
use App\Models\Contract;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\FormSchema;
use App\Models\Invoice;
use App\Models\LegalMatter;
use App\Models\PurchaseOrder;
use App\Models\ReportDefinition;
use App\Models\Segment;
use App\Models\ServiceCatalogItem;
use App\Models\ServiceRequest;
use App\Models\Vendor;
use App\Models\Comment;
use App\Models\Team;
use App\Policies\AssetPolicy;
use App\Policies\BankingRelationshipPolicy;
use App\Policies\CaseRecordPolicy;
use App\Policies\ContractPolicy;
use App\Policies\DealPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\FormSchemaPolicy;
use App\Policies\IntegrationOAuthClientPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LegalMatterPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\ReportDefinitionPolicy;
use App\Policies\SegmentPolicy;
use App\Policies\ServiceCatalogItemPolicy;
use App\Policies\ServiceRequestPolicy;
use App\Policies\TeamPolicy;
use App\Policies\VendorPolicy;
use Aws\S3\S3Client;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Storage::extend('r2', function ($app, $config) {
            $client = new S3Client([
                'credentials' => [
                    'key' => $config['key'],
                    'secret' => $config['secret'],
                ],
                'region' => $config['region'],
                'version' => 'latest',
                'endpoint' => $config['endpoint'],
                'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? false,
            ]);

            $adapter = new AwsS3V3Adapter($client, $config['bucket']);

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config,
                $client
            );
        });
    }

    public function boot(): void
    {
        Gate::policy(ReportDefinition::class, ReportDefinitionPolicy::class);
        Gate::policy(Segment::class, SegmentPolicy::class);
        Gate::policy(Deal::class, DealPolicy::class);
        Gate::policy(Contract::class, ContractPolicy::class);
        Gate::policy(LegalMatter::class, LegalMatterPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Vendor::class, VendorPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(BankingRelationship::class, BankingRelationshipPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(IntegrationOAuthClient::class, IntegrationOAuthClientPolicy::class);
        Gate::policy(ServiceCatalogItem::class, ServiceCatalogItemPolicy::class);
        Gate::policy(ServiceRequest::class, ServiceRequestPolicy::class);
        Gate::policy(CaseRecord::class, CaseRecordPolicy::class);
        Gate::policy(FormSchema::class, FormSchemaPolicy::class);

        \Event::listen(ServiceRequestCreated::class, ServiceManagementNotificationListener::class);
        \Event::listen(ServiceRequestStatusChanged::class, ServiceManagementNotificationListener::class);
        \Event::listen(ServiceRequestCompleted::class, ServiceManagementNotificationListener::class);
        \Event::listen(ServiceRequestClosed::class, ServiceManagementNotificationListener::class);
        \Event::listen(ServiceRequestDocumentRequested::class, ServiceManagementNotificationListener::class);
        \Event::listen(CaseCreated::class, ServiceManagementNotificationListener::class);
        \Event::listen(CaseStatusChanged::class, ServiceManagementNotificationListener::class);
        \Event::listen(CaseEscalated::class, ServiceManagementNotificationListener::class);
        \Event::listen(CaseSignoffRequired::class, ServiceManagementNotificationListener::class);
        \Event::listen(CaseClosed::class, ServiceManagementNotificationListener::class);

        \Event::listen(TicketAssigned::class, PushAssistantProactiveSuggestion::class);
        \Event::listen(SlaBreachWarning::class, PushAssistantProactiveSuggestion::class);
        \Event::listen(AssistantLowConfidenceRouteDetected::class, FlagAssistantLowConfidence::class);
        \Event::listen(WebhookEventOccurred::class, QueueWebhookDeliveries::class);
    }
}
