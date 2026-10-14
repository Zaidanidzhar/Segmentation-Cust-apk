<?php
namespace App\Services;

use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use RuntimeException;
use App\Models\Customer;
use App\Models\CustomerSegment;

class DatasetImportService
{
    /**
     * Mapping nama kolom yang kita butuhkan -> daftar kemungkinan nama header di file
     * (case-insensitive). Ini yang membuat importer "mengerti" berbagai format export,
     * termasuk export marketplace seperti Shopee/Tokopedia.
     */
    protected array $columnAliases = [
        'customer_code'    => ['customer_code', 'customer id', 'customerid', 'id pelanggan', 'customer', 'username (pembeli)', 'username', 'customername', 'customer code'],
        // 'nama penerima' sengaja TIDAK dimasukkan: kolom ini disensor Shopee
        // (contoh: "A******)"), jadi kalau dianggap sebagai 'name', dashboard
        // akan menampilkan nama tersensor. Biarkan importer gagal mendeteksi
        // kolom 'name' pada file Shopee, supaya otomatis fallback ke
        // customer_code (Username Pembeli, yang tidak disensor).
        'name'             => ['name', 'nama pelanggan', 'nama'],
        'product_name'     => ['product_name', 'nama produk', 'produk'],
        'quantity'         => ['quantity', 'jumlah', 'qty', 'item_count'],
        'unit_price'       => ['unit_price', 'harga setelah diskon', 'harga awal', 'harga satuan', 'harga', 'price', 'unit price'],
        'order_total'      => ['order_total', 'total pembayaran', 'total_pembayaran', 'total payment'],
        'invoice_no'       => ['invoice_no', 'no. pesanan', 'no pesanan', 'nomor invoice', 'invoice'],
        'transaction_date' => ['transaction_date', 'waktu pesanan selesai', 'waktu pembayaran dilakukan', 'waktu pesanan dibuat', 'tanggal transaksi', 'tanggal', 'date', 'created_at'],
        'order_status'     => ['status pesanan', 'order_status', 'status'],
        'recency'          => ['recency', 'days_since_last_transaction', 'hari sejak transaksi terakhir'],
        'frequency'        => ['frequency', 'jumlah transaksi', 'transaction_count', 'freq'],
        'monetary'         => ['monetary', 'total belanja', 'total spending', 'nilai transaksi', 'amount'],
    ];

    public function __construct(
        protected CustomerRepositoryInterface $customerRepo,
        protected TransactionRepositoryInterface $transactionRepo
    ) {
    }

    public function import(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $rawRows = in_array($extension, ['xlsx', 'xls'])
            ? $this->readSpreadsheet($file)
            : $this->readCsv($file);

        if (empty($rawRows)) {
            throw new RuntimeException('File kosong atau tidak ada baris data yang terbaca.');
        }

        // Setiap upload dataset baru MENGGANTIKAN data transaksi lama sepenuhnya
        // (bukan menambah/menumpuk). Tanpa ini, upload berulang kali akan membuat
        // baris duplikat untuk invoice_no yang sama -- yang pernah menyebabkan
        // Monetary salah hitung (MAX() pada data hasil dedup memilih nilai lama
        // yang salah, karena bercampur dengan nilai baru yang benar).
        $this->transactionRepo->truncate();
        CustomerSegment::query()->delete();
        Customer::query()->delete();

        $rawHeader = array_shift($rawRows);
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $rawHeader);
        $columnMap = $this->resolveColumnMap($header);

        $hasTransactionFields = array_key_exists('customer_code', $columnMap)
            && array_key_exists('quantity', $columnMap)
            && array_key_exists('unit_price', $columnMap)
            && array_key_exists('transaction_date', $columnMap);
        $hasRfmFields = array_key_exists('customer_code', $columnMap)
            && array_key_exists('recency', $columnMap)
            && array_key_exists('frequency', $columnMap)
            && array_key_exists('monetary', $columnMap);

        if (!$hasTransactionFields && !$hasRfmFields) {
            throw new RuntimeException(
                'Kolom wajib tidak ditemukan. Harap sertakan kolom transaksi (customer_code, quantity, unit_price, transaction_date) atau kolom RFM (customer_code, recency, frequency, monetary). Kolom terdeteksi: ' . implode(', ', $header)
            );
        }

        $rows = [];
        $cleanPreviewRows = [];
        $importedRows = 0;
        $skippedRows = 0;
        $customerCache = [];

        foreach ($rawRows as $rowValues) {
            $record = $this->mapRow($rowValues, $header, $columnMap);

            if ($hasRfmFields) {
                $customerCode = trim((string) ($record['customer_code'] ?? ''));
                if ($customerCode === '') {
                    $skippedRows++;
                    continue;
                }

                if (!isset($customerCache[$customerCode])) {
                    $customer = $this->customerRepo->updateOrCreateByCode($customerCode, [
                        'name' => $record['name'] ?? $customerCode,
                    ]);
                    $customerCache[$customerCode] = $customer->id;
                }

                $recency = (float) ($record['recency'] ?? 0);
                $frequency = (int) ($record['frequency'] ?? 0);
                $monetary = (float) ($record['monetary'] ?? 0);

                if ($frequency <= 0 && $monetary <= 0 && $recency < 0) {
                    $skippedRows++;
                    continue;
                }

                $transactionRow = [
                    'customer_id'      => $customerCache[$customerCode],
                    'invoice_no'       => $record['invoice_no'] ?? null,
                    'product_name'     => $record['product_name'] ?? null,
                    'quantity'         => max(1, $frequency),
                    'unit_price'       => max(0, $monetary),
                    'total_amount'     => max(0, $monetary),
                    'transaction_date' => now()->subDays(max(0, (int) $recency))->startOfDay(),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];

                if (count($cleanPreviewRows) < 10) {
                    $cleanPreviewRows[] = [
                        'customer_code'   => $customerCode,
                        'name'            => $record['name'] ?? null,
                        'invoice_no'      => $record['invoice_no'] ?? null,
                        'quantity'        => $transactionRow['quantity'],
                        'unit_price'      => $transactionRow['unit_price'],
                        'total_amount'    => $transactionRow['total_amount'],
                        'transaction_date'=> $transactionRow['transaction_date']->format('Y-m-d'),
                    ];
                }

                $rows[] = $transactionRow;
                $importedRows++;
                continue;
            }

            $status = strtolower(trim((string) ($record['order_status'] ?? '')));

            if ($status !== '' && !in_array($status, ['selesai', 'completed', 'success'], true)) {
                $skippedRows++;
                continue;
            }

            $customerCode = trim((string) ($record['customer_code'] ?? ''));
            $rawDate = $record['transaction_date'] ?? null;

            if ($customerCode === '' || empty($rawDate)) {
                $skippedRows++;
                continue;
            }

            $transactionDate = $this->parseDate($rawDate);
            if (!$transactionDate) {
                $skippedRows++;
                continue;
            }

            if (!isset($customerCache[$customerCode])) {
                $customer = $this->customerRepo->updateOrCreateByCode($customerCode, [
                    'name' => $record['name'] ?? $customerCode,
                ]);
                $customerCache[$customerCode] = $customer->id;
            }

            $quantity  = (int) $this->parseNumber($record['quantity'] ?? 1);
            $unitPrice = $this->parseNumber($record['unit_price'] ?? 0);
            $orderTotal = array_key_exists('order_total', $record) && $record['order_total'] !== null
                ? $this->parseNumber($record['order_total'])
                : null;

            if ($quantity <= 0) {
                $skippedRows++;
                continue;
            }

            // Pakai 'Total Pembayaran' (nilai final per pesanan dari Shopee) kalau
            // kolomnya terdeteksi -- ini sama persis dengan definisi Monetary di
            // Colab (df_orders.agg({'Total Pembayaran': 'first'})). Nilai ini akan
            // sama untuk setiap baris produk dalam 1 pesanan yang sama; dedup-nya
            // dilakukan nanti saat agregasi RFM (lihat TransactionRepository),
            // BUKAN di sini, supaya tetap 1 baris = 1 baris produk di tabel
            // transactions (konsisten dengan data mentah).
            $totalAmount = $orderTotal !== null ? $orderTotal : ($quantity * $unitPrice);

            $transactionRow = [
                'customer_id'      => $customerCache[$customerCode],
                'invoice_no'       => $record['invoice_no'] ?? null,
                'product_name'     => $record['product_name'] ?? null,
                'quantity'         => $quantity,
                'unit_price'       => $unitPrice,
                'total_amount'     => $totalAmount,
                'transaction_date' => $transactionDate,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];

            if (count($cleanPreviewRows) < 10) {
                $cleanPreviewRows[] = [
                    'customer_code'   => $customerCode,
                    'name'            => $record['name'] ?? null,
                    'invoice_no'      => $record['invoice_no'] ?? null,
                    'quantity'        => $quantity,
                    'unit_price'      => $unitPrice,
                    'total_amount'    => $quantity * $unitPrice,
                    'transaction_date'=> $transactionDate->format('Y-m-d'),
                ];
            }

            $rows[] = $transactionRow;
            $importedRows++;
        }

        if (empty($rows)) {
            throw new RuntimeException('Tidak ada baris valid yang bisa diimpor. Periksa kembali isi file Anda.');
        }

        $this->transactionRepo->insertBulk($rows);
        $this->syncLastTransactionDate(array_keys($customerCache));

        return [
            'imported'      => $importedRows,
            'skipped'       => $skippedRows,
            'customers'     => count($customerCache),
            'raw_preview'   => $this->buildRawPreview($rawHeader, $rawRows, 5),
            'clean_preview' => $cleanPreviewRows,
        ];
    }

    /**
     * Baca file .xlsx / .xls — otomatis pakai sheet AKTIF/PERTAMA,
     * apapun namanya (fix bug "Sheet XLSX tidak ditemukan").
     */
    protected function readSpreadsheet(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheet(0); // ambil sheet pertama, tidak hardcode nama

        return $sheet->toArray(null, true, true, false);
    }

    protected function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new RuntimeException('Gagal membuka file CSV.');
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = $data;
        }
        fclose($handle);

        if (!empty($rows[0][0])) {
            $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $rows[0][0]); // strip BOM
        }

        return $rows;
    }

    /**
     * Cocokkan header file dengan kolom yang kita butuhkan, berdasarkan alias.
     * PENTING: prioritas berdasarkan URUTAN ALIAS (alias pertama dalam list =
     * prioritas tertinggi), BUKAN urutan kemunculan kolom di file. Ini krusial
     * untuk kasus seperti Shopee export yang punya beberapa kolom tanggal
     * ("Waktu Pesanan Dibuat", "Waktu Pembayaran Dilakukan", "Waktu Pesanan
     * Selesai") -- tanpa prioritas alias, sistem akan salah pilih kolom
     * tanggal yang paling awal muncul di file, bukan yang paling sesuai
     * secara semantik untuk perhitungan RFM.
     * Return: ['customer_code' => 2, 'quantity' => 5, ...] (index kolom di header)
     */
    protected function normalizeHeaderName(string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = preg_replace('/[^a-z0-9]+/', '', $normalized);

        return $normalized;
    }

    protected function resolveColumnMap(array $header): array
    {
        $map = [];
        $normalizedHeader = array_map(fn ($columnName) => $this->normalizeHeaderName($columnName), $header);

        foreach ($this->columnAliases as $field => $aliases) {
            foreach ($aliases as $alias) {
                $normalizedAlias = $this->normalizeHeaderName($alias);
                $index = array_search($normalizedAlias, $normalizedHeader, true);

                if ($index !== false) {
                    $map[$field] = $index;
                    break; // alias dengan prioritas tertinggi yang match, langsung pakai
                }
            }
        }

        return $map;
    }

    protected function mapRow(array $rowValues, array $header, array $columnMap): array
    {
        $record = [];
        foreach ($columnMap as $field => $index) {
            $record[$field] = $rowValues[$index] ?? null;
        }
        return $record;
    }

    /**
     * Parse angka format Indonesia, misal "130.000" -> 130000, "14.700" -> 14700.
     * Juga aman untuk angka polos seperti 130000 atau "130000".
     */
    protected function parseNumber(mixed $value): float
    {
            if (is_int($value) || is_float($value)) {
                return (float) $value;
            }

            $clean = trim((string) $value);
            $clean = str_replace(['Rp', ' '], '', $clean);

            if ($clean === '') {
                return 0;
            }

            // Format Indonesia: titik = ribuan, koma = desimal. Contoh: "230.000" atau "230.000,50"
            if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $clean)) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            }
            // Format dengan koma desimal saja, misal "230000,50"
            elseif (preg_match('/^\d+,\d+$/', $clean)) {
                $clean = str_replace(',', '.', $clean);
            }
            // Angka polos tanpa separator, misal "230000"
            else {
                $clean = str_replace(',', '', $clean);
            }

            return is_numeric($clean) ? (float) $clean : 0;
        }

    protected function parseDate(mixed $value): ?Carbon
    {
        try {
            if (is_numeric($value)) {
                // Excel serial date
                return Carbon::instance(
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                );
            }
            return Carbon::parse((string) $value);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function buildRawPreview(array $header, array $rows, int $limit = 5): array
    {
        $previewRows = [];
        foreach (array_slice($rows, 0, $limit) as $rowValues) {
            $record = [];
            foreach ($header as $index => $columnName) {
                $record[$columnName] = $rowValues[$index] ?? null;
            }
            $previewRows[] = $record;
        }

        return [
            'header' => $header,
            'rows'   => $previewRows,
        ];
    }

    protected function buildCleanPreview(array $rows, int $limit = 10): array
    {
        $previewRows = [];
        foreach (array_slice($rows, 0, $limit) as $row) {
            $previewRows[] = [
                'customer_code'   => $row['customer_code'] ?? null,
                'name'            => $row['name'] ?? null,
                'invoice_no'      => $row['invoice_no'] ?? null,
                'quantity'        => $row['quantity'] ?? null,
                'unit_price'      => $row['unit_price'] ?? null,
                'total_amount'    => $row['total_amount'] ?? null,
                'transaction_date'=> $row['transaction_date'] instanceof \DateTimeInterface
                    ? $row['transaction_date']->format('Y-m-d')
                    : (string) $row['transaction_date'],
            ];
        }

        return $previewRows;
    }

    protected function syncLastTransactionDate(array $customerCodes): void
    {
        foreach ($customerCodes as $code) {
            $customer = $this->customerRepo->findByCode($code);
            if (!$customer) {
                continue;
            }
            $latest = $customer->transactions()->max('transaction_date');
            if ($latest) {
                $customer->update(['last_transaction_at' => $latest]);
            }
        }
    }
}