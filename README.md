# \# Customer Segmentation — RFM \& K-Means



Aplikasi berbasis web untuk membantu analisis dan segmentasi pelanggan menggunakan metode \*\*Recency, Frequency, Monetary (RFM)\*\* dan algoritma \*\*K-Means Clustering\*\*.



\## Overview



Project ini dikembangkan untuk membantu proses analisis data transaksi pelanggan, menghasilkan kelompok pelanggan berdasarkan karakteristik pembelian, serta menyajikan hasil clustering melalui dashboard.



\## Features



\- Import data transaksi pelanggan.

\- Perhitungan metrik RFM.

\- Segmentasi pelanggan menggunakan K-Means.

\- Evaluasi hasil clustering.

\- Visualisasi hasil analisis melalui dashboard.



\## Tech Stack



Sesuaikan daftar ini dengan teknologi yang benar-benar digunakan:



\- \*\*Backend:\*\* Laravel

\- \*\*Machine Learning API:\*\* Python, FastAPI, scikit-learn

\- \*\*Database:\*\* MySQL

\- \*\*Frontend:\*\* Blade, Tailwind CSS

\- \*\*Data Processing:\*\* pandas, NumPy



\## Project Structure



```text

segmentation-cust-apk/

├── app/                  # Laravel application

├── resources/            # Frontend views and assets

├── routes/               # Application routes

├── fastapi-service/      # Machine learning API

├── data/                 # Dataset (jangan unggah data sensitif)

├── tools/                # Development utilities

└── README.md

```



Sesuaikan struktur folder di atas dengan struktur repository yang sebenarnya.



\## Getting Started



\### Prerequisites



\- PHP dan Composer

\- Python dan pip

\- Database MySQL

\- Node.js dan npm jika dibutuhkan oleh frontend



\### Installation



1\. Clone repository.

2\. Install dependency Laravel menggunakan Composer.

3\. Salin `.env.example` menjadi `.env`, lalu atur konfigurasi database.

4\. Jalankan migrasi database jika diperlukan.

5\. Install dependency Python pada layanan FastAPI.

6\. Jalankan Laravel dan FastAPI sesuai konfigurasi project.



\## Project Status



Under development.



\## Author



Developed as an academic project on customer segmentation using RFM and K-Means.

