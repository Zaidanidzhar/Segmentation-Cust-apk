<?php
namespace App\Services;

use App\Repositories\Contracts\CustomerSegmentRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClusterPredictionService
{
    protected CustomerSegmentRepositoryInterface $segmentRepo;
    protected Client $httpClient;

    // Mapping label numerik dari K-Means ke nama segmen bisnis.
    // Urutan ini ditentukan berdasarkan karakteristik cluster (lihat catatan di bawah).
    protected array $segmentLabels = [
        0 => 'Champions',
        1 => 'Loyal Customers',
        2 => 'At Risk',
        3 => 'Hibernating',
    ];

    public function __construct(CustomerSegmentRepositoryInterface $segmentRepo)
    {
        $this->segmentRepo = $segmentRepo;

        $this->httpClient = new Client([
            'base_uri' => rtrim(config('services.fastapi.base_url'), '/') . '/',
            'timeout'  => config('services.fastapi.timeout', 30),
        ]);
    }

    /**
     * Titik masuk utama: ambil semua data RFM, kirim ke FastAPI,
     * terima hasil cluster, lalu simpan balik ke customer_segments.
     *
     * @return array{success: bool, processed: int, message: string}
     */
    public function predictAndSync(int $nClusters = 4): array
    {
        $segments = $this->segmentRepo->getAllForClustering();

        if ($segments->isEmpty()) {
            return [
                'success'   => false,
                'processed' => 0,
                'message'   => 'Tidak ada data RFM. Jalankan kalkulasi RFM terlebih dahulu.',
            ];
        }

        // Susun payload sesuai kontrak API FastAPI
        $payload = [
            'customers' => $segments->map(fn ($row) => [
                'customer_id' => $row->customer_id,
                'recency'     => (float) $row->recency,
                'frequency'   => (float) $row->frequency,
                'monetary'    => (float) $row->monetary,
            ])->values()->all(),
            'n_clusters' => $nClusters,
        ];

        try {
            $response = $this->httpClient->post('predict-cluster', [
                'json' => $payload,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            $detail = null;

            if ($e->hasResponse()) {
                $detail = json_decode((string) $e->getResponse()->getBody(), true);
            }

            if (is_array($detail) && isset($detail['detail'])) {
                $errorMessage = $detail['detail'];
            }

            Log::error('Gagal menghubungi FastAPI cluster service: ' . $errorMessage);

            return [
                'success'   => false,
                'processed' => 0,
                'message'   => 'FastAPI error: ' . $errorMessage,
            ];
        } catch (GuzzleException $e) {
            Log::error('Gagal menghubungi FastAPI cluster service: ' . $e->getMessage());

            return [
                'success'   => false,
                'processed' => 0,
                'message'   => 'Gagal terhubung ke layanan AI (FastAPI). Coba lagi nanti.',
            ];
        }

        if (!isset($result['results']) || !is_array($result['results'])) {
            return [
                'success'   => false,
                'processed' => 0,
                'message'   => 'Respons dari FastAPI tidak valid.',
            ];
        }

        // Ambil nama segmen dari database
        $segmentMappings = \App\Models\ClusterSetting::all()
            ->pluck('segment_name', 'cluster_label')
            ->toArray();

        $processed = 0;

        foreach ($result['results'] as $item) {
            $customerId   = $item['customer_id'] ?? null;
            $clusterLabel = $item['cluster_label'] ?? null;

            if ($customerId === null || $clusterLabel === null) {
                continue;
            }

            $segmentName = $segmentMappings[$clusterLabel] ?? "Cluster {$clusterLabel}";

            $this->segmentRepo->updateClusterResult(
                (int) $customerId,
                (int) $clusterLabel,
                $segmentName
            );

            $processed++;
        }

        // elbow_data ditentukan sekali saat training (mis. di Colab) untuk
        // menjustifikasi pemilihan k -- bukan dihitung ulang tiap prediksi live.
        // Supaya tidak hilang setiap kali proses klaster dijalankan, kita
        // pertahankan elbow_data yang sudah tersimpan sebelumnya kalau respons
        // FastAPI kali ini tidak membawa elbow_data baru.
        $existingMetrics = [];
        if (Storage::exists('model_metrics.json')) {
            $existingMetrics = json_decode(Storage::get('model_metrics.json'), true) ?? [];
        }

        // Metrik evaluasi model adalah hasil training penelitian, bukan hasil
        // prediction batch. Simpan sebagai sumber kebenaran agar dashboard
        // tetap konsisten dengan BAB V dan model .pkl yang dipakai FastAPI.
        $existingMetrics = [];
        if (Storage::exists('model_metrics.json')) {
            $existingMetrics = json_decode(Storage::get('model_metrics.json'), true) ?? [];
        }

        $metrics = array_merge($existingMetrics, [
            'chosen_k'                => $result['chosen_k'] ?? ($existingMetrics['chosen_k'] ?? 4),
            'centroid_distances'      => $result['centroid_distances'] ?? ($existingMetrics['centroid_distances'] ?? []),
            'assigned_euclidean_summary' => $result['assigned_euclidean_summary'] ?? ($existingMetrics['assigned_euclidean_summary'] ?? []),
            'euclidean_distance_by_k' => $existingMetrics['euclidean_distance_by_k'] ?? ($result['euclidean_distance_by_k'] ?? []),
        ]);

        Storage::put('model_metrics.json', json_encode($metrics, JSON_PRETTY_PRINT));

        return [
            'success'   => true,
            'processed' => $processed,
            'message'   => "Berhasil menyinkronkan hasil clustering untuk {$processed} pelanggan.",
            'metrics'   => $metrics,
        ];
    }
}