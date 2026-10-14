<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatasetImportService;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\CustomerSegment;
use App\Models\Transaction;

class UploadController extends Controller
{
    protected DatasetImportService $importService;

    public function __construct(DatasetImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index()
    {
        return view('admin.upload');
    }

    public function store(Request $request)
    {
        $request->validate([
            'dataset' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        try {
            $result = $this->importService->import($request->file('dataset'));
        } catch (Throwable $e) {
            return redirect()->route('admin.upload.index')
                ->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }

        $message = "Berhasil mengimpor {$result['imported']} transaksi dari {$result['customers']} pelanggan.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} baris dilewati.";
        }

        // Flash previews to session so the Process page can display them
        session()->flash('rawPreview', $result['raw_preview']);
        session()->flash('cleanPreview', $result['clean_preview']);

        return view('admin.upload', [
            'success'      => $message,
            'rawPreview'   => $result['raw_preview'],
            'cleanPreview' => $result['clean_preview'],
            'imported'     => $result['imported'],
            'skipped'      => $result['skipped'],
            'customerCount'=> $result['customers'],
        ]);
    }

    public function resetData(): \Illuminate\Http\RedirectResponse
    {
        DB::transaction(function () {
            CustomerSegment::query()->delete();
            Transaction::query()->delete();
            Customer::query()->delete();
        });

        return redirect()->route('admin.upload.index')
            ->with('success', 'Semua data transaksi, customer, dan segmentasi berhasil direset.');
    }
}