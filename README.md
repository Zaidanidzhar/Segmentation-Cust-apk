# Customer Segmentation: RFM & K-Means

Aplikasi web untuk segmentasi pelanggan berdasarkan analisis **RFM (Recency, Frequency, Monetary)** dan algoritma **K-Means Clustering**. Admin mengunggah data transaksi, sistem menghitung skor RFM, mengelompokkan pelanggan ke dalam segmen, lalu hasilnya ditampilkan di dashboard.

> Dikembangkan sebagai proyek skripsi, menggunakan data transaksi penjualan toko online (Visscotton, periode Mei 2025 - Juni 2026).

<!-- TODO: tambahkan screenshot dashboard di sini -->
<!-- ![Dashboard](docs/dashboard.png) -->

## Latar Belakang

Pelaku usaha kecil biasanya punya data transaksi, tetapi belum memanfaatkannya untuk mengenali pelanggan. Project ini mengelompokkan pelanggan berdasarkan perilaku belinya sehingga strategi promosi bisa dibedakan per segmen, misalnya mempertahankan pelanggan terbaik dan menghubungi kembali pelanggan yang mulai menjauh.

## Fitur

- Login admin (Laravel Breeze) dengan proteksi middleware role admin
- Unggah dataset transaksi (CSV/XLSX/XLS, maksimal 10 MB)
- Perhitungan metrik RFM per periode bulanan
- Eksperimen jumlah klaster (K = 2 sampai 10) dengan Elbow Method, Silhouette Score, dan Davies-Bouldin Index
- Segmentasi pelanggan dengan K-Means, hasilnya diberi label segmen
- Dashboard hasil: profil tiap segmen, metrik evaluasi model, dan visualisasi klaster
- Halaman publik (landing page) yang menampilkan ringkasan hasil

## Hasil Segmentasi

Dari data uji, pelanggan terbagi ke dalam 4 segmen:

| Segmen | Jumlah Pelanggan | Rata-rata Recency (hari) | Rata-rata Frequency | Rata-rata Monetary (Rp) |
|---|---:|---:|---:|---:|
| Champions | 120 | 68 | 7,09 | 2.996.633 |
| Loyal Customers | 1.425 | 74 | 1,19 | 215.848 |
| At Risk | 1.040 | 249 | 1,11 | 173.931 |
| Hibernating/Churn | 33 | 154 | 1,00 | 0 |

<!-- TODO: ganti angka evaluasi di bawah dengan satu sumber yang konsisten (lihat catatan di bawah) -->
Evaluasi model: K = 4 dipilih berdasarkan Elbow Method, dengan Silhouette Score dan Davies-Bouldin Index yang dilaporkan di dashboard.

## Arsitektur

```
Admin (browser)
      |
      v
Laravel 12 (rfm-segmentation)  --HTTP-->  FastAPI (fastapi-service)
  - autentikasi, upload data                - hitung RFM
  - dashboard, penyimpanan hasil            - K-Means (scikit-learn)
      |                                     - metrik evaluasi
      v
 Database (SQLite / MySQL)
```

Laravel menangani antarmuka dan data, sedangkan proses machine learning dipisahkan ke layanan FastAPI agar bisa dikembangkan dan di-deploy sendiri.

## Tech Stack

| Bagian | Teknologi |
|---|---|
| Web app | Laravel 12, PHP 8.2+, Laravel Breeze, Blade, Tailwind CSS, Alpine.js, Vite |
| ML service | Python, FastAPI, scikit-learn 1.6, pandas, NumPy, joblib |
| Database | SQLite (default), dapat diganti ke MySQL |
| Import data | PhpSpreadsheet |
| Testing | PHPUnit (Feature dan Unit test) |

## Struktur Folder

```
.
├── rfm-segmentation/        # Aplikasi web Laravel
│   ├── app/
│   │   ├── Http/Controllers/Admin/   # Dashboard, Upload, ClusterProcess
│   │   ├── Services/                 # RFM, import dataset, prediksi klaster
│   │   ├── Repositories/             # Pola repository (Eloquent)
│   │   └── Models/                   # Customer, Transaction, CustomerSegment, dll
│   ├── database/migrations/
│   ├── resources/views/              # admin, auth, landing
│   ├── routes/
│   └── tests/
├── fastapi-service/         # API machine learning
│   ├── app/
│   │   ├── main.py                   # Endpoint FastAPI
│   │   ├── ml/predictor.py           # Logika K-Means
│   │   ├── services/rfm_service.py   # Perhitungan RFM
│   │   ├── schemas.py                # Skema request/response
│   │   └── models/                   # Model terlatih dan hasil evaluasi
│   └── requirements.txt
├── data/                    # Dataset contoh
└── docs/                    # Dokumentasi
```

## Endpoint FastAPI

| Method | Path | Fungsi |
|---|---|---|
| GET | `/` | Info layanan |
| GET | `/health` | Cek status layanan |
| GET | `/model-metrics` | Metrik evaluasi model |
| POST | `/generate-rfm` | Hitung RFM dari data transaksi |
| POST | `/experiment-kmeans` | Uji beberapa nilai K |
| POST | `/predict-cluster` | Prediksi segmen pelanggan |

Dokumentasi interaktif otomatis tersedia di `http://127.0.0.1:8001/docs` setelah layanan berjalan.

## Cara Menjalankan

### Prasyarat

- PHP 8.2+ dan Composer
- Node.js dan npm
- Python 3.11+

### 1. Jalankan layanan FastAPI

```bash
cd fastapi-service
python -m venv .venv
source .venv/bin/activate        # Windows: .venv\Scripts\activate
pip install -r requirements.txt
uvicorn app.main:app --port 8001 --reload
```

### 2. Jalankan aplikasi Laravel

```bash
cd rfm-segmentation
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # Windows: type nul > database\database.sqlite
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Pastikan `.env` berisi alamat layanan FastAPI:

```env
FASTAPI_BASE_URL=http://127.0.0.1:8001
FASTAPI_TIMEOUT=30
```

Aplikasi bisa dibuka di `http://127.0.0.1:8000`. Seeder membuat akun admin awal; ganti kata sandinya segera setelah login pertama.

### 3. Alur penggunaan

1. Login sebagai admin
2. Buka menu **Upload**, unggah file transaksi (kolom: nomor pesanan, username pembeli, waktu pesanan selesai, total pembayaran)
3. Buka menu **Process**, jalankan eksperimen K lalu proses klaster
4. Lihat hasilnya di **Dashboard**

## Menjalankan Test

```bash
cd rfm-segmentation
php artisan test
```

## Catatan Pengembangan

- Dataset berisi username pembeli dari marketplace. Untuk repo publik, sebaiknya anonimkan atau ganti dengan data sintetis.
- Status: <!-- TODO: isi "Selesai" atau sebutkan fitur yang belum rampung -->

## Author

**Zaidan Idzharulhaq**
[LinkedIn](https://www.linkedin.com/in/zaidan-idzharulhaq/) | [GitHub](https://github.com/Zaidanidzhar)
