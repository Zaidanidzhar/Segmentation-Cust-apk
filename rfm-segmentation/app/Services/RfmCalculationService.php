<?php
namespace App\Services;

use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\CustomerSegmentRepositoryInterface;
use Illuminate\Support\Collection;

class RfmCalculationService
{
    protected TransactionRepositoryInterface $transactionRepo;
    protected CustomerSegmentRepositoryInterface $segmentRepo;

    public function __construct(
        TransactionRepositoryInterface $transactionRepo,
        CustomerSegmentRepositoryInterface $segmentRepo
    ) {
        $this->transactionRepo = $transactionRepo;
        $this->segmentRepo = $segmentRepo;
    }

    /**
     * Titik masuk utama: hitung RFM mentah + skor 1-5, lalu simpan ke customer_segments.
     * Return jumlah customer yang berhasil diproses.
     */
    public function calculate(): int
    {
        $aggregates = $this->transactionRepo->getRawRfmAggregates();

        if ($aggregates->isEmpty()) {
            return 0;
        }

        // Hitung skor 1-5 berbasis quintile (kuantil) untuk masing-masing R, F, M
        $recencyScores  = $this->quintileScores($aggregates->pluck('recency'), inverse: true);
        $frequencyScores = $this->quintileScores($aggregates->pluck('frequency'), inverse: false);
        $monetaryScores  = $this->quintileScores($aggregates->pluck('monetary'), inverse: false);

        $processed = 0;

        foreach ($aggregates as $index => $row) {
            $this->segmentRepo->upsert($row->customer_id, [
                'recency'         => (int) $row->recency,
                'frequency'       => (int) $row->frequency,
                'monetary'        => (float) $row->monetary,
                'recency_score'   => $recencyScores[$index],
                'frequency_score' => $frequencyScores[$index],
                'monetary_score'  => $monetaryScores[$index],
                'calculated_at'   => now(),
            ]);
            $processed++;
        }

        return $processed;
    }

    /**
     * Bagi nilai menjadi 5 kelompok (quintile) dan beri skor 1-5.
     * inverse=true dipakai untuk Recency, karena nilai kecil (baru transaksi) = skor tinggi.
     *
     * @return array<int,int> skor per index sesuai urutan asli $values
     */
    protected function quintileScores(Collection $values, bool $inverse): array
    {
        $count = $values->count();

        if ($count === 0) {
            return [];
        }

        // Urutkan nilai beserta index aslinya, supaya bisa kita kembalikan ke urutan semula
        $sorted = $values
            ->map(fn ($value, $index) => ['index' => $index, 'value' => (float) $value])
            ->sortBy('value')
            ->values();

        $scores = array_fill(0, $count, 1);

        foreach ($sorted as $rank => $item) {
            // Posisi relatif 0.0 - 1.0
            $percentile = $count > 1 ? $rank / ($count - 1) : 0;

            // Bagi ke 5 kelompok
            $bucket = (int) ceil($percentile * 5);
            $bucket = max(1, min(5, $bucket));

            $score = $inverse ? (6 - $bucket) : $bucket;

            $scores[$item['index']] = $score;
        }

        return $scores;
    }
}