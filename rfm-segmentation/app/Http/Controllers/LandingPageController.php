<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\FastApiMetricsService;

class LandingPageController extends Controller
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
        $allCustomers = $this->customerRepo->getAllWithSegment();
        $metrics      = $this->metricsService->fetchMetrics();

        return view('landing', [
            // ── Statistik ringkasan ──
            'total_customers'      => $this->customerRepo->countAll(),
            'average_monetary'     => $this->customerRepo->getAverageMonetary(),
            'active_percentage'    => $this->customerRepo->getActiveCustomerPercentage(),
            'total_revenue'        => $allCustomers->sum(fn ($c) => (float) ($c->segment->monetary ?? 0)),

            // ── Distribusi & top pelanggan ──
            'cluster_distribution' => $this->customerRepo->getClusterDistribution(),
            'top_potensial'        => $this->customerRepo->getTopCustomersBySegment('Champions'),
            'all_customers'        => $allCustomers,

            // ── Model metrics (untuk semua chart & insight) ──
            'model_metrics'        => $metrics,

            // ── 3D plot ──
            'plot_3d_data'         => $this->build3dPlotData($allCustomers),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    //  LOAD & NORMALISASI model_metrics.json
    //
    //  Seluruh key yang dibutuhkan blade dijamin ada (dengan
    //  nilai default kosong/null) sehingga view tidak perlu
    //  @isset berulang-ulang.
    //
    //  Prioritas data:
    //    1. model_metrics.json (nilai dari Colab — paling akurat)
    //    2. null (tampilkan empty-state di blade)
    // ──────────────────────────────────────────────────────────


    // ──────────────────────────────────────────────────────────
    //  3D PLOT DATA
    //  Sumbu Z pakai log1p(monetary) — sama persis dengan
    //  fitur training K-Means di Colab (Monetary_log).
    //  Nilai dikonversi ulang di sini agar konsisten.
    // ──────────────────────────────────────────────────────────

    protected function build3dPlotData($customers): array
    {
        return $customers
            ->filter(fn ($c) => $c->segment !== null)
            ->map(fn ($c) => [
                'x'       => (float) $c->segment->recency,
                'y'       => (float) $c->segment->frequency,
                'z'       => round(log1p((float) $c->segment->monetary), 4),
                'cluster' => $c->segment->segment_name ?? 'Unknown',
                'name'    => $c->name ?? $c->customer_code ?? '-',
            ])
            ->values()
            ->all();
    }
}