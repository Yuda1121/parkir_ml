<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analisis Parkir - RF vs SVR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #f0f4f8; font-family: 'Inter', sans-serif; }
        .stat-card { border: none; border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,0.07); transition: transform .2s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-value { font-size: 1.9rem; font-weight: 800; line-height: 1.1; }
        .chart-card { border: none; border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,0.07); }
        .algo-tab { cursor: pointer; border-radius: 8px; padding: 6px 18px; font-size: 13px; font-weight: 500; border: 1.5px solid transparent; transition: all .2s; }
        .algo-tab.rf-active   { background: #0d6efd; color: #fff; border-color: #0d6efd; }
        .algo-tab.svr-active  { background: #6f42c1; color: #fff; border-color: #6f42c1; }
        .algo-tab.rf-idle     { background: #fff; color: #0d6efd; border-color: #0d6efd; }
        .algo-tab.svr-idle    { background: #fff; color: #6f42c1; border-color: #6f42c1; }
        .metric-table td, .metric-table th { font-size: 13px; padding: 6px 10px; }
        .badge-rf  { background: #0d6efd; color: #fff; font-size: 11px; border-radius: 20px; padding: 2px 10px; }
        .badge-svr { background: #6f42c1; color: #fff; font-size: 11px; border-radius: 20px; padding: 2px 10px; }
        .badge-ai  { background: linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; font-size:0.7rem; }
        .r2-bar-wrap { background: #e9ecef; border-radius: 4px; height: 7px; margin-top: 3px; }
        .r2-bar { height: 7px; border-radius: 4px; }
        #api-status { font-size: .8rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary px-4 py-2 mb-4">
    <span class="navbar-brand mb-0 h5">
        <i class="bi bi-p-circle-fill me-2"></i>Sistem Parkir Cerdas
        <span class="badge badge-ai ms-2">RF &amp; SVR</span>
    </span>
    <span id="api-status" class="text-white-50 small">
        <i class="bi bi-circle-fill me-1" style="font-size:.6rem"></i>Menghubungkan...
    </span>
</nav>

<div class="container-fluid px-4">

    <!-- Toggle Algoritma -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <span class="fw-semibold text-secondary small">Tampilkan prediksi:</span>
        <span class="algo-tab rf-active"  id="tab-rf"  onclick="switchAlgo('rf')">
            <i class="bi bi-tree-fill me-1"></i>Random Forest
        </span>
        <span class="algo-tab svr-idle" id="tab-svr" onclick="switchAlgo('svr')">
            <i class="bi bi-bezier2 me-1"></i>SVR
        </span>
        <span class="algo-tab" id="tab-both"
              style="background:#fff;border:1.5px solid #198754;color:#198754"
              onclick="switchAlgo('both')">
            <i class="bi bi-intersect me-1"></i>Bandingkan
        </span>
    </div>

    <!-- Statistik Harian -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card card p-3 h-100" style="background:linear-gradient(135deg,#0d6efd,#0b5ed7);color:white">
                <div style="font-size:12px;opacity:.85"><i class="bi bi-car-front-fill me-1"></i>Total Kendaraan (<span id="stat-hari"><?= $hari ?? 'Senin' ?></span>)</div>
                <div class="stat-value mt-1" id="stat-total">-</div>
                <small class="opacity-75">Mobil: <span id="stat-mobil-total">-</span> | Motor: <span id="stat-motor-total">-</span></small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card card p-3 h-100 bg-dark text-white">
                <div style="font-size:12px;opacity:.85"><i class="bi bi-clock-fill me-1"></i>Jam Puncak (WIB)</div>
                <div class="stat-value mt-1" id="stat-puncak">-</div>
                <small class="opacity-75">Prediksi kepadatan tertinggi</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card card p-3 h-100 bg-secondary text-white">
                <div style="font-size:12px;opacity:.85"><i class="bi bi-bar-chart-fill me-1"></i>Rata-rata / Jam</div>
                <div class="stat-value mt-1" id="stat-rata">-</div>
                <small class="opacity-75">Kendaraan masuk</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card card p-3 h-100 bg-white">
                <div style="font-size:12px;color:#888"><i class="bi bi-lightbulb-fill me-1 text-warning"></i>Rekomendasi AI</div>
                <p class="small mb-0 mt-2 fw-semibold" id="stat-rekomendasi">Memuat...</p>
            </div>
        </div>
    </div>

    <!-- Grafik + Pie -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="chart-card card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Pola Kunjungan 24 Jam</h6>
                    <div id="chart-legend" class="small text-muted"></div>
                </div>
                <div style="height:320px"><canvas id="lineChart"></canvas></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-card card p-4 h-100">
                <h6 class="fw-bold mb-1"><i class="bi bi-pie-chart-fill me-2 text-warning"></i>Prediksi Jam <span id="label-jam-wib">08:00 WIB</span></h6>
                <div class="row text-center my-3" id="det-section">
                    <!-- diisi JS -->
                </div>
                <div style="height:200px"><canvas id="pieChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Metrik Evaluasi -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="chart-card card p-4">
                <h6 class="fw-bold mb-3"><span class="badge-rf me-2">RF</span>Random Forest — Metrik Evaluasi</h6>
                <table class="table table-sm metric-table mb-0">
                    <thead class="table-light">
                        <tr><th>Kendaraan</th><th>MAE</th><th>RMSE</th><th>R² <small class="text-muted">(akurasi)</small></th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="bi bi-car-front text-primary me-1"></i>Mobil</td>
                            <td>1.33</td><td>1.75</td>
                            <td>
                                <span class="fw-bold text-primary">0.567</span>
                                <div class="r2-bar-wrap"><div class="r2-bar bg-primary" style="width:56.7%"></div></div>
                            </td>
                        </tr>
                        <tr>
                            <td><i class="bi bi-bicycle text-warning me-1"></i>Motor</td>
                            <td>3.52</td><td>4.96</td>
                            <td>
                                <span class="fw-bold text-warning">0.322</span>
                                <div class="r2-bar-wrap"><div class="r2-bar bg-warning" style="width:32.2%"></div></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card card p-4">
                <h6 class="fw-bold mb-3"><span class="badge-svr me-2">SVR</span>Support Vector Regression — Metrik Evaluasi</h6>
                <table class="table table-sm metric-table mb-0">
                    <thead class="table-light">
                        <tr><th>Kendaraan</th><th>MAE</th><th>RMSE</th><th>R² <small class="text-muted">(akurasi)</small></th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="bi bi-car-front text-primary me-1"></i>Mobil</td>
                            <td>1.72</td><td>2.49</td>
                            <td>
                                <span class="fw-bold text-purple" style="color:#6f42c1">0.126</span>
                                <div class="r2-bar-wrap"><div class="r2-bar" style="width:12.6%;background:#6f42c1"></div></div>
                            </td>
                        </tr>
                        <tr>
                            <td><i class="bi bi-bicycle text-warning me-1"></i>Motor</td>
                            <td>4.77</td><td>5.40</td>
                            <td>
                                <span class="fw-bold" style="color:#6f42c1">0.198</span>
                                <div class="r2-bar-wrap"><div class="r2-bar" style="width:19.8%;background:#6f42c1"></div></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="alert alert-info py-2 px-3 mt-3 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i>
                    R² RF lebih tinggi → <strong>Random Forest lebih akurat</strong> untuk dataset ini.
                </div>
            </div>
        </div>
    </div>

    <!-- Form Input -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-10">
            <div class="chart-card card p-4" style="border-left:5px solid #0d6efd!important">
                <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-sliders me-2"></i>Update Prediksi</h6>
                <div class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Pilih Hari</label>
                        <select id="inputHari" class="form-select form-select-lg border-primary">
                            <option value="senin">Senin</option>
                            <option value="selasa">Selasa</option>
                            <option value="rabu">Rabu</option>
                            <option value="kamis">Kamis</option>
                            <option value="jumat">Jumat</option>
                            <option value="sabtu">Sabtu</option>
                            <option value="minggu">Minggu</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Pilih Waktu (WIB)</label>
                        <select id="inputJam" class="form-select form-select-lg border-primary">
                            <?php for ($h = 0; $h < 24; $h++): ?>
                                <option value="<?= $h ?>" <?= ($h == 8) ? 'selected' : '' ?>>
                                    <?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00 WIB
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button id="btnUpdate" class="btn btn-primary btn-lg w-100 fw-bold">
                            <i class="bi bi-cpu me-1"></i>UPDATE PREDIKSI
                        </button>
                    </div>
                </div>
                <div id="error-msg" class="alert alert-danger mt-3 d-none"></div>
            </div>
        </div>
    </div>

</div><!-- /container -->

<script>
let lineChart, pieChart;
let currentAlgo = 'rf';
let lastData = null;

$(document).ready(function() {
    checkApiStatus();
    loadData('senin', 8);
});

function checkApiStatus() {
    fetch('http://localhost:8000/docs', { method: 'HEAD' })
        .then(() => $('#api-status').html('<i class="bi bi-circle-fill me-1" style="color:#22c55e;font-size:.6rem"></i><span style="color:#22c55e">API Python Aktif</span>'))
        .catch(() => $('#api-status').html('<i class="bi bi-circle-fill me-1" style="color:#ef4444;font-size:.6rem"></i><span style="color:#ef4444">API Python Offline</span>'));
}

function switchAlgo(algo) {
    currentAlgo = algo;
    // Update tab style
    $('#tab-rf').removeClass('rf-active rf-idle').addClass(algo==='rf'||algo==='both' ? 'rf-active' : 'rf-idle');
    $('#tab-svr').removeClass('svr-active svr-idle').addClass(algo==='svr'||algo==='both' ? 'svr-active' : 'svr-idle');
    $('#tab-both').css({'background': algo==='both'?'#198754':'#fff', 'color': algo==='both'?'#fff':'#198754'});

    if (lastData) renderAll(lastData);
}

function loadData(hari, jam) {
    $.ajax({
        // Tembak LANGSUNG ke rute /dashboard di server Python lu
        url: 'https://ai-parkir.prayudabowono.my.id/dashboard', 
        method: 'POST',
        // FastAPI WAJIB pakai format JSON asli, bukan Form Data
        contentType: 'application/json', 
        data: JSON.stringify({ 
            "hari": hari, 
            "jam": jam 
        }),
        dataType: 'json',
        success: function(res) {
            if (res.status !== 'success') { showError(res.message || 'Gagal'); return; }
            lastData = res;
            renderAll(res);
        },
        error: function(xhr, s, err) { showError('Gagal konek server AI. ' + err); }
    });
}

function renderAll(res) {
    const algo = currentAlgo;
    const rf   = res.rf;
    const svr  = res.svr;

    // Tentukan data aktif untuk stat cards
    const active = algo === 'svr' ? svr : rf;

    // Update stat cards
    $('#stat-hari').text(res.hari);
    $('#stat-total').text(new Intl.NumberFormat('id-ID').format(active.total_hari));
    $('#stat-mobil-total').text(active.total_mobil);
    $('#stat-motor-total').text(active.total_motor);
    $('#stat-puncak').text(active.jam_puncak);
    $('#stat-rata').text(active.rata_rata);
    $('#stat-rekomendasi').text(res.rekomendasi);

    // Update jam label
    $('#label-jam-wib').text(res.jam_label);

    // Detail jam (RF vs SVR panel)
    if (algo === 'both') {
        $('#det-section').html(`
            <div class="col-6 border-end">
                <small class="badge-rf">RF</small>
                <h3 class="text-primary fw-bold mb-0 mt-1">${rf.mobil}</h3>
                <p class="text-muted small mb-1">Mobil</p>
                <h3 class="text-warning fw-bold mb-0">${rf.motor}</h3>
                <p class="text-muted small mb-0">Motor</p>
            </div>
            <div class="col-6">
                <small class="badge-svr">SVR</small>
                <h3 style="color:#6f42c1" class="fw-bold mb-0 mt-1">${svr.mobil}</h3>
                <p class="text-muted small mb-1">Mobil</p>
                <h3 class="text-warning fw-bold mb-0">${svr.motor}</h3>
                <p class="text-muted small mb-0">Motor</p>
            </div>
        `);
    } else {
        const d = algo === 'svr' ? svr : rf;
        const c = algo === 'svr' ? '#6f42c1' : '#0d6efd';
        const badge = algo === 'svr' ? '<small class="badge-svr">SVR</small>' : '<small class="badge-rf">RF</small>';
        $('#det-section').html(`
            <div class="col-6 border-end">
                ${badge}
                <h2 style="color:${c}" class="fw-bold mb-0 mt-1">${d.mobil}</h2>
                <p class="text-muted small">Mobil</p>
            </div>
            <div class="col-6">
                ${badge}
                <h2 class="text-warning fw-bold mb-0 mt-1">${d.motor}</h2>
                <p class="text-muted small">Motor</p>
            </div>
        `);
    }

    // Update grafik
    updateCharts(res, algo);

    // Update pie
    const pm = algo === 'svr' ? svr.mobil : rf.mobil;
    const pmo = algo === 'svr' ? svr.motor : rf.motor;
    pieChart.data.datasets[0].data = [pm, pmo];
    pieChart.data.datasets[0].backgroundColor = algo === 'svr' ? ['#6f42c1','#ffc107'] : ['#0d6efd','#ffc107'];
    pieChart.update();
}

function updateCharts(res, algo) {
    const labels = res.chart_labels;
    if (!lineChart) {
        initCharts(res, algo);
        return;
    }

    if (algo === 'both') {
        lineChart.data.datasets = [
            { label: 'RF Mobil',  data: res.rf.chart_mobil,  borderColor:'#0d6efd', backgroundColor:'rgba(13,110,253,.08)', fill:true, tension:.4, pointRadius:2 },
            { label: 'RF Motor',  data: res.rf.chart_motor,  borderColor:'#ffc107', backgroundColor:'rgba(255,193,7,.08)',   fill:true, tension:.4, pointRadius:2 },
            { label: 'SVR Mobil', data: res.svr.chart_mobil, borderColor:'#6f42c1', backgroundColor:'rgba(111,66,193,.06)', fill:false, tension:.4, pointRadius:2, borderDash:[5,3] },
            { label: 'SVR Motor', data: res.svr.chart_motor, borderColor:'#e83e8c', backgroundColor:'rgba(232,62,140,.06)', fill:false, tension:.4, pointRadius:2, borderDash:[5,3] },
        ];
        $('#chart-legend').html('<span style="color:#6f42c1">-- SVR</span> &nbsp; <span style="color:#0d6efd">— RF</span>');
    } else {
        const d = algo === 'svr' ? res.svr : res.rf;
        const c1 = algo === 'svr' ? '#6f42c1' : '#0d6efd';
        const c2 = '#ffc107';
        lineChart.data.datasets = [
            { label: 'Mobil', data: d.chart_mobil, borderColor:c1, backgroundColor:`${c1}18`, fill:true, tension:.4, pointRadius:2 },
            { label: 'Motor', data: d.chart_motor, borderColor:c2, backgroundColor:`${c2}18`, fill:true, tension:.4, pointRadius:2 },
        ];
        const badge = algo === 'svr' ? '<span class="badge-svr me-1">SVR</span>' : '<span class="badge-rf me-1">RF</span>';
        $('#chart-legend').html(badge + 'aktif');
    }
    lineChart.data.labels = labels;
    lineChart.update();
}

function initCharts(res, algo) {
    const ctxLine = document.getElementById('lineChart').getContext('2d');
    lineChart = new Chart(ctxLine, {
        type: 'line',
        data: { labels: res.chart_labels, datasets: [] },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { position:'bottom', labels:{ boxWidth:12, font:{size:11} } } },
            scales: {
                y: { beginAtZero:true, grid:{ color:'rgba(0,0,0,.04)' } },
                x: { grid:{ display:false } }
            }
        }
    });

    const ctxPie = document.getElementById('pieChart').getContext('2d');
    pieChart = new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ['Mobil','Motor'],
            datasets: [{ data:[0,0], backgroundColor:['#0d6efd','#ffc107'], borderWidth:0 }]
        },
        options: { maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } }, cutout:'65%' }
    });

    updateCharts(res, algo);
    const pm = res.rf.mobil, pmo = res.rf.motor;
    pieChart.data.datasets[0].data = [pm, pmo];
    pieChart.update();
}

$('#btnUpdate').on('click', function() {
    const hari = $('#inputHari').val();
    const jam  = parseInt($('#inputJam').val());
    const btn  = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>MEMPROSES...');
    $('#error-msg').addClass('d-none');
    loadData(hari, jam);
    setTimeout(() => btn.prop('disabled', false).html('<i class="bi bi-cpu me-1"></i>UPDATE PREDIKSI'), 1500);
});

function showError(msg) {
    $('#error-msg').removeClass('d-none').text('⚠️ ' + msg);
}
</script>
</body>
</html>
