<?php
require_once '../../includes/auth_check.php';
requireRole(['admin','ustad']);

$db   = getDB();
$pageTitle = 'Laporan Keuangan';

$filterTahun = (int)($_GET['tahun'] ?? date('Y'));

// Rekap per bulan tahun ini
$rekapBulan = [];
for ($m = 1; $m <= 12; $m++) {
    $bln = sprintf('%04d-%02d', $filterTahun, $m);
    $masuk = $db->prepare("SELECT COALESCE(SUM(jumlah),0) FROM transaksi_keuangan WHERE jenis='masuk' AND DATE_FORMAT(tanggal,'%Y-%m')=?");
    $masuk->execute([$bln]); $masuk = (int)$masuk->fetchColumn();
    $keluar = $db->prepare("SELECT COALESCE(SUM(jumlah),0) FROM transaksi_keuangan WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')=?");
    $keluar->execute([$bln]); $keluar = (int)$keluar->fetchColumn();
    $rekapBulan[] = ['bulan'=>date('M', mktime(0,0,0,$m,1)),'masuk'=>$masuk,'keluar'=>$keluar,'saldo'=>$masuk-$keluar];
}

// Rekap per kategori
$rekapKat = $db->query("SELECT k.nama, k.jenis, k.ikon, COALESCE(SUM(t.jumlah),0) as total, COUNT(t.id) as jml
    FROM kategori_keuangan k
    LEFT JOIN transaksi_keuangan t ON k.id=t.kategori_id AND YEAR(t.tanggal)=$filterTahun
    GROUP BY k.id ORDER BY k.jenis, total DESC")->fetchAll();

// Rekap sumber
$rekapSumber = $db->query("SELECT sumber, COALESCE(SUM(jumlah),0) as total, COUNT(*) as jml
    FROM transaksi_keuangan WHERE YEAR(tanggal)=$filterTahun
    GROUP BY sumber ORDER BY total DESC")->fetchAll();

// Total tahun
$totalMasukTahun  = array_sum(array_column($rekapBulan,'masuk'));
$totalKeluarTahun = array_sum(array_column($rekapBulan,'keluar'));
$saldoTahun       = $totalMasukTahun - $totalKeluarTahun;

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Laporan Keuangan</h4>
        <p class="page-subtitle">Ringkasan arus kas pesantren per tahun</p>
    </div>
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="form-label mb-0 fw-bold">Tahun:</label>
        <select name="tahun" class="form-select" style="max-width:100px" onchange="this.form.submit()">
            <?php for ($y = date('Y'); $y >= date('Y')-3; $y--): ?>
            <option value="<?= $y ?>" <?= $filterTahun==$y?'selected':''?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<!-- Ringkasan Tahun -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center p-4" style="border-top:4px solid #2E7D32">
            <div style="font-size:12px;color:#888;font-weight:600">TOTAL PEMASUKAN <?= $filterTahun ?></div>
            <div style="font-size:26px;font-weight:800;color:#2E7D32;margin:8px 0">
                Rp <?= number_format($totalMasukTahun,0,',','.') ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-4" style="border-top:4px solid #c62828">
            <div style="font-size:12px;color:#888;font-weight:600">TOTAL PENGELUARAN <?= $filterTahun ?></div>
            <div style="font-size:26px;font-weight:800;color:#c62828;margin:8px 0">
                Rp <?= number_format($totalKeluarTahun,0,',','.') ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-4" style="border-top:4px solid <?= $saldoTahun>=0?'#1565C0':'#c62828' ?>">
            <div style="font-size:12px;color:#888;font-weight:600">SALDO BERSIH <?= $filterTahun ?></div>
            <div style="font-size:26px;font-weight:800;color:<?= $saldoTahun>=0?'#1565C0':'#c62828' ?>;margin:8px 0">
                Rp <?= number_format($saldoTahun,0,',','.') ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Chart Arus Kas -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-chart-line me-2"></i>Arus Kas Bulanan <?= $filterTahun ?></div>
            <div class="card-body">
                <canvas id="chartBulanan" height="120"></canvas>
            </div>
        </div>

        <!-- Tabel Rekap Bulan -->
        <div class="card">
            <div class="card-header"><i class="fas fa-table me-2"></i>Rekap Per Bulan</div>
            <div class="table-responsive">
                <table class="table" style="font-size:13px">
                    <thead><tr><th>Bulan</th><th>Pemasukan</th><th>Pengeluaran</th><th>Saldo</th></tr></thead>
                    <tbody>
                        <?php foreach ($rekapBulan as $r): ?>
                        <tr>
                            <td><strong><?= $r['bulan'] ?></strong></td>
                            <td class="text-success">+Rp <?= number_format($r['masuk'],0,',','.') ?></td>
                            <td class="text-danger">-Rp <?= number_format($r['keluar'],0,',','.') ?></td>
                            <td>
                                <span style="font-weight:700;color:<?= $r['saldo']>=0?'#2E7D32':'#c62828' ?>">
                                    Rp <?= number_format($r['saldo'],0,',','.') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="border-top:2px solid #ddd;background:#f9f9f9">
                            <td><strong>TOTAL</strong></td>
                            <td class="text-success"><strong>+Rp <?= number_format($totalMasukTahun,0,',','.') ?></strong></td>
                            <td class="text-danger"><strong>-Rp <?= number_format($totalKeluarTahun,0,',','.') ?></strong></td>
                            <td><strong style="color:<?= $saldoTahun>=0?'#2E7D32':'#c62828' ?>">Rp <?= number_format($saldoTahun,0,',','.') ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar: Sumber & Kategori -->
    <div class="col-lg-4">
        <!-- Pie chart sumber -->
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-chart-pie me-2"></i>Komposisi Pemasukan</div>
            <div class="card-body">
                <canvas id="chartSumber" height="200"></canvas>
            </div>
        </div>

        <!-- Rekap per sumber -->
        <div class="card">
            <div class="card-header"><i class="fas fa-tags me-2"></i>Per Sumber</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($rekapSumber as $s):
                        $icons=['infaq'=>'fa-hand-holding-heart','spp'=>'fa-money-bill-wave',
                                'pengeluaran'=>'fa-minus-circle','gaji'=>'fa-user-tie','lainnya'=>'fa-circle'];
                        $colors=['infaq'=>'#2E7D32','spp'=>'#1565C0','pengeluaran'=>'#c62828','gaji'=>'#F57F17','lainnya'=>'#555'];
                        $ic = $icons[$s['sumber']] ?? 'fa-circle';
                        $cl = $colors[$s['sumber']] ?? '#555';
                    ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas <?= $ic ?>" style="color:<?= $cl ?>;width:16px"></i>
                            <span style="font-size:13px;font-weight:600"><?= ucfirst($s['sumber']) ?></span>
                            <small class="text-muted">(<?= $s['jml'] ?>x)</small>
                        </div>
                        <span style="font-weight:700;font-size:13px">Rp <?= number_format($s['total'],0,',','.') ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Bar chart bulanan
new Chart(document.getElementById('chartBulanan').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($rekapBulan,'bulan')) ?>,
        datasets: [
            { label:'Pemasukan', data: <?= json_encode(array_column($rekapBulan,'masuk')) ?>, backgroundColor:'rgba(46,125,50,0.75)', borderRadius:5 },
            { label:'Pengeluaran', data: <?= json_encode(array_column($rekapBulan,'keluar')) ?>, backgroundColor:'rgba(198,40,40,0.75)', borderRadius:5 }
        ]
    },
    options: {
        responsive:true,
        plugins:{ legend:{ position:'top' } },
        scales:{ y:{ ticks:{ callback: v => 'Rp'+(v/1000)+'rb' } } }
    }
});

// Pie chart sumber pemasukan
const sumberData = <?= json_encode(array_filter($rekapSumber, fn($s)=>$s['total']>0)) ?>;
new Chart(document.getElementById('chartSumber').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: sumberData.map(s => s.sumber.charAt(0).toUpperCase()+s.sumber.slice(1)),
        datasets:[{ data: sumberData.map(s=>s.total), backgroundColor:['#2E7D32','#1565C0','#c62828','#F57F17','#9E9E9E'] }]
    },
    options: {
        plugins:{ legend:{ position:'bottom' } },
        cutout:'60%'
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>