<?php
// pages/santri/dashboard.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('santri');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Dashboard Santri';

// Kelas yang diikuti
$kelas = $db->prepare("SELECT k.*, u.nama as nama_ustad FROM kelas k
    JOIN santri_kelas sk ON k.id=sk.kelas_id
    JOIN users u ON k.ustad_id=u.id
    WHERE sk.santri_id=? AND k.status='aktif'");
$kelas->execute([$uid]);
$kelas = $kelas->fetchAll();

// Materi terbaru
$materi = $db->prepare("SELECT m.*, km.nama as nama_kat FROM materi m
    LEFT JOIN kategori_materi km ON m.kategori_id=km.id
    WHERE m.status='publik' AND (m.kelas_id IS NULL OR m.kelas_id IN (
        SELECT kelas_id FROM santri_kelas WHERE santri_id=?
    ))
    ORDER BY m.created_at DESC LIMIT 6");
$materi->execute([$uid]);
$materi = $materi->fetchAll();

// Tugas aktif
$tugas = $db->prepare("SELECT t.*, k.nama_kelas,
    (SELECT id FROM pengumpulan_tugas WHERE tugas_id=t.id AND santri_id=?) as sudah_kumpul
    FROM tugas t
    JOIN kelas k ON t.kelas_id=k.id
    JOIN santri_kelas sk ON k.id=sk.kelas_id
    WHERE sk.santri_id=? AND t.status='aktif'
    ORDER BY t.deadline ASC LIMIT 5");
$tugas->execute([$uid,$uid]);
$tugas = $tugas->fetchAll();

// Nilai terbaru
$nilaiBaru = $db->prepare("SELECT n.*, k.nama_kelas FROM nilai n
    JOIN kelas k ON n.kelas_id=k.id
    WHERE n.santri_id=? ORDER BY n.tanggal DESC LIMIT 5");
$nilaiBaru->execute([$uid]);
$nilaiBaru = $nilaiBaru->fetchAll();

// Rata-rata nilai
$avgNilai = $db->prepare("SELECT AVG(nilai_angka) FROM nilai WHERE santri_id=?");
$avgNilai->execute([$uid]);
$avgNilai = round($avgNilai->fetchColumn() ?? 0, 1);

// Absensi bulan ini
$absenBulan = $db->prepare("SELECT status, COUNT(*) as jml FROM absensi 
    WHERE santri_id=? AND DATE_FORMAT(tanggal,'%Y-%m')=? GROUP BY status");
$absenBulan->execute([$uid, date('Y-m')]);
$absen = [];
foreach ($absenBulan->fetchAll() as $a) $absen[$a['status']] = $a['jml'];

// Pengumuman
$pengumuman = $db->query("SELECT p.*, u.nama as penulis FROM pengumuman p
    JOIN users u ON p.penulis_id=u.id
    WHERE FIND_IN_SET('santri', p.target_role)
    ORDER BY p.created_at DESC LIMIT 3")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="welcome-banner">
    <div class="welcome-title">Bismillah, <?= sanitize($user['nama']) ?>! 📖</div>
    <div class="welcome-sub">Semangat belajar hari ini — <?= date('l, d F Y') ?></div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-school"></i></div>
            <div><div class="stat-value"><?= count($kelas) ?></div><div class="stat-label">Kelas Aktif</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-star"></i></div>
            <div><div class="stat-value"><?= $avgNilai ?: '-' ?></div><div class="stat-label">Rata-rata Nilai</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fas fa-calendar-check"></i></div>
            <div><div class="stat-value"><?= $absen['hadir'] ?? 0 ?></div><div class="stat-label">Hadir Bulan Ini</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-tasks"></i></div>
            <div>
                <div class="stat-value"><?= count(array_filter($tugas, fn($t) => !$t['sudah_kumpul'])) ?></div>
                <div class="stat-label">Tugas Pending</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Materi Terbaru -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-book-open me-2"></i>Materi Terbaru</span>
                <a href="materi.php" class="btn-outline-green" style="padding:3px 10px;font-size:12px">Semua</a>
            </div>
            <div class="card-body">
                <?php if (empty($materi)): ?>
                <div class="empty-state"><i class="fas fa-book-open"></i><p>Belum ada materi</p></div>
                <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($materi as $m): ?>
                    <div class="col-md-6">
                        <a href="materi.php?id=<?= $m['id'] ?>" class="text-decoration-none">
                            <div class="materi-card d-flex gap-3 align-items-start" style="padding:12px">
                                <div class="materi-icon" style="width:36px;height:36px;font-size:15px;flex-shrink:0">
                                    <?php $icons=['pdf'=>'fa-file-pdf','video'=>'fa-play-circle',
                                                  'gambar'=>'fa-image','link'=>'fa-link','teks'=>'fa-file-lines'];
                                    echo '<i class="fas '.($icons[$m['tipe_file']]??'fa-file').'"></i>'; ?>
                                </div>
                                <div style="min-width:0">
                                    <div class="materi-title" style="font-size:13px"><?= sanitize($m['judul']) ?></div>
                                    <div class="materi-meta" style="font-size:11px">
                                        <?= sanitize($m['nama_kat'] ?? 'Umum') ?>
                                        · <?= formatTanggal($m['created_at']) ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar kanan -->
    <div class="col-lg-4">
        <!-- Tugas Mendatang -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-tasks me-2"></i>Tugas Aktif</span>
                <a href="tugas.php" class="btn-outline-green" style="padding:3px 10px;font-size:12px">Semua</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tugas)): ?>
                <div class="empty-state py-3"><i class="fas fa-check text-success"></i><p>Tidak ada tugas aktif</p></div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($tugas as $t): ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div style="font-size:13px;font-weight:600"><?= sanitize($t['judul']) ?></div>
                                <div style="font-size:11px;color:#888"><?= sanitize($t['nama_kelas']) ?></div>
                            </div>
                            <?php if ($t['sudah_kumpul']): ?>
                            <span class="badge bg-success">Terkumpul</span>
                            <?php elseif ($t['deadline'] && strtotime($t['deadline']) < time()): ?>
                            <span class="badge bg-danger">Terlambat</span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($t['deadline']): ?>
                        <small class="text-muted">Deadline: <?= formatTanggal($t['deadline']) ?></small>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Nilai Terbaru -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-star me-2"></i>Nilai Terbaru</span>
                <a href="nilai.php" class="btn-outline-green" style="padding:3px 10px;font-size:12px">Semua</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($nilaiBaru)): ?>
                <div class="empty-state py-3"><p>Belum ada nilai</p></div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($nilaiBaru as $n): ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom d-flex justify-content-between">
                        <div>
                            <div style="font-size:13px;font-weight:600"><?= sanitize($n['mata_pelajaran'] ?: ucfirst($n['jenis'])) ?></div>
                            <small class="text-muted"><?= formatTanggal($n['tanggal']) ?></small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold text-<?= nilaiToWarna($n['nilai_angka']) ?>"><?= $n['nilai_angka'] ?></span>
                            <br><span class="badge bg-<?= nilaiToWarna($n['nilai_angka']) ?>"><?= nilaiToHuruf($n['nilai_angka']) ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pengumuman -->
        <div class="card">
            <div class="card-header"><i class="fas fa-bullhorn me-2"></i>Pengumuman</div>
            <div class="card-body p-0">
                <?php if (empty($pengumuman)): ?>
                <div class="empty-state py-3"><p>Tidak ada pengumuman</p></div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($pengumuman as $p): ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom">
                        <span class="badge bg-<?= $p['prioritas']==='darurat'?'danger':($p['prioritas']==='penting'?'warning':'secondary') ?> mb-1">
                            <?= ucfirst($p['prioritas']) ?>
                        </span>
                        <div style="font-size:13px;font-weight:600"><?= sanitize($p['judul']) ?></div>
                        <small class="text-muted"><?= formatTanggal($p['created_at']) ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
