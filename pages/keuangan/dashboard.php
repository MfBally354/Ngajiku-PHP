<?php
require_once '../../includes/auth_check.php';
requireRole(['admin','ustad']);

$db   = getDB();
$uid  = $user['id'];
$role = $user['role'];
$pageTitle = 'Keuangan';

// Saldo total
$totalMasuk  = $db->query("SELECT COALESCE(SUM(jumlah),0) FROM transaksi_keuangan WHERE jenis='masuk'")->fetchColumn();
$totalKeluar = $db->query("SELECT COALESCE(SUM(jumlah),0) FROM transaksi_keuangan WHERE jenis='keluar'")->fetchColumn();
$saldo       = $totalMasuk - $totalKeluar;

// Bulan ini
$bulanIni = date('Y-m');
$masukBulanIni  = $db->prepare("SELECT COALESCE(SUM(jumlah),0) FROM transaksi_keuangan WHERE jenis='masuk' AND DATE_FORMAT(tanggal,'%Y-%m')=?");
$masukBulanIni->execute([$bulanIni]); $masukBulanIni = $masukBulanIni->fetchColumn();

$keluarBulanIni = $db->prepare("SELECT COALESCE(SUM(jumlah),0) FROM transaksi_keuangan WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')=?");
$keluarBulanIni->execute([$bulanIni]); $keluarBulanIni = $keluarBulanIni->fetchColumn();

$infaqBulanIni  = $db->prepare("SELECT COALESCE(SUM(jumlah),0) FROM transaksi_keuangan WHERE sumber='infaq' AND DATE_FORMAT(tanggal,'%Y-%m')=?");
$infaqBulanIni->execute([$bulanIni]); $infaqBulanIni = $infaqBulanIni->fetchColumn();

$sppBulanIni    = $db->prepare("SELECT COALESCE(SUM(jumlah),0) FROM transaksi_keuangan WHERE sumber='spp' AND DATE_FORMAT(tanggal,'%Y-%m')=?");
$sppBulanIni->execute([$bulanIni]); $sppBulanIni = $sppBulanIni->fetchColumn();

// Gaji pending
$gajiPending = $db->query("SELECT COUNT(*) FROM gaji_ustad WHERE status='pending'")->fetchColumn();

// SPP belum lunas bulan ini
$sppBelum = $db->prepare("SELECT COUNT(*) FROM tagihan_spp WHERE DATE_FORMAT(bulan,'%Y-%m')=? AND status='belum'");
$sppBelum->execute([$bulanIni]); $sppBelum = $sppBelum->fetchColumn();

// Transaksi terbaru
$transaksiTerbaru = $db->query("SELECT t.*, u.nama as pencatat, k.nama as nama_kat
    FROM transaksi_keuangan t
    JOIN users u ON t.dicatat_oleh=u.id
    LEFT JOIN kategori_keuangan k ON t.kategori_id=k.id
    ORDER BY t.created_at DESC LIMIT 8")->fetchAll();

// Data chart 6 bulan terakhir
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $bln = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $m = $db->prepare("SELECT COALESCE(SUM(jumlah),0) FROM transaksi_keuangan WHERE jenis='masuk' AND DATE_FORMAT(tanggal,'%Y-%m')=?");
    $m->execute([$bln]); $m = (int)$m->fetchColumn();
    $k2 = $db->prepare("SELECT COALESCE(SUM(jumlah),0) FROM transaksi_keuangan WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')=?");
    $k2->execute([$bln]); $k2 = (int)$k2->fetchColumn();
    $chartData[] = ['label'=>$label,'masuk'=>$m,'keluar'=>$k2];
}

require_once '../../includes/header.php';
?>

<div class="welcome-banner">
    <div class="welcome-title">💰 Keuangan NgajiKu</div>
    <div class="welcome-sub">Kelola keuangan pesantren dengan transparan — <?= date('d F Y') ?></div>
</div>

<!-- Saldo Utama -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center p-4" style="border-top:4px solid #2E7D32">
            <div style="font-size:13px;color:#888;margin-bottom:6px">SALDO TOTAL</div>
            <div style="font-size:32px;font-weight:800;color:<?= $saldo>=0?'#2E7D32':'#c62828' ?>">
                Rp <?= number_format($saldo,0,',','.') ?>
            </div>
            <div style="font-size:12px;color:#aaa;margin-top:4px">Semua waktu</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-4" style="border-top:4px solid #1565C0">
            <div style="font-size:13px;color:#888;margin-bottom:6px">PEMASUKAN BULAN INI</div>
            <div style="font-size:26px;font-weight:800;color:#1565C0">
                Rp <?= number_format($masukBulanIni,0,',','.') ?>
            </div>
            <div style="font-size:12px;color:#aaa;margin-top:4px"><?= date('F Y') ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-4" style="border-top:4px solid #c62828">
            <div style="font-size:13px;color:#888;margin-bottom:6px">PENGELUARAN BULAN INI</div>
            <div style="font-size:26px;font-weight:800;color:#c62828">
                Rp <?= number_format($keluarBulanIni,0,',','.') ?>
            </div>
            <div style="font-size:12px;color:#aaa;margin-top:4px"><?= date('F Y') ?></div>
        </div>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-hand-holding-heart"></i></div>
            <div><div class="stat-value">Rp <?= number_format($infaqBulanIni/1000,0,',','.')?>rb</div><div class="stat-label">Infaq Bulan Ini</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-money-bill-wave"></i></div>
            <div><div class="stat-value">Rp <?= number_format($sppBulanIni/1000,0,',','.')?>rb</div><div class="stat-label">SPP Bulan Ini</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon <?= $gajiPending>0?'orange':'green' ?>"><i class="fas fa-user-tie"></i></div>
            <div><div class="stat-value"><?= $gajiPending ?></div><div class="stat-label">Gaji Pending</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon <?= $sppBelum>0?'red':'green' ?>"><i class="fas fa-file-invoice-dollar"></i></div>
            <div><div class="stat-value"><?= $sppBelum ?></div><div class="stat-label">SPP Belum Lunas</div></div>
        </div>
    </div>
</div>

<?php if ($role === 'admin'): ?>
<!-- Aksi Cepat (admin only) -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fas fa-bolt me-2 text-warning"></i>Aksi Cepat</div>
            <div class="card-body d-flex flex-wrap gap-2 p-3">
                <a href="transaksi.php?act=tambah&sumber=infaq" class="btn-primary-green">
                    <i class="fas fa-hand-holding-heart"></i>Catat Infaq
                </a>
                <a href="transaksi.php?act=tambah&sumber=spp" class="btn-outline-green">
                    <i class="fas fa-money-bill-wave"></i>Bayar SPP
                </a>
                <a href="transaksi.php?act=tambah&sumber=pengeluaran" class="btn-outline-green">
                    <i class="fas fa-minus-circle"></i>Catat Pengeluaran
                </a>
                <a href="gaji.php" class="btn-outline-green">
                    <i class="fas fa-user-tie"></i>Kelola Gaji
                </a>
                <a href="laporan.php" class="btn-outline-green">
                    <i class="fas fa-chart-bar"></i>Laporan
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Chart -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Arus Kas 6 Bulan Terakhir</div>
            <div class="card-body">
                <canvas id="chartKeuangan" height="120"></canvas>
            </div>
        </div>
    </div>

    <!-- Transaksi Terbaru -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list me-2"></i>Transaksi Terbaru</span>
                <a href="transaksi.php" class="btn-outline-green" style="padding:3px 10px;font-size:12px">Semua</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($transaksiTerbaru)): ?>
                <div class="empty-state py-4"><p>Belum ada transaksi</p></div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($transaksiTerbaru as $t): ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <div style="font-size:13px;font-weight:600"><?= sanitize($t['keterangan']) ?></div>
                            <small class="text-muted"><?= sanitize($t['nama_kat'] ?? ucfirst($t['sumber'])) ?> · <?= formatTanggal($t['tanggal']) ?></small>
                        </div>
                        <span style="font-weight:700;color:<?= $t['jenis']==='masuk'?'#2E7D32':'#c62828' ?>;font-size:13px;white-space:nowrap">
                            <?= $t['jenis']==='masuk'?'+':'-' ?>Rp <?= number_format($t['jumlah']/1000,0,',','.')?>rb
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('chartKeuangan').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($chartData,'label')) ?>,
        datasets: [
            {
                label: 'Pemasukan',
                data: <?= json_encode(array_column($chartData,'masuk')) ?>,
                backgroundColor: 'rgba(46,125,50,0.7)',
                borderRadius: 6
            },
            {
                label: 'Pengeluaran',
                data: <?= json_encode(array_column($chartData,'keluar')) ?>,
                backgroundColor: 'rgba(198,40,40,0.7)',
                borderRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: {
                ticks: {
                    callback: val => 'Rp ' + (val/1000).toLocaleString('id') + 'rb'
                }
            }
        }
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>