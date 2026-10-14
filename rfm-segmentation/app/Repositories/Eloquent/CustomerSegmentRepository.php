<?php
namespace App\Repositories\Eloquent;

use App\Models\CustomerSegment;
use App\Repositories\Contracts\CustomerSegmentRepositoryInterface;

class CustomerSegmentRepository implements CustomerSegmentRepositoryInterface
{
    protected CustomerSegment $model;

    public function __construct(CustomerSegment $model)
    {
        $this->model = $model;
    }

    public function upsert(int $customerId, array $data)
    {
        return $this->model->updateOrCreate(
            ['customer_id' => $customerId],
            $data
        );
    }

    public function getAllForClustering()
    {
        // Data RFM yang siap dikirim ke FastAPI untuk clustering
        return $this->model
            ->select('id', 'customer_id', 'recency', 'frequency', 'monetary')
            ->get();
    }

    public function updateClusterResult(int $customerId, int $clusterLabel, string $segmentName): void
    {
        $this->model
            ->where('customer_id', $customerId)
            ->update([
                'cluster_label' => $clusterLabel,
                'segment_name' => $segmentName,
            ]);
    }
}