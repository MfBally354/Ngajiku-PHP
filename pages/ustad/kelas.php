<?php
// pages/ustad/kelas.php
require_once '../../includes/auth_check.php';
requireRole('ustad');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Kelas Saya';

// Kelas milik ustad ini
$kelasList = $db->prepare("SELECT k.*, COUNT(sk.santri_id) as jml_santri
    FROM kelas k
    LEFT JOIN santri_kelas sk ON k.id=sk.kelas_id
    WHERE k.ustad_id=?
    GROUP BY k.id ORDER BY k.status DESC, k.nama_kelas");
$kelasList->execute([$uid]);
$kelasList = $kelasList->fetchAll();

// Detail kelas
$detailKelas = null;
$santriDiKelas = [];
if (isset($_GET['detail'])) {
    $kid = (int)$_GET['detail'];
    $stmt = $db->prepare("SELECT * FROM kelas WHERE id=? AND ustad_id=?");
    $stmt->execute([$kid,$uid]);
    $detailKelas = $stmt->fetch();

    if ($detailKelas) {
        $stmt = $db->prepare("SELECT u.*, 
            (SELECT ROUND(AVG(nilai_angka),1) FROM nilai WHERE santri_id=u.id AND kelas_id=?) as avg_nilai,
            (SELECT COUNT(*) FROM absensi WHERE santri_id=u.id AND kelas_id=? AND status='hadir') as hadir,
            (SELECT COUNT(*) FROM absensi WHERE santri_id=u.id AND kelas_id=? AND status='alpha') as alpha
            FROM users u JOIN santri_kelas sk ON u.id=sk.santri_id WHERE sk.kelas_id=? ORDER BY u.nama");
        $stmt->execute([$kid,$kid,$kid,$kid]);
        $santriDiKelas = $stmt->fetchAll();
    }
}

require_once '../../includes/header.php';
?>

<h4 class="page-title mb-1">Kelas Saya</h4>
<p class="page-subtitle mb-4">Lihat dan kelola kelas yang Anda ampu</p>

<?php if ($detailKelas): ?>
<div class="mb-3">
    <a href="kelas.php" class="btn-outline-green" style="padding:6px 14px">
        <i class="fas fa-arrow-left"></i>Kembali
    </a>
</div>
<div class="card mb-4" style="border-left:4px solid #2E7D32">
    <div class="card-body">
        <h5 style="font-weight:700;margin-bottom:6px"><?= sanitize($detailKelas['nama_kelas']) ?></h5>
        <div style="font-size:13px;color:#888" class="d-flex gap-3 flex-wrap">
            <?php if ($detailKelas['jadwal']): ?>
            <span><i class="fas fa-clock me-1"></i><?= sanitize($detailKelas['jadwal']) ?></span>
            <?php endif; ?>
            <?php if ($detailKelas['lokasi']): ?>
            <span><i class="fas fa-location-dot me-1"></i><?= sanitize($detailKelas['lokasi']) ?></span>
            <?php endif; ?>
            <span><i class="fas fa-users me-1"></i><?= count($santriDiKelas) ?> santri</span>
        </div>
        <?php if ($detailKelas['deskripsi']): ?>
        <p style="font-size:13px;margin-top:8px;color:#555"><?= sanitize($detailKelas['deskripsi']) ?></p>
        <?php endif; ?>
        <div class="d-flex gap-2 mt-3">
            <a href="nilai.php?kelas_id=<?= $detailKelas['id'] ?>" class="btn-primary-green">
                <i class="fas fa-star"></i>Input Nilai
            </a>
            <a href="absensi.php?kelas_id=<?= $detailKelas['id'] ?>" class="btn-outline-green">
                <i class="fas fa-calendar-check"></i>Absensi
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-users me-2"></i>Daftar Santri</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>#</th><th>Santri</th><th>Rata-rata Nilai</th><th>Hadir</th><th>Alpha</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php if (empty($santriDiKelas)): ?>
                <tr><td colspan="6"><div class="empty-state py-4"><i class="fas fa-users"></i><p>Belum ada santri di kelas ini.<br>Hubungi admin untuk mendaftarkan santri.</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($santriDiKelas as $i => $s): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0"><?= avatarInitial($s['nama']) ?></div>
                            <div>
                                <div style="font-weight:600;font-size:13px"><?= sanitize($s['nama']) ?></div>
                                <div style="font-size:11px;color:#888"><?= sanitize($s['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($s['avg_nilai']): ?>
                        <strong class="text-<?= nilaiToWarna($s['avg_nilai']) ?>"><?= $s['avg_nilai'] ?></strong>
                        <span class="badge bg-<?= nilaiToWarna($s['avg_nilai']) ?>"><?= nilaiToHuruf($s['avg_nilai']) ?></span>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-success"><?= $s['hadir'] ?>x</span></td>
                    <td><span class="badge bg-<?= $s['alpha']>3?'danger':'secondary' ?>"><?= $s['alpha'] ?>x</span></td>
                    <td>
                        <a href="nilai.php?kelas_id=<?= $detailKelas['id'] ?>" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-star"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- List Kelas -->
<?php if (empty($kelasList)): ?>
<div class="card"><div class="empty-state py-5">
    <i class="fas fa-school"></i>
    <p>Anda belum ditugaskan ke kelas apapun.<br>Hubungi admin untuk menambahkan kelas.</p>
</div></div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($kelasList as $k): ?>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <?= getStatusBadge($k['status']) ?>
                    <span class="badge bg-success"><?= $k['jml_santri'] ?> santri</span>
                </div>
                <h6 style="font-weight:700;margin-bottom:6px"><?= sanitize($k['nama_kelas']) ?></h6>
                <?php if ($k['deskripsi']): ?>
                <p style="font-size:13px;color:#666;margin-bottom:8px"><?= sanitize(substr($k['deskripsi'],0,100)) ?></p>
                <?php endif; ?>
                <div style="font-size:12px;color:#888;margin-bottom:12px" class="d-flex gap-3 flex-wrap">
                    <?php if ($k['jadwal']): ?>
                    <span><i class="fas fa-clock me-1"></i><?= sanitize($k['jadwal']) ?></span>
                    <?php endif; ?>
                    <?php if ($k['lokasi']): ?>
                    <span><i class="fas fa-location-dot me-1"></i><?= sanitize($k['lokasi']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="kelas.php?detail=<?= $k['id'] ?>" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-users"></i> Lihat Santri
                    </a>
                    <a href="nilai.php?kelas_id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-star"></i> Nilai
                    </a>
                    <a href="absensi.php?kelas_id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-calendar-check"></i> Absensi
                    </a>
                    <a href="tugas.php" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-tasks"></i> Tugas
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
