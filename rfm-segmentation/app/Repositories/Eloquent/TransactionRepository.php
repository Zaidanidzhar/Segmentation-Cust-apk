<?php
namespace App\Repositories\Eloquent;

use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TransactionRepository implements TransactionRepositoryInterface
{
    protected Transaction $model;

    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function insertBulk(array $rows): void
    {
        collect($rows)->chunk(500)->each(function ($chunk) {
            $this->model->insert($chunk->toArray());
        });
    }

    public function countTransactions(): int
    {
        return $this->model->count();
    }

    /**
     * Agregasi mentah per customer_id untuk hitung RFM:
     * - recency: dihitung terhadap SNAPSHOT DATETIME dinamis (= waktu transaksi
     *   TERAKHIR di seluruh data + 1 hari, JAM-nya tetap dipertahankan), BUKAN
     *   NOW()/hari ini, dan BUKAN dipotong ke tanggal doang. Ini mereplikasi
     *   persis baris Colab:
     *       snapshot_date = df_orders['Waktu Pesanan Selesai'].max() + 1 hari
     *   (di Python, hasil penjumlahan Timestamp + Timedelta tetap membawa jam).
     *
     *   Selisih hari dihitung pakai TIMESTAMPDIFF(SECOND, ...) / 86400 lalu
     *   di-FLOOR -- BUKAN DATEDIFF(). DATEDIFF() di MySQL cuma membandingkan
     *   komponen TANGGAL (jam selalu diabaikan/dianggap 00:00:00), sedangkan
     *   Python .days pada Timedelta menghitung dari selisih waktu PENUH
     *   (termasuk jam) baru dibulatkan ke bawah. Contoh: transaksi jam 08:00
     *   dan snapshot jam 20:00 keesokan harinya -- DATEDIFF() bilang selisih
     *   1 hari (cuma lihat tanggal), padahal baru lewat 12 jam (belum genap
     *   24 jam), harusnya 0 hari. Selisih pembulatan seperti ini cukup untuk
     *   menggeser pelanggan yang nilainya pas di garis batas antar cluster.
     *
     * - frequency & monetary: dihitung lewat subquery yang DEDUP dulu per
     *   (customer_id, invoice_no) -- 1 baris = 1 pesanan unik, bukan 1 baris
     *   produk. Ini persis mereplikasi Colab:
     *       df_orders = df_clean.groupby(['No. Pesanan', 'Username (Pembeli)'])
     *                            .agg({'Total Pembayaran': 'first'})
     *   Tanpa dedup ini, pesanan dengan banyak baris produk akan membuat
     *   Frequency dan Monetary ke-hitung berkali-kali (over-counted).
     */
    public function getRawRfmAggregates()
    {
        $lastTransactionDate = $this->model->max('transaction_date');

        // Snapshot TETAP membawa jam (bukan toDateString()), persis seperti
        // Colab yang mempertahankan jam pada snapshot_date.
        $snapshotDateTime = $lastTransactionDate
            ? \Carbon\Carbon::parse($lastTransactionDate)->addDay()
            : now();
        $snapshotDateTimeString = $snapshotDateTime->format('Y-m-d H:i:s');

        // Subquery: 1 baris = 1 pesanan unik (dedup per customer_id + invoice_no)
        $ordersPerCustomer = $this->model
            ->select('customer_id', 'invoice_no')
            ->selectRaw('MAX(transaction_date) as transaction_date')
            ->selectRaw('MAX(total_amount) as order_total')
            ->groupBy('customer_id', 'invoice_no');

        return DB::query()
            ->fromSub($ordersPerCustomer, 'unique_orders')
            ->select('customer_id')
            ->selectRaw('FLOOR(TIMESTAMPDIFF(SECOND, MAX(transaction_date), ?) / 86400) as recency', [$snapshotDateTimeString])
            ->selectRaw('COUNT(*) as frequency')
            ->selectRaw('SUM(order_total) as monetary')
            ->groupBy('customer_id')
            ->get();
    }

    public function truncate(): void
    {
        $this->model->truncate();
    }
}