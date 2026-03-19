<?php
// pages/ustad/dashboard.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('ustad');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Dashboard Ustad';

// Kelas yang diajar
$kelas = $db->prepare("SELECT k.*, COUNT(sk.santri_id) as jml_santri 
    FROM kelas k 
    LEFT JOIN santri_kelas sk ON k.id = sk.kelas_id
    WHERE k.ustad_id = ? AND k.status='aktif'
    GROUP BY k.id ORDER BY k.created_at DESC");
$kelas->execute([$uid]);
$kelas = $kelas->fetchAll();

$stats = [
    'kelas'    => count($kelas),
    'santri'   => $db->prepare("SELECT COUNT(DISTINCT sk.santri_id) FROM santri_kelas sk 
                                 JOIN kelas k ON sk.kelas_id = k.id WHERE k.ustad_id = ?"),
    'materi'   => $db->prepare("SELECT COUNT(*) FROM materi WHERE ustad_id = ?"),
    'tugas'    => $db->prepare("SELECT COUNT(*) FROM tugas WHERE ustad_id = ? AND status='aktif'"),
];
foreach (['santri','materi','tugas'] as $k) {
    $stats[$k]->execute([$uid]);
    $stats[$k] = $stats[$k]->fetchColumn();
}

// Tugas belum dinilai
$tugas_baru = $db->prepare("SELECT pt.*, t.judul as judul_tugas, u.nama as nama_santri
    FROM pengumpulan_tugas pt
    JOIN tugas t ON pt.tugas_id = t.id
    JOIN users u ON pt.santri_id = u.id
    WHERE t.ustad_id = ? AND pt.status = 'dikumpulkan'
    ORDER BY pt.waktu_kumpul DESC LIMIT 5");
$tugas_baru->execute([$uid]);
$tugas_baru = $tugas_baru->fetchAll();

require_once '../../includes/header.php';
?>

<div class="welcome-banner">
    <div class="welcome-title">Assalamu'alaikum, <?= sanitize($user['nama']) ?>! 🌙</div>
    <div class="welcome-sub">Semoga ilmu yang kita ajarkan menjadi amal jariyah — <?= date('d F Y') ?></div>
</div>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['icon'=>'fa-school','cls'=>'blue','val'=>$stats['kelas'],'lbl'=>'Kelas Aktif'],
        ['icon'=>'fa-user-graduate','cls'=>'purple','val'=>$stats['santri'],'lbl'=>'Total Santri'],
        ['icon'=>'fa-book-open','cls'=>'green','val'=>$stats['materi'],'lbl'=>'Materi Dibuat'],
        ['icon'=>'fa-tasks','cls'=>'orange','val'=>$stats['tugas'],'lbl'=>'Tugas Aktif'],
    ];
    foreach ($cards as $c): ?>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon <?= $c['cls'] ?>"><i class="fas <?= $c['icon'] ?>"></i></div>
            <div>
                <div class="stat-value"><?= $c['val'] ?></div>
                <div class="stat-label"><?= $c['lbl'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Kelas yang diajar -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-chalkboard me-2"></i>Kelas Saya</span>
                <a href="kelas.php" class="btn-outline-green" style="padding:3px 10px;font-size:12px">Kelola</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($kelas)): ?>
                <div class="empty-state py-4"><i class="fas fa-school"></i><p>Belum ada kelas</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Nama Kelas</th><th>Jadwal</th><th>Santri</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($kelas as $k): ?>
                        <tr>
                            <td><strong><?= sanitize($k['nama_kelas']) ?></strong></td>
                            <td><small class="text-muted"><?= sanitize($k['jadwal'] ?: '-') ?></small></td>
                            <td><span class="badge bg-success"><?= $k['jml_santri'] ?> santri</span></td>
                            <td>
                                <a href="nilai.php?kelas_id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-star"></i>
                                </a>
                                <a href="absensi.php?kelas_id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-calendar-check"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tugas Belum Dinilai -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-clipboard-check me-2"></i>Belum Dinilai</span>
                <a href="tugas.php" class="btn-outline-green" style="padding:3px 10px;font-size:12px">Semua</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tugas_baru)): ?>
                <div class="empty-state py-4"><i class="fas fa-check-circle text-success"></i><p>Semua sudah dinilai!</p></div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($tugas_baru as $t): ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom">
                        <div style="font-size:13px;font-weight:600"><?= sanitize($t['nama_santri']) ?></div>
                        <div style="font-size:11px;color:#888"><?= sanitize($t['judul_tugas']) ?></div>
                        <div style="font-size:11px;color:#888"><?= formatTanggal($t['waktu_kumpul']) ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
