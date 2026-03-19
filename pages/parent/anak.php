<?php
// pages/parent/anak.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('parent');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Anak Saya';

$anak = $db->prepare("SELECT u.* FROM users u JOIN parent_santri ps ON u.id=ps.santri_id WHERE ps.parent_id=? ORDER BY u.nama");
$anak->execute([$uid]);
$anak = $anak->fetchAll();

require_once '../../includes/header.php';
?>

<h4 class="page-title mb-1">Anak Saya</h4>
<p class="page-subtitle mb-4">Data santri yang terhubung ke akun Anda</p>

<?php if (empty($anak)): ?>
<div class="card"><div class="empty-state py-5">
    <i class="fas fa-child" style="font-size:64px;color:#ccc"></i>
    <p class="mt-2">Akun anak belum ditautkan.<br>Hubungi admin untuk menautkan akun santri Anda.</p>
</div></div>
<?php else: ?>
<div class="row g-4">
    <?php foreach ($anak as $a):
        // Stats per anak
        $avgNilai = $db->prepare("SELECT ROUND(AVG(nilai_angka),1) FROM nilai WHERE santri_id=?");
        $avgNilai->execute([$a['id']]);
        $avgNilai = $avgNilai->fetchColumn() ?? 0;

        $hadir = $db->prepare("SELECT COUNT(*) FROM absensi WHERE santri_id=? AND status='hadir' AND DATE_FORMAT(tanggal,'%Y-%m')=?");
        $hadir->execute([$a['id'], date('Y-m')]);
        $hadir = $hadir->fetchColumn();

        $kelas = $db->prepare("SELECT k.nama_kelas FROM kelas k JOIN santri_kelas sk ON k.id=sk.kelas_id WHERE sk.santri_id=? AND k.status='aktif'");
        $kelas->execute([$a['id']]);
        $kelasSantri = $kelas->fetchAll();
    ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="avatar-circle" style="width:56px;height:56px;font-size:22px;flex-shrink:0"><?= avatarInitial($a['nama']) ?></div>
                    <div>
                        <h6 style="font-weight:700;font-size:16px;margin-bottom:4px"><?= sanitize($a['nama']) ?></h6>
                        <div style="font-size:13px;color:#888"><?= sanitize($a['email']) ?></div>
                        <?= getStatusBadge($a['status']) ?>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-4 text-center">
                        <div class="fw-bold text-<?= $avgNilai>=80?'success':($avgNilai>=60?'warning':'danger') ?>" style="font-size:22px"><?= $avgNilai ?: '-' ?></div>
                        <div style="font-size:11px;color:#888">Rata-rata Nilai</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="fw-bold text-success" style="font-size:22px"><?= $hadir ?></div>
                        <div style="font-size:11px;color:#888">Hadir Bulan Ini</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="fw-bold text-primary" style="font-size:22px"><?= count($kelasSantri) ?></div>
                        <div style="font-size:11px;color:#888">Kelas Aktif</div>
                    </div>
                </div>

                <?php if ($kelasSantri): ?>
                <div class="mb-3">
                    <div style="font-size:12px;color:#888;margin-bottom:6px">Kelas yang diikuti:</div>
                    <div class="d-flex gap-1 flex-wrap">
                        <?php foreach ($kelasSantri as $k): ?>
                        <span class="kategori-pill" style="font-size:11px;padding:3px 8px"><?= sanitize($k['nama_kelas']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <a href="nilai_anak.php?anak_id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-success flex-fill text-center">
                        <i class="fas fa-star me-1"></i>Nilai
                    </a>
                    <a href="absensi.php?anak_id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-info flex-fill text-center">
                        <i class="fas fa-calendar-check me-1"></i>Absensi
                    </a>
                    <a href="dashboard.php?anak_id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary flex-fill text-center">
                        <i class="fas fa-gauge me-1"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
