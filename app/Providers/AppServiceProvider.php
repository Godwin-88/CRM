<?php

namespace App\Providers;

use App\Models\Contract;
use App\Models\Deal;
use App\Models\LegalMatter;
use App\Models\Segment;
use App\Policies\ContractPolicy;
use App\Policies\DealPolicy;
use App\Policies\LegalMatterPolicy;
use App\Policies\SegmentPolicy;
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
    }
}
