@extends('layouts.admin')

@section('title', 'Proses Clustering')

@section('content')
<style>
.process-page{max-width:1180px;margin:0 auto}.process-page .process-card{background:#fff;border:1px solid #e6e9e7;border-radius:10px;box-shadow:0 4px 18px rgba(30,35,32,.04)}
.process-page .process-header{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:22px}.process-page h1,.process-page h2,.process-page h3{color:#1f2422}.process-page .eyebrow{font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#6f8f7e}.process-page .muted{color:#7b817e;font-size:12px;line-height:1.6}
.process-page .process-card-head{padding:20px 22px 0}.process-page .process-card-body{padding:20px 22px}.process-page .process-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.process-page .process-form-grid{display:grid;grid-template-columns:1fr 1fr auto;gap:14px;align-items:end}.process-page label{display:block;color:#515a55;font-size:11px;font-weight:700;margin-bottom:7px}.process-page input{width:100%;height:42px;border:1px solid #dfe3e1;border-radius:7px;padding:0 12px;color:#1f2422;background:#fff}.process-page input:focus{outline:0;border-color:#789c89;box-shadow:0 0 0 3px #edf5f1}.process-page .action-btn{height:42px;border:0;border-radius:7px;padding:0 18px;font-size:12px;font-weight:700;cursor:pointer;transition:background .2s,transform .2s}.process-page .action-btn:hover{transform:translateY(-1px)}.process-page .action-primary{background:#303537;color:#fff}.process-page .action-primary:hover{background:#202426}.process-page .action-success{background:#5f8d78;color:#fff}.process-page .action-success:hover{background:#4f7b68}.process-page .action-btn:disabled{opacity:.55;cursor:wait;transform:none}.process-page .status-pill{display:inline-flex;align-items:center;gap:7px;padding:7px 11px;border-radius:999px;background:#edf5f1;color:#35634e;font-size:11px;font-weight:700}.process-page .chart-wrap{height:280px;padding:18px 22px 22px}.process-page .table-wrap{overflow-x:auto}.process-page table{width:100%;border-collapse:collapse;font-size:11px}.process-page th{padding:11px 12px;text-align:left;background:#f7f9f7;color:#7b817e;font-size:10px;text-transform:uppercase;letter-spacing:.04em}.process-page td{padding:12px;border-top:1px solid #eef0ef}.process-page .alert{border-radius:7px;padding:12px 14px;font-size:12px}.process-page .alert-danger{background:#f8eeee;color:#8d4f4f}.process-page .spinner-border{display:inline-block;width:18px;height:18px;border:2px solid #dfe8e3;border-top-color:#5f8d78;border-radius:50%;animation:process-spin .7s linear infinite}.process-page .d-none{display:none!important}@keyframes process-spin{to{transform:rotate(360deg)}}
@media(max-width:700px){.process-page .process-header{display:block}.process-page .process-form-grid,.process-page .process-grid{grid-template-columns:1fr}.process-page .action-btn{width:100%}.process-page .chart-wrap{height:230px;padding-left:10px;padding-right:10px}}
</style>
<div class="process-page">

    {{-- HEADER --}}
    <div class="process-header">
        <div><div class="eyebrow">Model laboratory</div><h1 class="fw-bold mb-1">Proses RFM & K-Means</h1>
        <p class="muted mb-0">
            Eksperimen parameter K dan deployment model final.
        </p></div><span class="status-pill"><span>●</span> Model siap digunakan</span>
    </div>

    {{-- MODEL FINAL --}}
    <div class="process-card mb-4">
        <div class="process-card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">Model deployment</h2>
                    <p class="muted mb-0">
                        Model final menggunakan scaler.pkl dan kmeans.pkl.
                    </p>
                </div>

                <span class="status-pill">
                    K = 4
                </span>
            </div>
        </div>
    </div>

    {{-- EXPERIMENT --}}
    <div class="process-card mb-4">
        <div class="process-card-head">
            <h2 class="fw-bold mb-1">Eksperimen K-Means</h2>
            <p class="muted mb-0">
                Eksperimen digunakan untuk mengevaluasi beberapa nilai K.
                Eksperimen tidak mengubah model deployment K=4.
            </p>
        </div>

        <div class="process-card-body">

            <form id="experimentForm">
                @csrf

                <div class="process-form-grid">

                    <div>
                        <label>
                            K Minimum
                        </label>

                        <input
                            type="number"
                            id="k_min"
                            name="k_min"
                            class="app-input"
                            value="2"
                            min="2"
                            max="10"
                            required
                        >
                    </div>

                    <div>
                        <label>
                            K Maksimum
                        </label>

                        <input
                            type="number"
                            id="k_max"
                            name="k_max"
                            class="app-input"
                            value="10"
                            min="2"
                            max="10"
                            required
                        >
                    </div>

                    <div>
                        <button
                            type="submit"
                            id="experimentButton"
                            class="action-btn action-primary"
                        >
                            Jalankan Eksperimen
                        </button>
                    </div>

                </div>
            </form>

            {{-- LOADING --}}
            <div
                id="experimentLoading"
                class="text-center py-5 d-none"
            >
                <div class="spinner-border mb-3"></div>

                <div class="fw-semibold">
                    Menjalankan eksperimen K-Means...
                </div>

                <small class="text-muted">
                    Mohon tunggu.
                </small>
            </div>

            {{-- ERROR --}}
            <div
                id="experimentError"
                class="alert alert-danger mt-4 d-none"
            ></div>

        </div>
    </div>

    {{-- VISUALISASI --}}
    <div id="visualizationSection" class="d-none">

        <div class="row g-4">

            {{-- ELBOW --}}
            <div class="col-lg-6">
                <div class="process-card h-100">

                    <div class="process-card-head">
                        <h2 class="fw-bold mb-1">
                            Elbow Method
                        </h5>

                        <p class="muted mb-0">
                            Hubungan jumlah cluster (K) dengan WCSS / inertia.
                        </p>
                    </div>

                    <div class="chart-wrap">
                        <canvas id="elbowChart"></canvas>
                    </div>

                </div>
            </div>

            {{-- EUCLIDEAN --}}
            <div class="col-lg-6">
                <div class="process-card h-100">

                    <div class="process-card-head">
                        <h2 class="fw-bold mb-1">
                            K-Distance Euclidean
                        </h5>

                        <p class="muted mb-0">
                            Rata-rata jarak Euclidean setiap eksperimen K.
                        </p>
                    </div>

                    <div class="chart-wrap">
                        <canvas id="euclideanChart"></canvas>
                    </div>

                </div>
            </div>

        </div>

    </div>

    {{-- HASIL EKSPERIMEN --}}
    <div
        id="experimentResult"
        class="process-card mt-4 d-none"
    >

        <div class="process-card-head">
            <h2 class="fw-bold mb-1">
                Hasil Eksperimen
            </h5>

            <p class="muted mb-0">
                Perbandingan metrik setiap nilai K.
            </p>
        </div>

        <div class="process-card-body">

            <div class="table-wrap">

                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>K</th>
                            <th>WCSS / Inertia</th>
                            <th>Silhouette</th>
                            <th>Davies-Bouldin</th>
                            <th>Avg Euclidean</th>
                            <th>Max Euclidean</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody id="experimentTableBody"></tbody>

                </table>

            </div>

        </div>

    </div>

    {{-- DEPLOYMENT --}}
    <div class="process-card mt-4">

        <div class="process-card-body">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                        <h2 class="fw-bold mb-1">
                        Deployment Model
                    </h5>

                    <p class="muted mb-0">
                        Gunakan model final K=4 untuk melakukan segmentasi
                        pelanggan.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('admin.process-cluster') }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="action-btn action-success"
                    >
                        Jalankan Deployment K=4
                    </button>
                </form>

            </div>

        </div>

    </div>

</div>
@endsection


@push('scripts')

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

let elbowChart = null;
let euclideanChart = null;


/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function numberFormat(value, decimals = 4)
{
    if (value === null || value === undefined || isNaN(value)) {
        return '-';
    }

    return Number(value).toFixed(decimals);
}


/*
|--------------------------------------------------------------------------
| Destroy Existing Charts
|--------------------------------------------------------------------------
*/

function destroyCharts()
{
    if (elbowChart) {
        elbowChart.destroy();
        elbowChart = null;
    }

    if (euclideanChart) {
        euclideanChart.destroy();
        euclideanChart = null;
    }
}


/*
|--------------------------------------------------------------------------
| Render Elbow Chart
|--------------------------------------------------------------------------
*/

function renderElbowChart(results)
{
    const labels = results.map(item => item.k);

    const inertia = results.map(item => item.inertia);

    const ctx = document
        .getElementById('elbowChart')
        .getContext('2d');

    elbowChart = new Chart(ctx, {

        type: 'line',

        data: {
            labels: labels,

            datasets: [{
                label: 'WCSS / Inertia',

                data: inertia,

                tension: 0.25,

                pointRadius: 5,

                pointHoverRadius: 7,

                fill: false
            }]
        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                x: {
                    title: {
                        display: true,
                        text: 'Jumlah Cluster (K)'
                    }
                },

                y: {
                    title: {
                        display: true,
                        text: 'WCSS / Inertia'
                    },

                    beginAtZero: true
                }
            },

            plugins: {

                legend: {
                    display: true
                },

                tooltip: {
                    callbacks: {

                        label: function(context)
                        {
                            return 'WCSS: ' +
                                numberFormat(context.raw, 4);
                        }

                    }
                }

            }

        }

    });
}


/*
|--------------------------------------------------------------------------
| Render Euclidean Chart
|--------------------------------------------------------------------------
*/

function renderEuclideanChart(results)
{
    const labels = results.map(item => item.k);

    const distances = results.map(
        item => item.average_euclidean_distance
    );

    const ctx = document
        .getElementById('euclideanChart')
        .getContext('2d');

    euclideanChart = new Chart(ctx, {

        type: 'line',

        data: {
            labels: labels,

            datasets: [{
                label: 'Average Euclidean Distance',

                data: distances,

                tension: 0.25,

                pointRadius: 5,

                pointHoverRadius: 7,

                fill: false
            }]
        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                x: {
                    title: {
                        display: true,
                        text: 'Jumlah Cluster (K)'
                    }
                },

                y: {
                    title: {
                        display: true,
                        text: 'Average Euclidean Distance'
                    },

                    beginAtZero: true
                }
            },

            plugins: {

                legend: {
                    display: true
                },

                tooltip: {
                    callbacks: {

                        label: function(context)
                        {
                            return 'Distance: ' +
                                numberFormat(context.raw, 4);
                        }

                    }
                }

            }

        }

    });
}


/*
|--------------------------------------------------------------------------
| Render Experiment Table
|--------------------------------------------------------------------------
*/

function renderExperimentTable(results)
{
    const tbody = document.getElementById(
        'experimentTableBody'
    );

    tbody.innerHTML = '';

    results.forEach(item => {

        const isFinalModel = Number(item.k) === 4;

        const row = document.createElement('tr');

        if (isFinalModel) {
            row.classList.add('table-success');
        }

        row.innerHTML = `

            <td>
                <strong>K = ${item.k}</strong>
            </td>

            <td>
                ${numberFormat(item.inertia)}
            </td>

            <td>
                ${numberFormat(item.silhouette_score)}
            </td>

            <td>
                ${numberFormat(item.davies_bouldin_index)}
            </td>

            <td>
                ${numberFormat(
                    item.average_euclidean_distance
                )}
            </td>

            <td>
                ${numberFormat(
                    item.maximum_euclidean_distance
                )}
            </td>

            <td>
                ${
                    isFinalModel
                    ? '<span class="badge bg-success">Final K</span>'
                    : ''
                }
            </td>

        `;

        tbody.appendChild(row);

    });
}


/*
|--------------------------------------------------------------------------
| Run Experiment
|--------------------------------------------------------------------------
*/

document
    .getElementById('experimentForm')
    .addEventListener('submit', async function(event)
    {
        event.preventDefault();

        const kMin = Number(
            document.getElementById('k_min').value
        );

        const kMax = Number(
            document.getElementById('k_max').value
        );

        const loading = document.getElementById(
            'experimentLoading'
        );

        const errorBox = document.getElementById(
            'experimentError'
        );

        const visualization = document.getElementById(
            'visualizationSection'
        );

        const resultSection = document.getElementById(
            'experimentResult'
        );

        const button = document.getElementById(
            'experimentButton'
        );


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        errorBox.classList.add('d-none');

        if (kMin < 2 || kMax > 10 || kMin > kMax) {

            errorBox.textContent =
                'Rentang K harus antara 2 sampai 10.';

            errorBox.classList.remove('d-none');

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Loading
        |--------------------------------------------------------------------------
        */

        loading.classList.remove('d-none');

        button.disabled = true;

        visualization.classList.add('d-none');

        resultSection.classList.add('d-none');


        try {

            const response = await fetch(
                "{{ route('admin.experiment-cluster') }}",
                {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'X-CSRF-TOKEN':
                            document
                                .querySelector(
                                    'input[name="_token"]'
                                )
                                .value
                    },

                    body: JSON.stringify({
                        k_min: kMin,
                        k_max: kMax
                    })
                }
            );


            const data = await response.json();


            if (!response.ok) {

                throw new Error(
                    data.message ||
                    'Eksperimen gagal dijalankan.'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Ambil hasil eksperimen
            |--------------------------------------------------------------------------
            */

            const results =
                data.results ||
                data.experiments ||
                [];


            if (!results.length) {

                throw new Error(
                    'Tidak ada hasil eksperimen yang dikembalikan.'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Render
            |--------------------------------------------------------------------------
            */

            destroyCharts();

            renderElbowChart(results);

            renderEuclideanChart(results);

            renderExperimentTable(results);


            visualization.classList.remove('d-none');

            resultSection.classList.remove('d-none');


        } catch (error) {

            errorBox.textContent =
                error.message ||
                'Terjadi kesalahan ketika menjalankan eksperimen.';

            errorBox.classList.remove('d-none');

        } finally {

            loading.classList.add('d-none');

            button.disabled = false;

        }

    });

</script>

<style>

#elbowChart,
#euclideanChart {
    min-height: 350px;
}

.card {
    border-radius: 12px;
}

</style>

@endpush
