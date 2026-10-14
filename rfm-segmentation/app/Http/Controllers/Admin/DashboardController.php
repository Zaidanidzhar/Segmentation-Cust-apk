<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerSegment;
use App\Models\Transaction;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\FastApiMetricsService;

class DashboardController extends Controller
{
    protected CustomerRepositoryInterface $customerRepo;
    protected FastApiMetricsService $metricsService;

    public function __construct(CustomerRepositoryInterface $customerRepo, FastApiMetricsService $metricsService)
    {
        $this->customerRepo = $customerRepo;
        $this->metricsService = $metricsService;
    }

    // ──────────────────────────────────────────────────────────
    //  ENTRY POINT
    // ──────────────────────────────────────────────────────────

    public function index()
    {
        return view('admin.dashboard', [
            'totalCustomers'    => $this->customerRepo->countAll(),
            'totalTransactions' => Transaction::count(),
            'totalRevenue'      => (float) Transaction::sum('total_amount'),
            'customerGrowth'   => $this->buildCustomerGrowthTrend(),
            'lastSyncAt'        => CustomerSegment::max('calculated_at'),
            'clusteringResult'  => $this->buildClusteringResult(),
            'modelMetrics'      => $this->metricsService->fetchMetrics(),
            'topChampions'      => $this->customerRepo->getTopCustomersBySegment('Champions'),
        ]);
    }

    protected function buildCustomerGrowthTrend(): array
    {
        $months = collect(range(11, 0))
            ->map(fn (int $monthsAgo) => now()->subMonths($monthsAgo)->startOfMonth());

        $counts = Transaction::query()
            ->where('transaction_date', '>=', $months->first())
            ->get(['customer_id', 'transaction_date'])
            ->groupBy(fn ($transaction) => $transaction->transaction_date->format('Y-m'))
            ->map(fn ($transactions) => $transactions->pluck('customer_id')->unique()->count());

        return $months->map(fn ($month) => [
            'label' => $month->format('M Y'),
            'value' => $counts->get($month->format('Y-m'), 0),
        ])->all();
    }

    // ──────────────────────────────────────────────────────────
    //  BUILD CLUSTERING RESULT
    //  Prioritas data:
    //    1. model_metrics.json (dari Colab — paling akurat)
    //    2. Hitung ulang dari DB (fallback jika JSON belum ada)
    // ──────────────────────────────────────────────────────────

    protected function buildClusteringResult(): ?array
    {
        $metrics = $this->metricsService->fetchMetrics();

        $segmentCounts = $this->customerRepo->getClusterDistribution();

        if (! $metrics) {
            [$bins, $before, $after, $skewBefore, $skewAfter] = $this->buildLog1pDistribution();
            $scalerStats = $this->buildScalerStats();

            if (empty($bins)) {
                return null;
            }

            return [
                'chosen_k'             => null,
                'k_optimal'            => null,
                'silhouette_score'     => null,
                'davies_bouldin_index' => null,
                'elbow_inertias'       => [],
                'elbow_k_range'        => [],
                'silhouette_per_k'     => [],
                'silhouette_k_range'   => [],
                'log1p_bins'           => $bins,
                'log1p_before'         => $before,
                'log1p_after'          => $after,
                'skewness_before'      => $skewBefore,
                'skewness_after'       => $skewAfter,
                'scaler_stats'         => $scalerStats,
                'segment_counts'       => $segmentCounts,
                'profiling'            => [],
                'strategi'             => [],
                'algorithm_comparison' => [],
            ];
        }

        $result = [
            'chosen_k'             => isset($metrics['chosen_k'])             ? (int)   $metrics['chosen_k']             : null,
            'k_optimal'            => isset($metrics['chosen_k'])             ? (int)   $metrics['chosen_k']             : null,
            'silhouette_score'     => isset($metrics['silhouette_score'])     ? (float) $metrics['silhouette_score']     : null,
            'davies_bouldin_index' => isset($metrics['davies_bouldin_index']) ? (float) $metrics['davies_bouldin_index'] : null,
            'euclidean_distance_by_k' => is_array($metrics['euclidean_distance_by_k'] ?? null) ? $metrics['euclidean_distance_by_k'] : [],
            'centroid_distances'   => is_array($metrics['centroid_distances'] ?? null) ? $metrics['centroid_distances'] : [],
            'elbow_inertias'       => ! empty($metrics['elbow_inertias']) ? array_map('floatval', $metrics['elbow_inertias']) : [],
            'elbow_k_range'        => ! empty($metrics['elbow_k_range'])  ? array_map('intval',   $metrics['elbow_k_range'])  : [],
            'plot_3d_data'         => is_array($metrics['plot_3d_data'] ?? null) ? $metrics['plot_3d_data'] : $this->build3dData()['points'],
            'plot_3d_centroids'    => is_array($metrics['plot_3d_centroids'] ?? null) ? $metrics['plot_3d_centroids'] : $this->build3dData()['centroids'],
            'silhouette_per_k'     => ! empty($metrics['silhouette_per_k']) ? array_map('floatval', $metrics['silhouette_per_k']) : [],
            'silhouette_k_range'   => ! empty($metrics['silhouette_k_range']) ? array_map('intval',   $metrics['silhouette_k_range']) : [],
            'log1p_bins'           => $metrics['log1p_bins']   ?? [],
            'log1p_before'         => $metrics['log1p_before'] ?? [],
            'log1p_after'          => $metrics['log1p_after']  ?? [],
            'skewness_before'      => isset($metrics['skewness_before']) ? (float) $metrics['skewness_before'] : null,
            'skewness_after'       => isset($metrics['skewness_after'])  ? (float) $metrics['skewness_after']  : null,
            'scaler_stats'         => $metrics['scaler_stats'] ?? null,
            'segment_counts'       => $segmentCounts,
            'profiling'            => $metrics['profiling'] ?? [],
            'strategi'             => $metrics['strategi']  ?? [],
            'algorithm_comparison' => $metrics['algorithm_comparison'] ?? [],
        ];

        // ── Elbow: coba dari elbow_data dulu, fallback ke elbow_inertias ──
        if (! empty($metrics['elbow_data']) && is_array($metrics['elbow_data'])) {
            $result['elbow_k_range'] = [];
            $result['elbow_inertias'] = [];

            foreach ($metrics['elbow_data'] as $item) {
                if (! isset($item['k'])) {
                    continue;
                }
                $result['elbow_k_range'][]  = (int)   $item['k'];
                $result['elbow_inertias'][] = (float) ($item['inertia'] ?? 0.0);
            }
        } elseif (! empty($metrics['elbow_inertias']) && ! empty($metrics['elbow_k_range'])) {
            $result['elbow_inertias'] = array_map('floatval', $metrics['elbow_inertias']);
            $result['elbow_k_range']  = array_map('intval',   $metrics['elbow_k_range']);
        }

        // ── Silhouette per K ──
        if (! empty($metrics['silhouette_per_k']) && is_array($metrics['silhouette_per_k'])) {
            $result['silhouette_per_k']   = array_map('floatval', $metrics['silhouette_per_k']);
            $result['silhouette_k_range'] = array_map('intval',   $metrics['silhouette_k_range'] ?? []);
        } elseif ($result['silhouette_score'] !== null && $result['k_optimal'] !== null) {
            // Fallback: hanya satu titik jika data per-K tidak ada
            $result['silhouette_per_k']   = [(float) $result['silhouette_score']];
            $result['silhouette_k_range'] = [(int)   $result['k_optimal']];
        }

        if (empty($result['strategi'])) {
            $result['strategi'] = [
                [
                    'segment' => 'Champions',
                    'focus' => 'Pertahankan loyalitas pelanggan bernilai tinggi.',
                    'action' => 'Berikan akses awal, reward eksklusif, dan program referral.',
                ],
                [
                    'segment' => 'Loyal Customers',
                    'focus' => 'Naikkan frekuensi pembelian dan nilai transaksi.',
                    'action' => 'Tawarkan bundling personal dan program membership.',
                ],
                [
                    'segment' => 'At Risk',
                    'focus' => 'Reaktivasi pelanggan yang mulai jarang bertransaksi.',
                    'action' => 'Kirim penawaran win-back dengan batas waktu yang jelas.',
                ],
                [
                    'segment' => 'Hibernating/Churn',
                    'focus' => 'Uji peluang untuk mengaktifkan kembali pelanggan pasif.',
                    'action' => 'Gunakan kampanye re-engagement berbiaya rendah dan evaluasi responsnya.',
                ],
            ];
        }

        return $result;
    }

    protected function build3dData(): array
    {
        $rows = CustomerSegment::query()
            ->whereNotNull('recency')
            ->whereNotNull('frequency')
            ->whereNotNull('monetary')
            ->get(['customer_id', 'recency', 'frequency', 'monetary', 'segment_name']);

        $points = $rows->map(fn ($row) => [
            'x' => (float) $row->recency,
            'y' => (float) $row->frequency,
            'z' => log1p((float) $row->monetary),
            'cluster' => $row->segment_name ?: 'Unassigned',
            'name' => 'Customer #' . $row->customer_id,
        ])->values()->all();

        $centroids = $rows
            ->groupBy(fn ($row) => $row->segment_name ?: 'Unassigned')
            ->map(function ($group, $segment) {
                return [
                    'x' => round((float) $group->avg('recency'), 4),
                    'y' => round((float) $group->avg('frequency'), 4),
                    'z' => round((float) $group->avg(fn ($row) => log1p((float) $row->monetary)), 4),
                    'cluster' => $segment,
                ];
            })->values()->all();

        return compact('points', 'centroids');
    }



    // ──────────────────────────────────────────────────────────
    //  LOG1P DISTRIBUTION
    //  Menghitung histogram Monetary sebelum & sesudah log1p
    //  langsung dari tabel customer_segments (data live DB).
    //  Return: [bins, countsBefore, countsAfter, skewBefore, skewAfter]
    // ──────────────────────────────────────────────────────────

    protected function buildLog1pDistribution(): array
    {
        $values = CustomerSegment::query()
            ->whereNotNull('monetary')
            ->pluck('monetary')
            ->map(fn ($v) => (float) $v)
            ->filter(fn ($v) => $v >= 0)
            ->values();

        if ($values->isEmpty()) {
            return [[], [], [], null, null];
        }

        $logValues  = $values->map(fn ($v) => log1p($v));
        $binCount   = 10;

        $countsBefore = array_fill(0, $binCount, 0);
        $countsAfter  = array_fill(0, $binCount, 0);

        $rawMin   = (float) $values->min();
        $rawMax   = (float) $values->max();
        $logMin   = (float) $logValues->min();
        $logMax   = (float) $logValues->max();
        $rawRange = max($rawMax - $rawMin, 1.0);
        $logRange = max($logMax - $logMin, 1.0);

        foreach ($values as $i => $raw) {
            $log    = $logValues[$i];
            $rawBin = min($binCount - 1, (int) floor((($raw - $rawMin) / $rawRange) * $binCount));
            $logBin = min($binCount - 1, (int) floor((($log - $logMin) / $logRange) * $binCount));
            $countsBefore[$rawBin]++;
            $countsAfter[$logBin]++;
        }

        // Label bin: tampilkan batas bawah tiap bin dalam format Rupiah singkat
        $binWidth = $rawRange / $binCount;
        $labels   = array_map(function ($i) use ($rawMin, $binWidth) {
            $val = $rawMin + $i * $binWidth;
            if ($val >= 1_000_000) {
                return number_format($val / 1_000_000, 1) . 'M';
            }
            return number_format($val / 1_000, 0) . 'k';
        }, range(0, $binCount - 1));

        return [
            $labels,
            $countsBefore,
            $countsAfter,
            $this->calculateSkewness($values->all()),
            $this->calculateSkewness($logValues->all()),
        ];
    }

    // ──────────────────────────────────────────────────────────
    //  STANDARD SCALER STATS
    //  Mensimulasikan StandardScaler Python (z-score normalisasi)
    //  pada 3 fitur: Recency, Frequency, Monetary_log
    //  Return: array stats per fitur, atau null jika data kosong
    // ──────────────────────────────────────────────────────────

    protected function buildScalerStats(): ?array
    {
        $rows = CustomerSegment::query()
            ->whereNotNull('recency')
            ->whereNotNull('frequency')
            ->whereNotNull('monetary')
            ->get(['recency', 'frequency', 'monetary']);

        if ($rows->isEmpty()) {
            return null;
        }

        $featureSeries = [
            'Recency'      => $rows->pluck('recency')->map(fn ($v) => (float) $v),
            'Frequency'    => $rows->pluck('frequency')->map(fn ($v) => (float) $v),
            'Monetary_log' => $rows->pluck('monetary')->map(fn ($v) => log1p((float) $v)),
        ];

        $stats = [];
        foreach ($featureSeries as $feat => $values) {
            $meanBefore = (float) $values->avg();
            $stdBefore  = $this->populationStd($values->all());
            $scaled     = $values->map(
                fn ($v) => $stdBefore > 0 ? ($v - $meanBefore) / $stdBefore : 0.0
            );

            $stats[$feat] = [
                'mean_before' => round($meanBefore, 4),
                'std_before'  => round($stdBefore, 4),
                'mean_after'  => round((float) $scaled->avg(), 6),   // ≈ 0
                'std_after'   => round($this->populationStd($scaled->all()), 6),  // ≈ 1
                'min_after'   => round((float) $scaled->min(), 4),
                'max_after'   => round((float) $scaled->max(), 4),
            ];
        }

        return $stats;
    }

    // ──────────────────────────────────────────────────────────
    //  MATH HELPERS
    // ──────────────────────────────────────────────────────────

    /**
     * Population standard deviation (pembagi N, bukan N-1).
     * Konsisten dengan np.std() default Python di Colab.
     */
    protected function populationStd(array $values): float
    {
        $n = count($values);
        if ($n === 0) {
            return 0.0;
        }

        $mean     = array_sum($values) / $n;
        $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / $n;

        return $variance > 0 ? sqrt($variance) : 0.0;
    }

    /**
     * Fisher-Pearson skewness coefficient.
     * Konsisten dengan pd.Series.skew() di Python (pakai koreksi bias N-1, N-2).
     */
    protected function calculateSkewness(array $values): ?float
    {
        $n = count($values);
        if ($n < 3) {
            return null;
        }

        $mean     = array_sum($values) / $n;
        $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / ($n - 1);

        if ($variance <= 0.0) {
            return null;
        }

        $std = sqrt($variance);
        $m3  = array_sum(array_map(fn ($v) => ($v - $mean) ** 3, $values)) / $n;

        // Koreksi bias Pearson: G1 = [n / ((n-1)(n-2))] * (m3 / std^3)
        $skew = ($n / (($n - 1) * ($n - 2))) * ($m3 / ($std ** 3)) * $n;

        return round($skew, 4);
    }
}