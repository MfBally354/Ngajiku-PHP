<?php
// pages/admin/dashboard.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('admin');

$db = getDB();
$pageTitle = 'Dashboard Admin';

// Statistik
$stats = [
    'total_user'    => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_santri'  => $db->query("SELECT COUNT(*) FROM users WHERE role='santri'")->fetchColumn(),
    'total_ustad'   => $db->query("SELECT COUNT(*) FROM users WHERE role='ustad'")->fetchColumn(),
    'total_kelas'   => $db->query("SELECT COUNT(*) FROM kelas WHERE status='aktif'")->fetchColumn(),
    'total_materi'  => $db->query("SELECT COUNT(*) FROM materi WHERE status='publik'")->fetchColumn(),
    'total_parent'  => $db->query("SELECT COUNT(*) FROM users WHERE role='parent'")->fetchColumn(),
];

// Pengumuman terbaru
$pengumuman = $db->query("SELECT p.*, u.nama as penulis FROM pengumuman p 
    JOIN users u ON p.penulis_id = u.id 
    ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

// Santri terbaru
$santri_baru = $db->query("SELECT * FROM users WHERE role='santri' 
    ORDER BY created_at DESC LIMIT 5")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="welcome-banner">
    <div class="welcome-title">Selamat Datang, <?= sanitize($user['nama']) ?>! 👋</div>
    <div class="welcome-sub">Panel Admin NgajiKu — <?= date('l, d F Y') ?></div>
</div>

<!-- Statistik Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total_user'] ?></div>
                <div class="stat-label">Total Pengguna</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-user-graduate"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total_santri'] ?></div>
                <div class="stat-label">Santri</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fas fa-chalkboard-user"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total_ustad'] ?></div>
                <div class="stat-label">Ustad</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-user-tie"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total_parent'] ?></div>
                <div class="stat-label">Orang Tua</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-school"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total_kelas'] ?></div>
                <div class="stat-label">Kelas Aktif</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-book-open"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total_materi'] ?></div>
                <div class="stat-label">Materi</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Aksi Cepat -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-bolt me-2 text-warning"></i>Aksi Cepat</div>
            <div class="card-body d-flex flex-column gap-2 p-3">
                <a href="users.php?action=tambah" class="btn-primary-green w-100 justify-content-center">
                    <i class="fas fa-user-plus"></i>Tambah Pengguna
                </a>
                <a href="kelas.php?action=tambah" class="btn-outline-green w-100 justify-content-center">
                    <i class="fas fa-plus"></i>Buat Kelas
                </a>
                <a href="pengumuman.php?action=tambah" class="btn-outline-green w-100 justify-content-center">
                    <i class="fas fa-bullhorn"></i>Kirim Pengumuman
                </a>
                <a href="laporan.php" class="btn-outline-green w-100 justify-content-center">
                    <i class="fas fa-chart-bar"></i>Lihat Laporan
                </a>
            </div>
        </div>
    </div>

    <!-- Pengumuman Terbaru -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-bullhorn me-2"></i>Pengumuman Terbaru</span>
                <a href="pengumuman.php" class="btn-outline-green" style="padding:3px 10px;font-size:12px">Semua</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pengumuman)): ?>
                <div class="empty-state py-4"><i class="fas fa-bullhorn"></i><p>Belum ada pengumuman</p></div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($pengumuman as $p): ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom">
                        <div class="d-flex align-items-start gap-2">
                            <span class="badge bg-<?= $p['prioritas']==='darurat'?'danger':($p['prioritas']==='penting'?'warning':'secondary') ?> mt-1">
                                <?= ucfirst($p['prioritas']) ?>
                            </span>
                            <div>
                                <div style="font-size:13px;font-weight:600"><?= sanitize($p['judul']) ?></div>
                                <div style="font-size:11px;color:#888"><?= sanitize($p['penulis']) ?> · <?= formatTanggal($p['created_at']) ?></div>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Santri Terbaru -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-graduate me-2"></i>Santri Terbaru</span>
                <a href="users.php?role=santri" class="btn-outline-green" style="padding:3px 10px;font-size:12px">Semua</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($santri_baru)): ?>
                <div class="empty-state py-4"><i class="fas fa-user-graduate"></i><p>Belum ada santri</p></div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($santri_baru as $s): ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom d-flex align-items-center gap-2">
                        <div class="avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0">
                            <?= avatarInitial($s['nama']) ?>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:600"><?= sanitize($s['nama']) ?></div>
                            <div style="font-size:11px;color:#888">Daftar <?= formatTanggal($s['created_at']) ?></div>
                        </div>
                        <?= getStatusBadge($s['status']) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
