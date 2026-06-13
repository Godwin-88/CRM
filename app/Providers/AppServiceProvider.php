<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\BankingRelationship;
use App\Models\Contract;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\LegalMatter;
use App\Models\PurchaseOrder;
use App\Models\Segment;
use App\Models\Vendor;
use App\Policies\AssetPolicy;
use App\Policies\BankingRelationshipPolicy;
use App\Policies\ContractPolicy;
use App\Policies\DealPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LegalMatterPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\SegmentPolicy;
use App\Policies\VendorPolicy;
use Aws\S3\S3Client;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;

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
    }
}
