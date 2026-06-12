<?php

namespace App\Providers;

use App\Models\Deal;
use App\Models\Segment;
use App\Policies\DealPolicy;
use App\Policies\SegmentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Segment::class, SegmentPolicy::class);
        Gate::policy(Deal::class, DealPolicy::class);
    }
}