<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClusterExperimentService
{
    protected string $fastApiUrl;

    public function __construct()
    {
        $this->fastApiUrl = rtrim(
            config('services.fastapi.base_url', 'http://127.0.0.1:8001'),
            '/'
        );
    }

    /**
     * Menjalankan eksperimen K-Means.
     *
     * Eksperimen TIDAK mengubah kmeans.pkl.
     */
    public function experiment(
        array $rfmData,
        int $kMin = 2,
        int $kMax = 10
    ): array {

        $response = Http::timeout(120)
            ->post(
                $this->fastApiUrl . '/experiment-kmeans',
                [
                    'customers' => $rfmData,
                    'k_min' => $kMin,
                    'k_max' => $kMax,
                ]
            );

        if ($response->failed()) {

            throw new RuntimeException(
                'FastAPI experiment gagal: ' .
                $response->body()
            );
        }

        return $response->json();
    }
}
