<?php
// pages/santri/absensi.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('santri');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Absensi Saya';

$bulan = $_GET['bulan'] ?? date('Y-m');

$stmt = $db->prepare("SELECT a.*, k.nama_kelas FROM absensi a JOIN kelas k ON a.kelas_id=k.id WHERE a.santri_id=? AND DATE_FORMAT(a.tanggal,'%Y-%m')=? ORDER BY a.tanggal DESC");
$stmt->execute([$uid,$bulan]);
$absensiList = $stmt->fetchAll();

$rekap = [];
foreach ($absensiList as $a) {
    $rekap[$a['status']] = ($rekap[$a['status']] ?? 0) + 1;
}

$total   = count($absensiList);
$pctHadir = $total ? round(($rekap['hadir']??0)/$total*100) : 0;

require_once '../../includes/header.php';
?>

<h4 class="page-title mb-1">Absensi Saya</h4>
<p class="page-subtitle mb-4">Rekap kehadiran di setiap kelas</p>

<div class="card mb-3"><div class="card-body p-3">
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="form-label mb-0 fw-bold">Bulan:</label>
        <input type="month" name="bulan" class="form-control" style="max-width:180px" value="<?= $bulan ?>" onchange="this.form.submit()">
    </form>
</div></div>

<!-- Rekap -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div><div class="stat-value"><?= $rekap['hadir'] ?? 0 ?></div><div class="stat-label">Hadir</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
            <div><div class="stat-value"><?= $rekap['izin'] ?? 0 ?></div><div class="stat-label">Izin</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-hospital"></i></div>
            <div><div class="stat-value"><?= $rekap['sakit'] ?? 0 ?></div><div class="stat-label">Sakit</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
            <div><div class="stat-value"><?= $rekap['alpha'] ?? 0 ?></div><div class="stat-label">Alpha</div></div>
        </div>
    </div>
</div>

<?php if ($total): ?>
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between mb-2">
        <span style="font-weight:600;font-size:14px">Tingkat Kehadiran</span>
        <strong class="text-<?= $pctHadir>=80?'success':($pctHadir>=60?'warning':'danger') ?>"><?= $pctHadir ?>%</strong>
    </div>
    <div class="progress" style="height:10px;border-radius:5px">
        <div class="progress-bar bg-<?= $pctHadir>=80?'success':($pctHadir>=60?'warning':'danger') ?>"
            style="width:<?= $pctHadir ?>%;border-radius:5px"></div>
    </div>
    <?php if ($pctHadir < 75): ?>
    <small class="text-danger mt-1 d-block">⚠️ Kehadiran di bawah 75%, tingkatkan kehadiran!</small>
    <?php elseif ($pctHadir >= 90): ?>
    <small class="text-success mt-1 d-block">✅ Alhamdulillah, kehadiran sangat baik!</small>
    <?php endif; ?>
</div></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="fas fa-calendar me-2"></i>Detail — <?= date('F Y', strtotime($bulan.'-01')) ?></div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Kelas</th><th>Status</th><th>Keterangan</th></tr></thead>
            <tbody>
                <?php if (empty($absensiList)): ?>
                <tr><td colspan="4"><div class="empty-state py-4"><p>Tidak ada data absensi bulan ini</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($absensiList as $a): ?>
                <tr>
                    <td><?= formatTanggal($a['tanggal'], 'd F Y') ?></td>
                    <td><?= sanitize($a['nama_kelas']) ?></td>
                    <td><?= getStatusBadge($a['status']) ?></td>
                    <td><small class="text-muted"><?= sanitize($a['keterangan'] ?: '-') ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
