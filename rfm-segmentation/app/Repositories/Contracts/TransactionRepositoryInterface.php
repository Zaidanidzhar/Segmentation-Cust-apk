<?php
namespace App\Repositories\Contracts;

interface TransactionRepositoryInterface
{
    public function create(array $data);

    public function insertBulk(array $rows): void;

    public function getRawRfmAggregates();

    public function countTransactions(): int;

    public function truncate(): void;
}