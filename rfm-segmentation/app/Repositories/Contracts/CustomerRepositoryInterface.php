<?php
namespace App\Repositories\Contracts;

interface CustomerRepositoryInterface
{
    public function countAll(): int;

    public function getClusterDistribution(): array;

    public function getTopCustomersBySegment(string $segmentName, int $limit = 10);

    public function getAverageMonetary(): float;

    public function getActiveCustomerPercentage(int $days = 30): float;

    public function getAllWithSegment();

    public function findByCode(string $customerCode);

    public function updateOrCreateByCode(string $customerCode, array $data);
}
