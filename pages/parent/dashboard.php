<?php
// pages/parent/dashboard.php
require_once '../../includes/auth_check.php';
requireRole('parent');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Dashboard Orang Tua';

// Anak-anak terdaftar
$anak = $db->prepare("SELECT u.* FROM users u
    JOIN parent_santri ps ON u.id=ps.santri_id
    WHERE ps.parent_id=? AND u.status='aktif'");
$anak->execute([$uid]);
$anak = $anak->fetchAll();

// Pilih anak aktif
$anakId = (int)($_GET['anak_id'] ?? ($anak[0]['id'] ?? 0));
$anakAktif = null;
foreach ($anak as $a) {
    if ($a['id'] === $anakId) { $anakAktif = $a; break; }
}

$nilaiAnak = $kelasAnak = $absenAnak = $tugasAnak = [];
if ($anakId) {
    // Nilai terbaru
    $stmt = $db->prepare("SELECT n.*, k.nama_kelas FROM nilai n
        JOIN kelas k ON n.kelas_id=k.id
        WHERE n.santri_id=? ORDER BY n.tanggal DESC LIMIT 8");
    $stmt->execute([$anakId]);
    $nilaiAnak = $stmt->fetchAll();

    // Kelas anak
    $stmt = $db->prepare("SELECT k.*, u.nama as nama_ustad FROM kelas k
        JOIN santri_kelas sk ON k.id=sk.kelas_id
        JOIN users u ON k.ustad_id=u.id
        WHERE sk.santri_id=? AND k.status='aktif'");
    $stmt->execute([$anakId]);
    $kelasAnak = $stmt->fetchAll();

    // Absensi bulan ini
    $stmt = $db->prepare("SELECT status, COUNT(*) as jml FROM absensi
        WHERE santri_id=? AND DATE_FORMAT(tanggal,'%Y-%m')=?
        GROUP BY status");
    $stmt->execute([$anakId, date('Y-m')]);
    foreach ($stmt->fetchAll() as $a) $absenAnak[$a['status']] = $a['jml'];

    // Tugas aktif
    $stmt = $db->prepare("SELECT t.*, k.nama_kelas,
        (SELECT id FROM pengumpulan_tugas WHERE tugas_id=t.id AND santri_id=?) as sudah_kumpul
        FROM tugas t JOIN kelas k ON t.kelas_id=k.id
        JOIN santri_kelas sk ON k.id=sk.kelas_id
        WHERE sk.santri_id=? AND t.status='aktif'
        ORDER BY t.deadline LIMIT 5");
    $stmt->execute([$anakId,$anakId]);
    $tugasAnak = $stmt->fetchAll();
}

$avgNilai = $nilaiAnak ? round(array_sum(array_column($nilaiAnak,'nilai_angka')) / count($nilaiAnak), 1) : 0;

// Pengumuman
$pengumuman = $db->query("SELECT p.*, u.nama as penulis FROM pengumuman p
    JOIN users u ON p.penulis_id=u.id
    WHERE FIND_IN_SET('parent', p.target_role)
    ORDER BY p.created_at DESC LIMIT 4")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="welcome-banner">
    <div class="welcome-title">Assalamu'alaikum, <?= sanitize($user['nama']) ?>! 👋</div>
    <div class="welcome-sub">Pantau perkembangan belajar anak Anda — <?= date('d F Y') ?></div>
</div>

<!-- Pilih Anak -->
<?php if (count($anak) > 1): ?>
<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <label class="form-label mb-0 fw-bold">Lihat data:</label>
            <select name="anak_id" class="form-select" style="max-width:220px" onchange="this.form.submit()">
                <?php foreach ($anak as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $anakId==$a['id']?'selected':'' ?>>
                    <?= sanitize($a['nama']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (empty($anak)): ?>
<div class="card"><div class="empty-state py-5">
    <i class="fas fa-child" style="font-size:64px;color:#ccc"></i>
    <p class="mt-2">Akun anak belum ditautkan ke akun ini.<br>
    Hubungi admin untuk menautkan akun santri.</p>
</div></div>
<?php else: ?>

<!-- Info Anak Aktif -->
<?php if ($anakAktif): ?>
<div class="card mb-4" style="border-left:4px solid #2E7D32">
    <div class="card-body d-flex align-items-center gap-3">
        <div class="avatar-circle" style="width:52px;height:52px;font-size:20px;flex-shrink:0">
            <?= avatarInitial($anakAktif['nama']) ?>
        </div>
        <div>
            <div style="font-size:18px;font-weight:700"><?= sanitize($anakAktif['nama']) ?></div>
            <div style="font-size:13px;color:#888">
                <?= sanitize($anakAktif['email']) ?>
                · <?= count($kelasAnak) ?> kelas aktif
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-star"></i></div>
            <div><div class="stat-value"><?= $avgNilai ?: '-' ?></div><div class="stat-label">Rata-rata Nilai</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fas fa-calendar-check"></i></div>
            <div><div class="stat-value"><?= $absenAnak['hadir'] ?? 0 ?></div><div class="stat-label">Hadir Bulan Ini</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-exclamation-circle"></i></div>
            <div><div class="stat-value"><?= $absenAnak['alpha'] ?? 0 ?></div><div class="stat-label">Alpha Bulan Ini</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-tasks"></i></div>
            <div>
                <div class="stat-value"><?= count(array_filter($tugasAnak, fn($t) => !$t['sudah_kumpul'])) ?></div>
                <div class="stat-label">Tugas Belum Dikumpulkan</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Nilai Terbaru -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-star me-2"></i>Nilai Terbaru</span>
                <a href="nilai_anak.php?anak_id=<?= $anakId ?>" class="btn-outline-green" style="padding:3px 10px;font-size:12px">Lengkap</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Tanggal</th><th>Mapel</th><th>Jenis</th><th>Nilai</th></tr></thead>
                    <tbody>
                        <?php if (empty($nilaiAnak)): ?>
                        <tr><td colspan="4"><div class="empty-state py-3"><p>Belum ada nilai</p></div></td></tr>
                        <?php else: ?>
                        <?php foreach ($nilaiAnak as $n): ?>
                        <tr>
                            <td><small><?= formatTanggal($n['tanggal']) ?></small></td>
                            <td><?= sanitize($n['mata_pelajaran'] ?: $n['nama_kelas']) ?></td>
                            <td><span class="badge bg-info"><?= ucfirst($n['jenis']) ?></span></td>
                            <td>
                                <strong class="text-<?= nilaiToWarna($n['nilai_angka']) ?>"><?= $n['nilai_angka'] ?></strong>
                                <span class="badge bg-<?= nilaiToWarna($n['nilai_angka']) ?>"><?= nilaiToHuruf($n['nilai_angka']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kanan -->
    <div class="col-md-5">
        <!-- Tugas -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-tasks me-2"></i>Tugas Anak</div>
            <div class="card-body p-0">
                <?php if (empty($tugasAnak)): ?>
                <div class="empty-state py-3"><p>Tidak ada tugas aktif</p></div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($tugasAnak as $t): ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom d-flex justify-content-between align-items-start">
                        <div>
                            <div style="font-size:13px;font-weight:600"><?= sanitize($t['judul']) ?></div>
                            <?php if ($t['deadline']): ?>
                            <small class="text-muted">Deadline: <?= formatTanggal($t['deadline']) ?></small>
                            <?php endif; ?>
                        </div>
                        <?php if ($t['sudah_kumpul']): ?>
                        <span class="badge bg-success">Terkumpul</span>
                        <?php else: ?>
                        <span class="badge bg-warning text-dark">Belum</span>
                        <?php endif; ?>
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
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
