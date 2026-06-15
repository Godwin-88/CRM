<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpClientService
{
    protected const CIRCUIT_KEY_PREFIX = 'circuit_breaker:';

    protected const FAILURE_THRESHOLD = 5;

    protected const TIMEOUT_SECONDS = 30;

    public function get(string $url, array $options = []): array
    {
        return $this->request('GET', $url, $options);
    }

    public function post(string $url, array $data = [], array $options = []): array
    {
        return $this->request('POST', $url, array_merge($options, ['json' => $data]));
    }

    public function put(string $url, array $data = [], array $options = []): array
    {
        return $this->request('PUT', $url, array_merge($options, ['json' => $data]));
    }

    protected function request(string $method, string $url, array $options): array
    {
        $circuitKey = $this->getCircuitKey($url);

        if ($this->isCircuitOpen($circuitKey)) {
            throw new \RuntimeException('Circuit breaker open for this endpoint');
        }

        try {
            $options['timeout'] = $options['timeout'] ?? self::TIMEOUT_SECONDS;
            $json = $options['json'] ?? null;
            unset($options['json']);
            $response = Http::withOptions($options)->$method($url, $json);

            $this->resetCircuit($circuitKey);

            return [
                'status' => $response->status(),
                'body' => $response->json(),
            ];
        } catch (ConnectionException $e) {
            $this->recordFailure($circuitKey);
            Log::warning("Circuit breaker: connection failed for {$url}");

            throw $e;
        }
    }

    protected function getCircuitKey(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return self::CIRCUIT_KEY_PREFIX.$host;
    }

    protected function isCircuitOpen(string $key): bool
    {
        $failures = Cache::get($key, 0);

        return $failures >= self::FAILURE_THRESHOLD;
    }

    protected function recordFailure(string $key): void
    {
        $failures = Cache::get($key, 0);
        Cache::put($key, $failures + 1, 300);
    }

    protected function resetCircuit(string $key): void
    {
        Cache::put($key, 0, 300);
    }
}
