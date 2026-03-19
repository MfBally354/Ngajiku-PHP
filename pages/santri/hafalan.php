<?php
// pages/santri/hafalan.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('santri');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Hafalan Saya';

$hafalanList = $db->prepare("SELECT h.*, k.nama_kelas FROM hafalan h JOIN kelas k ON h.kelas_id=k.id WHERE h.santri_id=? ORDER BY h.tanggal DESC");
$hafalanList->execute([$uid]);
$hafalanList = $hafalanList->fetchAll();

$rekap = ['A'=>0,'B'=>0,'C'=>0,'D'=>0];
foreach ($hafalanList as $h) $rekap[$h['nilai']]++;

require_once '../../includes/header.php';
?>

<h4 class="page-title mb-1">Hafalan Saya</h4>
<p class="page-subtitle mb-4">Rekap hafalan Al-Quran, doa harian, dan hadits</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-scroll"></i></div>
            <div><div class="stat-value"><?= count($hafalanList) ?></div><div class="stat-label">Total Hafalan</div></div>
        </div>
    </div>
    <?php foreach (['A'=>['success','Lancar & Tajwid'],'B'=>['info','Lancar'],'C'=>['warning','Cukup'],'D'=>['danger','Perlu Ulang']] as $n => [$cls,$lbl]): ?>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon <?= $cls ?>"><span style="font-size:20px;font-weight:800"><?= $n ?></span></div>
            <div><div class="stat-value"><?= $rekap[$n] ?></div><div class="stat-label"><?= $lbl ?></div></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-scroll me-2"></i>Riwayat Hafalan</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Kelas</th><th>Jenis</th><th>Hafalan</th><th>Nilai</th><th>Catatan</th></tr></thead>
            <tbody>
                <?php
                $nilaiMap = ['A'=>['success','A — Lancar & Tajwid'],'B'=>['info','B — Lancar'],'C'=>['warning','C — Cukup'],'D'=>['danger','D — Perlu Ulang']];
                if (empty($hafalanList)): ?>
                <tr><td colspan="6"><div class="empty-state py-5">
                    <i class="fas fa-book-quran"></i>
                    <p>Belum ada data hafalan.<br>Ustad akan menginputkan hafalan setelah kamu maju.</p>
                </div></td></tr>
                <?php else: ?>
                <?php foreach ($hafalanList as $h):
                    $nm = $nilaiMap[$h['nilai']] ?? ['secondary',$h['nilai']]; ?>
                <tr>
                    <td><small><?= formatTanggal($h['tanggal']) ?></small></td>
                    <td><?= sanitize($h['nama_kelas']) ?></td>
                    <td><span class="badge bg-secondary"><?= ucfirst($h['jenis']) ?></span></td>
                    <td><strong><?= sanitize($h['nama_hafalan']) ?></strong></td>
                    <td><span class="badge bg-<?= $nm[0] ?>"><?= $nm[1] ?></span></td>
                    <td><small class="text-muted"><?= sanitize($h['catatan'] ?: '-') ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
