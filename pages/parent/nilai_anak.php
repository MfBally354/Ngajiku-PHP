<?php
// pages/parent/nilai_anak.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('parent');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Nilai Anak';

$anak = $db->prepare("SELECT u.* FROM users u JOIN parent_santri ps ON u.id=ps.santri_id WHERE ps.parent_id=? AND u.status='aktif'");
$anak->execute([$uid]);
$anak = $anak->fetchAll();

$anakId = (int)($_GET['anak_id'] ?? ($anak[0]['id'] ?? 0));
$anakAktif = null;
foreach ($anak as $a) { if ($a['id']==$anakId) { $anakAktif=$a; break; } }

$nilaiList = $nilaiTugas = $avgPerKelas = [];
$overallAvg = 0;

if ($anakId) {
    $stmt = $db->prepare("SELECT n.*, k.nama_kelas FROM nilai n JOIN kelas k ON n.kelas_id=k.id WHERE n.santri_id=? ORDER BY n.tanggal DESC");
    $stmt->execute([$anakId]);
    $nilaiList = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT pt.*, t.judul as judul_tugas, k.nama_kelas FROM pengumpulan_tugas pt JOIN tugas t ON pt.tugas_id=t.id JOIN kelas k ON t.kelas_id=k.id WHERE pt.santri_id=? ORDER BY pt.waktu_kumpul DESC");
    $stmt->execute([$anakId]);
    $nilaiTugas = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT k.nama_kelas, ROUND(AVG(n.nilai_angka),1) as avg, COUNT(*) as cnt FROM nilai n JOIN kelas k ON n.kelas_id=k.id WHERE n.santri_id=? GROUP BY k.id");
    $stmt->execute([$anakId]);
    $avgPerKelas = $stmt->fetchAll();

    $overallAvg = $nilaiList ? round(array_sum(array_column($nilaiList,'nilai_angka'))/count($nilaiList),1) : 0;
}

require_once '../../includes/header.php';
?>

<h4 class="page-title mb-1">Nilai Anak</h4>
<p class="page-subtitle mb-4">Pantau perkembangan nilai belajar anak Anda</p>

<?php if (empty($anak)): ?>
<div class="card"><div class="empty-state py-5"><i class="fas fa-child"></i><p>Akun anak belum ditautkan. Hubungi admin.</p></div></div>
<?php else: ?>

<!-- Pilih Anak -->
<?php if (count($anak) > 1): ?>
<div class="card mb-3"><div class="card-body p-3">
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="form-label mb-0 fw-bold">Anak:</label>
        <select name="anak_id" class="form-select" style="max-width:220px" onchange="this.form.submit()">
            <?php foreach ($anak as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $anakId==$a['id']?'selected':'' ?>><?= sanitize($a['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div></div>
<?php endif; ?>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="nilai-big text-<?= $overallAvg>=80?'success':($overallAvg>=60?'warning':'danger') ?>"><?= $overallAvg ?: '-' ?></div>
            <div class="stat-label mt-1">Rata-rata Keseluruhan</div>
            <?php if ($overallAvg): ?>
            <span class="badge bg-<?= nilaiToWarna($overallAvg) ?> mt-1"><?= nilaiToHuruf($overallAvg) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php foreach ($avgPerKelas as $i => $ak):
        if ($i >= 3) break; ?>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="nilai-big text-<?= nilaiToWarna($ak['avg']) ?>"><?= $ak['avg'] ?></div>
            <div class="stat-label mt-1" style="font-size:11px"><?= sanitize($ak['nama_kelas']) ?></div>
            <span class="badge bg-<?= nilaiToWarna($ak['avg']) ?> mt-1"><?= nilaiToHuruf($ak['avg']) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= ($_GET['tab']??'nilai')==='nilai'?'active':'' ?>" href="?anak_id=<?= $anakId ?>&tab=nilai">
            <i class="fas fa-star me-1"></i>Nilai Harian/Ujian
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($_GET['tab']??'')==='tugas'?'active':'' ?>" href="?anak_id=<?= $anakId ?>&tab=tugas">
            <i class="fas fa-tasks me-1"></i>Nilai Tugas
        </a>
    </li>
</ul>

<?php if (($_GET['tab']??'nilai')==='nilai'): ?>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Jenis</th><th>Nilai</th><th>Grade</th><th>Keterangan</th></tr></thead>
            <tbody>
                <?php if (empty($nilaiList)): ?>
                <tr><td colspan="7"><div class="empty-state py-4"><p>Belum ada nilai</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($nilaiList as $n): ?>
                <tr>
                    <td><small><?= formatTanggal($n['tanggal']) ?></small></td>
                    <td><?= sanitize($n['nama_kelas']) ?></td>
                    <td><?= sanitize($n['mata_pelajaran'] ?: '-') ?></td>
                    <td><span class="badge bg-info"><?= ucfirst($n['jenis']) ?></span></td>
                    <td><strong class="text-<?= nilaiToWarna($n['nilai_angka']) ?>"><?= $n['nilai_angka'] ?></strong></td>
                    <td><span class="badge bg-<?= nilaiToWarna($n['nilai_angka']) ?>"><?= nilaiToHuruf($n['nilai_angka']) ?></span></td>
                    <td><small class="text-muted"><?= sanitize($n['keterangan'] ?: '-') ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Tugas</th><th>Kelas</th><th>Dikumpulkan</th><th>Status</th><th>Nilai</th><th>Catatan Ustad</th></tr></thead>
            <tbody>
                <?php if (empty($nilaiTugas)): ?>
                <tr><td colspan="6"><div class="empty-state py-4"><p>Belum ada data tugas</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($nilaiTugas as $t): ?>
                <tr>
                    <td><strong><?= sanitize($t['judul_tugas']) ?></strong></td>
                    <td><?= sanitize($t['nama_kelas']) ?></td>
                    <td><small><?= formatTanggal($t['waktu_kumpul']) ?></small></td>
                    <td><?= getStatusBadge($t['status']) ?></td>
                    <td>
                        <?php if ($t['nilai'] !== null): ?>
                        <strong class="text-<?= nilaiToWarna($t['nilai']) ?>"><?= $t['nilai'] ?></strong>
                        <span class="badge bg-<?= nilaiToWarna($t['nilai']) ?>"><?= nilaiToHuruf($t['nilai']) ?></span>
                        <?php else: ?>
                        <span class="text-muted">Belum dinilai</span>
                        <?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= sanitize($t['catatan_ustad'] ?: '-') ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
