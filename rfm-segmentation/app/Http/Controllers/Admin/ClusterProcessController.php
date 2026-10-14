<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CustomerSegmentRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Services\ClusterExperimentService;
use App\Services\RfmCalculationService;
use App\Services\ClusterPredictionService;
use Illuminate\Http\Request;

class ClusterProcessController extends Controller
{
    protected RfmCalculationService $rfmService;
    protected ClusterPredictionService $clusterService;
    protected CustomerRepositoryInterface $customerRepo;
    protected TransactionRepositoryInterface $transactionRepo;
    protected CustomerSegmentRepositoryInterface $segmentRepo;

    public function __construct(
        RfmCalculationService $rfmService,
        ClusterPredictionService $clusterService,
        CustomerRepositoryInterface $customerRepo,
        TransactionRepositoryInterface $transactionRepo,
        CustomerSegmentRepositoryInterface $segmentRepo
    ) {
        $this->rfmService = $rfmService;
        $this->clusterService = $clusterService;
        $this->customerRepo = $customerRepo;
        $this->transactionRepo = $transactionRepo;
        $this->segmentRepo = $segmentRepo;
    }

    public function index()
    {
        $transactionCount = $this->transactionRepo->countTransactions();

        $data = [
            'transactionCount' => $transactionCount,
            'hasTransactions'  => $transactionCount > 0,
        ];

        // If upload just happened, previews may be flashed in session — forward them
        if (session()->has('rawPreview')) {
            $data['rawPreview'] = session('rawPreview');
        }
        if (session()->has('cleanPreview')) {
            $data['cleanPreview'] = session('cleanPreview');
        }

        return view('admin.process', $data);
    }

    public function process(\Illuminate\Http\Request $request)
    {
        // K=4 adalah model final penelitian, ditentukan pada tahap eksperimen
        // menggunakan Elbow Method. Production prediction tidak retraining.
        $nClusters = 4;

        // Tahap 1: hitung RFM dari data transaksi mentah
        $processedCount = $this->rfmService->calculate();

        if ($processedCount === 0) {
            return redirect()->route('admin.upload.index')
                ->with('error', 'Tidak ada data transaksi untuk dihitung. Unggah dataset terlebih dahulu.');
        }

        // Tahap 2: kirim data RFM ke FastAPI untuk clustering K-Means
        $clusterResult = $this->clusterService->predictAndSync($nClusters);

        if (!$clusterResult['success']) {
            return redirect()->route('admin.upload.index')
                ->with('error', $clusterResult['message']);
        }

        return redirect()->route('admin.dashboard')
            ->with('success', "RFM dihitung untuk {$processedCount} pelanggan. {$clusterResult['message']}");
    }
    public function experiment(
        Request $request,
        ClusterExperimentService $experimentService
    ) {
        try {

            $kMin = (int) $request->input('k_min', 2);
            $kMax = (int) $request->input('k_max', 10);

            if ($kMin < 2) {
                $kMin = 2;
            }

            if ($kMax > 10) {
                $kMax = 10;
            }

            if ($kMin > $kMax) {
                return response()->json([
                    'message' => 'K minimum tidak boleh lebih besar dari K maksimum.'
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Ambil data RFM
            |--------------------------------------------------------------------------
            |
            | Gunakan method RFM yang sama dengan proses deployment.
            |
            */

            $rfmData = $this->segmentRepo->getAllForClustering()
                ->map(fn ($segment) => [
                    'customer_id' => (int) $segment->customer_id,
                    'recency' => (float) $segment->recency,
                    'frequency' => (float) $segment->frequency,
                    'monetary' => (float) $segment->monetary,
                ])
                ->values()
                ->all();


            if (empty($rfmData)) {

                return response()->json([
                    'message' => 'Data RFM tidak tersedia.'
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Jalankan eksperimen
            |--------------------------------------------------------------------------
            */

            $result = $experimentService->experiment(
                $rfmData,
                $kMin,
                $kMax
            );


            return response()->json($result);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}