<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FastApiMetricsService
{
    protected Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'base_uri' => rtrim(config('services.fastapi.base_url'), '/') . '/',
            'timeout'  => config('services.fastapi.timeout', 30),
        ]);
    }

    public function fetchMetrics(): ?array
    {
        $metrics = $this->fetchRemoteMetrics();

        if ($metrics !== null) {
            return $metrics;
        }

        return $this->loadLocalMetrics();
    }

    protected function fetchRemoteMetrics(): ?array
    {
        try {
            $response = $this->httpClient->get('model-metrics');
            $decoded = json_decode((string) $response->getBody(), true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            Log::error('FastAPI model metrics response invalid JSON.', [
                'body' => (string) $response->getBody(),
            ]);
        } catch (RequestException $e) {
            $detail = null;
            if ($e->hasResponse()) {
                $detail = json_decode((string) $e->getResponse()->getBody(), true);
            }

            $message = $e->getMessage();
            if (is_array($detail) && isset($detail['detail'])) {
                $message = $detail['detail'];
            }

            Log::error('Gagal menghubungi FastAPI metrics service: ' . $message);
        } catch (GuzzleException $e) {
            Log::error('Gagal menghubungi FastAPI metrics service: ' . $e->getMessage());
        }

        return null;
    }

    protected function loadLocalMetrics(): ?array
    {
        foreach (['model_metrics.json', 'private/model_metrics.json'] as $path) {
            if (Storage::exists($path)) {
                $decoded = json_decode(Storage::get($path), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return null;
    }
}
