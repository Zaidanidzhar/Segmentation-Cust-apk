<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();

            // Skor RFM mentah
            $table->integer('recency')->comment('Hari sejak transaksi terakhir');
            $table->integer('frequency')->comment('Jumlah total transaksi');
            $table->decimal('monetary', 15, 2)->comment('Total nilai transaksi');

            // Skor RFM ternormalisasi (1-5), opsional tapi berguna utk analisis
            $table->tinyInteger('recency_score')->nullable();
            $table->tinyInteger('frequency_score')->nullable();
            $table->tinyInteger('monetary_score')->nullable();

            // Hasil dari FastAPI (K-Means)
            $table->integer('cluster_label')->nullable()->comment('Label cluster dari K-Means, misal 0,1,2');
            $table->string('segment_name')->nullable()->comment('Champions, Loyal, At Risk, dll');

            $table->timestamp('calculated_at')->nullable(); // kapan proses RFM+cluster terakhir dijalankan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_segments');
    }
};