<?php
// pages/santri/nilai.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('santri');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Nilai Saya';

// Semua nilai santri
$nilaiList = $db->prepare("SELECT n.*, k.nama_kelas FROM nilai n
    JOIN kelas k ON n.kelas_id=k.id
    WHERE n.santri_id=? ORDER BY n.tanggal DESC");
$nilaiList->execute([$uid]);
$nilaiList = $nilaiList->fetchAll();

// Rata-rata per kelas
$avgPerKelas = $db->prepare("SELECT k.nama_kelas, AVG(n.nilai_angka) as avg, COUNT(*) as cnt
    FROM nilai n JOIN kelas k ON n.kelas_id=k.id
    WHERE n.santri_id=? GROUP BY k.id, k.nama_kelas");
$avgPerKelas->execute([$uid]);
$avgPerKelas = $avgPerKelas->fetchAll();

// Nilai tugas
$nilaiTugas = $db->prepare("SELECT pt.*, t.judul as judul_tugas, k.nama_kelas
    FROM pengumpulan_tugas pt
    JOIN tugas t ON pt.tugas_id=t.id
    JOIN kelas k ON t.kelas_id=k.id
    WHERE pt.santri_id=? AND pt.status='dinilai'
    ORDER BY pt.waktu_kumpul DESC");
$nilaiTugas->execute([$uid]);
$nilaiTugas = $nilaiTugas->fetchAll();

$overallAvg = count($nilaiList) ? round(array_sum(array_column($nilaiList,'nilai_angka')) / count($nilaiList), 1) : 0;

require_once '../../includes/header.php';
?>

<h4 class="page-title mb-1">Nilai Saya</h4>
<p class="page-subtitle mb-4">Lihat semua perkembangan nilaimu</p>

<!-- Summary Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="nilai-big text-<?= $overallAvg>=80?'success':($overallAvg>=60?'warning':'danger') ?>">
                <?= $overallAvg ?: '-' ?>
            </div>
            <div class="stat-label mt-1">Rata-rata Keseluruhan</div>
            <?php if ($overallAvg): ?>
            <span class="badge bg-<?= nilaiToWarna($overallAvg) ?> mt-1"><?= nilaiToHuruf($overallAvg) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="nilai-big text-primary"><?= count($nilaiList) ?></div>
            <div class="stat-label mt-1">Total Penilaian</div>
        </div>
    </div>
    <?php foreach ($avgPerKelas as $i => $ak):
        if ($i >= 2) break; ?>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="nilai-big text-<?= nilaiToWarna($ak['avg']) ?>"><?= round($ak['avg'],1) ?></div>
            <div class="stat-label mt-1"><?= sanitize($ak['nama_kelas']) ?></div>
            <span class="badge bg-<?= nilaiToWarna($ak['avg']) ?> mt-1"><?= nilaiToHuruf($ak['avg']) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= ($_GET['tab']??'nilai')==='nilai'?'active':'' ?>" href="nilai.php?tab=nilai">
            <i class="fas fa-star me-1"></i>Nilai Harian/Ujian
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($_GET['tab']??'')==='tugas'?'active':'' ?>" href="nilai.php?tab=tugas">
            <i class="fas fa-tasks me-1"></i>Nilai Tugas
        </a>
    </li>
</ul>

<?php if (($_GET['tab']??'nilai') === 'nilai'): ?>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Jenis</th><th>Nilai</th><th>Grade</th><th>Keterangan</th></tr></thead>
            <tbody>
                <?php if (empty($nilaiList)): ?>
                <tr><td colspan="7"><div class="empty-state py-4"><i class="fas fa-star"></i><p>Belum ada nilai</p></div></td></tr>
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
            <thead><tr><th>Tugas</th><th>Kelas</th><th>Dikumpulkan</th><th>Nilai</th><th>Catatan Ustad</th></tr></thead>
            <tbody>
                <?php if (empty($nilaiTugas)): ?>
                <tr><td colspan="5"><div class="empty-state py-4"><p>Belum ada nilai tugas</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($nilaiTugas as $t): ?>
                <tr>
                    <td><strong><?= sanitize($t['judul_tugas']) ?></strong></td>
                    <td><?= sanitize($t['nama_kelas']) ?></td>
                    <td><small><?= formatTanggal($t['waktu_kumpul']) ?></small></td>
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

<?php require_once '../../includes/footer.php'; ?>
