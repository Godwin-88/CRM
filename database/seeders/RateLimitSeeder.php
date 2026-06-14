<?php

namespace Database\Seeders;

use App\Models\RateLimit;
use Illuminate\Database\Seeder;

class RateLimitSeeder extends Seeder
{
    public function run(): void
    {
        $limits = [
            ['key' => 'auth', 'max_requests' => 5, 'window_seconds' => 60],
            ['key' => 'api', 'max_requests' => 60, 'window_seconds' => 60],
            ['key' => 'bulk', 'max_requests' => 10, 'window_seconds' => 60],
            ['key' => 'kiosk', 'max_requests' => 500, 'window_seconds' => 60],
            ['key' => 'ivr', 'max_requests' => 200, 'window_seconds' => 60],
        ];

        foreach ($limits as $limit) {
            RateLimit::create($limit);
        }
    }
}
