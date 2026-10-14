<?php
namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CustomerRepository implements CustomerRepositoryInterface
{
    protected Customer $model;

    public function __construct(Customer $model)
    {
        $this->model = $model;
    }

    public function countAll(): int
    {
        return $this->model->count();
    }

    public function getClusterDistribution(): array
    {
        return $this->model
            ->join('customer_segments', 'customers.id', '=', 'customer_segments.customer_id')
            ->select('customer_segments.segment_name', DB::raw('count(*) as total'))
            ->groupBy('customer_segments.segment_name')
            ->pluck('total', 'segment_name')
            ->toArray();
    }

    public function getTopCustomersBySegment(string $segmentName, int $limit = 10)
    {
        return $this->model
            ->join('customer_segments', 'customers.id', '=', 'customer_segments.customer_id')
            ->where('customer_segments.segment_name', $segmentName)
            ->orderByDesc('customer_segments.monetary')
            ->select('customers.*', 'customer_segments.monetary', 'customer_segments.frequency', 'customer_segments.recency')
            ->limit($limit)
            ->get();
    }

    /**
     * Semua pelanggan yang sudah punya hasil segmentasi (lintas semua segmen),
     * tanpa limit. Dipakai di halaman "Proses Klaster" supaya admin bisa lihat
     * seluruh dataset yang baru saja diproses, bukan cuma Top 10 Champions.
     */
    public function getAllProcessedCustomers()
    {
        return $this->model
            ->join('customer_segments', 'customers.id', '=', 'customer_segments.customer_id')
            ->orderByDesc('customer_segments.monetary')
            ->select(
                'customers.*',
                'customer_segments.monetary',
                'customer_segments.frequency',
                'customer_segments.recency',
                'customer_segments.segment_name'
            )
            ->get();
    }

    public function getAverageMonetary(): float
    {
        return (float) DB::table('customer_segments')->avg('monetary') ?? 0;
    }

    public function getActiveCustomerPercentage(int $days = 30): float
    {
        $total = $this->model->count();
        if ($total === 0) {
            return 0;
        }

        $active = $this->model
            ->where('last_transaction_at', '>=', now()->subDays($days))
            ->count();

        return round(($active / $total) * 100, 2);
    }

    public function getAllWithSegment()
    {
        return $this->model->with('segment')->get();
    }

    public function findByCode(string $customerCode)
    {
        return $this->model->where('customer_code', $customerCode)->first();
    }

    public function updateOrCreateByCode(string $customerCode, array $data)
    {
        return $this->model->updateOrCreate(
            ['customer_code' => $customerCode],
            $data
        );
    }
}