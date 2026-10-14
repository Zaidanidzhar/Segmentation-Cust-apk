<?php
namespace App\Repositories\Contracts;

interface CustomerSegmentRepositoryInterface
{
    public function upsert(int $customerId, array $data);

    public function getAllForClustering();

    public function updateClusterResult(int $customerId, int $clusterLabel, string $segmentName): void;
}
