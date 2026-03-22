<?php
// pages/parent/absensi.php
require_once '../../includes/auth_check.php';
requireRole('parent');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Absensi Anak';

$anak = $db->prepare("SELECT u.* FROM users u JOIN parent_santri ps ON u.id=ps.santri_id WHERE ps.parent_id=? AND u.status='aktif'");
$anak->execute([$uid]);
$anak = $anak->fetchAll();

$anakId = (int)($_GET['anak_id'] ?? ($anak[0]['id'] ?? 0));
$bulan  = $_GET['bulan'] ?? date('Y-m');

$absensiList = $rekapBulan = [];
if ($anakId) {
    $stmt = $db->prepare("SELECT a.*, k.nama_kelas FROM absensi a JOIN kelas k ON a.kelas_id=k.id WHERE a.santri_id=? AND DATE_FORMAT(a.tanggal,'%Y-%m')=? ORDER BY a.tanggal DESC, k.nama_kelas");
    $stmt->execute([$anakId,$bulan]);
    $absensiList = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT status, COUNT(*) as jml FROM absensi WHERE santri_id=? AND DATE_FORMAT(tanggal,'%Y-%m')=? GROUP BY status");
    $stmt->execute([$anakId,$bulan]);
    foreach ($stmt->fetchAll() as $r) $rekapBulan[$r['status']] = $r['jml'];
}

require_once '../../includes/header.php';
?>

<h4 class="page-title mb-1">Absensi Anak</h4>
<p class="page-subtitle mb-4">Pantau kehadiran anak di kelas ngaji</p>

<?php if (empty($anak)): ?>
<div class="card"><div class="empty-state py-5"><p>Akun anak belum ditautkan. Hubungi admin.</p></div></div>
<?php else: ?>

<div class="card mb-3"><div class="card-body p-3">
    <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
        <?php if (count($anak) > 1): ?>
        <select name="anak_id" class="form-select" style="max-width:200px" onchange="this.form.submit()">
            <?php foreach ($anak as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $anakId==$a['id']?'selected':'' ?>><?= sanitize($a['nama']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php else: ?>
        <input type="hidden" name="anak_id" value="<?= $anakId ?>">
        <?php endif; ?>
        <input type="month" name="bulan" class="form-control" style="max-width:180px" value="<?= $bulan ?>" onchange="this.form.submit()">
    </form>
</div></div>

<!-- Rekap Bulan -->
<div class="row g-3 mb-4">
    <?php foreach (['hadir'=>['success','Hadir'],'izin'=>['info','Izin'],'sakit'=>['warning','Sakit'],'alpha'=>['danger','Alpha']] as $sts => [$cls,$label]): ?>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon <?= $cls ?>"><i class="fas <?= $sts==='hadir'?'fa-check-circle':($sts==='izin'?'fa-file-alt':($sts==='sakit'?'fa-hospital':'fa-times-circle')) ?>"></i></div>
            <div><div class="stat-value"><?= $rekapBulan[$sts] ?? 0 ?></div><div class="stat-label"><?= $label ?></div></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-calendar-check me-2"></i>Detail Absensi — <?= date('F Y', strtotime($bulan.'-01')) ?></div>
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
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
