<?php

namespace App\Helpers;

/**
 * ModelMetricsHelper
 *
 * Membaca model_metrics.json yang di-export dari Google Colab
 * dan mengubahnya menjadi array PHP siap pakai untuk controller.
 *
 * Cara penggunaan di controller:
 *   $model_metrics = ModelMetricsHelper::load();
 */
class ModelMetricsHelper
{
    /**
     * Path default file JSON (relatif dari storage/app/).
     * Letakkan model_metrics.json di: storage/app/model_metrics.json
     */
    protected static string $defaultPath = 'model_metrics.json';

    // ──────────────────────────────────────────────────────────
    //  PUBLIC API
    // ──────────────────────────────────────────────────────────

    /**
     * Load dan kembalikan seluruh isi model_metrics.json sebagai array PHP.
     * Return null jika file belum ada.
     */
    public static function load(?string $path = null): ?array
    {
        $fullPath = storage_path('app/' . ($path ?? static::$defaultPath));

        if (! file_exists($fullPath)) {
            return null;
        }

        $raw = file_get_contents($fullPath);
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * Kembalikan array $model_metrics yang sudah dinormalisasi
     * sesuai struktur yang diharapkan blade dashboard & landing page.
     *
     * Semua key dijamin ada (dengan nilai default) sehingga
     * blade tidak perlu @isset berulang-ulang.
     */
    public static function forView(?string $path = null): ?array
    {
        $raw = static::load($path);

        if (! $raw) {
            return null;
        }

        // ── Elbow data: ubah ke format [{k, inertia}] jika perlu ──
        $elbowData = $raw['elbow_data'] ?? [];
        if (empty($elbowData) && ! empty($raw['elbow_inertias'])) {
            foreach ($raw['elbow_k_range'] ?? range(1, 10) as $i => $k) {
                $elbowData[] = [
                    'k'       => (int) $k,
                    'inertia' => (float) ($raw['elbow_inertias'][$i] ?? 0),
                ];
            }
        }

        return [
            // ── Evaluasi utama ──
            'silhouette_score'     => (float) ($raw['silhouette_score']     ?? 0),
            'davies_bouldin_index' => (float) ($raw['davies_bouldin_index'] ?? 0),
            'chosen_k'             => (int)   ($raw['chosen_k']             ?? 4),
            'k_optimal'            => (int)   ($raw['chosen_k']             ?? 4),
            'euclidean_distance_by_k' => $raw['euclidean_distance_by_k'] ?? [],

            // ── Log1p ──
            'skewness_before'      => (float) ($raw['skewness_before'] ?? 0),
            'skewness_after'       => (float) ($raw['skewness_after']  ?? 0),
            'log1p_bins'           => $raw['log1p_bins']        ?? [],
            'log1p_before'         => $raw['log1p_before']      ?? [],
            'log1p_after'          => $raw['log1p_after']       ?? [],
            'log1p_bins_after'     => $raw['log1p_bins_after']  ?? [],

            // ── StandardScaler ──
            'scaler_stats'         => $raw['scaler_stats'] ?? [],

            // ── Elbow ──
            'elbow_data'           => $elbowData,
            'elbow_inertias'       => $raw['elbow_inertias'] ?? [],
            'elbow_k_range'        => $raw['elbow_k_range']  ?? [],

            // ── Silhouette per K ──
            'silhouette_per_k'     => $raw['silhouette_per_k']   ?? [],
            'silhouette_k_range'   => $raw['silhouette_k_range'] ?? [],

            // ── Segmen ──
            'segment_counts'       => $raw['segment_counts'] ?? [],
            'profiling'            => $raw['profiling']      ?? [],
            'strategi'             => $raw['strategi']       ?? [],

            // ── Perbandingan algoritma ──
            'algorithm_comparison' => $raw['algorithm_comparison'] ?? [],
        ];
    }

    /**
     * Cek apakah file metrics sudah ada.
     */
    public static function exists(?string $path = null): bool
    {
        return file_exists(
            storage_path('app/' . ($path ?? static::$defaultPath))
        );
    }

    /**
     * Kembalikan waktu terakhir file dimodifikasi (Carbon-compatible).
     */
    public static function lastModified(?string $path = null): ?string
    {
        $fullPath = storage_path('app/' . ($path ?? static::$defaultPath));

        if (! file_exists($fullPath)) {
            return null;
        }

        return date('Y-m-d H:i:s', filemtime($fullPath));
    }
}