<?php
// includes/header.php
// Dipanggil di semua halaman setelah auth_check
$user = currentUser();
$flash = getFlash();
$pageTitle = $pageTitle ?? SITE_NAME;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> — <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-mosque"></i>
        </div>
        <div class="brand-text">
            <span class="brand-name"><?= SITE_NAME ?></span>
            <span class="brand-tagline">Platform Ngaji Digital</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php
        $role = $user['role'];
        $base = SITE_URL . '/pages/' . $role;
        $currentPage = basename($_SERVER['PHP_SELF']);

        // Menu sesuai role
        $menus = [];

        if ($role === 'admin') {
            $menus = [
                ['icon'=>'fa-gauge',      'label'=>'Dashboard',     'url'=>$base.'/dashboard.php'],
                ['icon'=>'fa-users',       'label'=>'Kelola User',   'url'=>$base.'/users.php'],
                ['icon'=>'fa-school',      'label'=>'Kelola Kelas',  'url'=>$base.'/kelas.php'],
                ['icon'=>'fa-book',        'label'=>'Materi',        'url'=>$base.'/materi.php'],
                ['icon'=>'fa-bullhorn',    'label'=>'Pengumuman',    'url'=>$base.'/pengumuman.php'],
                ['icon'=>'fa-chart-bar',   'label'=>'Laporan',       'url'=>$base.'/laporan.php'],
            ];
        } elseif ($role === 'ustad') {
            $menus = [
                ['icon'=>'fa-gauge',       'label'=>'Dashboard',     'url'=>$base.'/dashboard.php'],
                ['icon'=>'fa-chalkboard',  'label'=>'Kelas Saya',    'url'=>$base.'/kelas.php'],
                ['icon'=>'fa-book-open',   'label'=>'Materi',        'url'=>$base.'/materi.php'],
                ['icon'=>'fa-tasks',       'label'=>'Tugas',         'url'=>$base.'/tugas.php'],
                ['icon'=>'fa-star',        'label'=>'Nilai',         'url'=>$base.'/nilai.php'],
                ['icon'=>'fa-calendar-check','label'=>'Absensi',     'url'=>$base.'/absensi.php'],
                ['icon'=>'fa-scroll',      'label'=>'Hafalan',       'url'=>$base.'/hafalan.php'],
                ['icon'=>'fa-bullhorn',    'label'=>'Pengumuman',    'url'=>$base.'/pengumuman.php'],
            ];
        } elseif ($role === 'parent') {
            $menus = [
                ['icon'=>'fa-gauge',       'label'=>'Dashboard',     'url'=>$base.'/dashboard.php'],
                ['icon'=>'fa-child',       'label'=>'Anak Saya',     'url'=>$base.'/anak.php'],
                ['icon'=>'fa-book-open',   'label'=>'Materi',        'url'=>$base.'/materi.php'],
                ['icon'=>'fa-star',        'label'=>'Nilai Anak',    'url'=>$base.'/nilai_anak.php'],
                ['icon'=>'fa-calendar-check','label'=>'Absensi',     'url'=>$base.'/absensi.php'],
                ['icon'=>'fa-bullhorn',    'label'=>'Pengumuman',    'url'=>$base.'/pengumuman.php'],
            ];
        } elseif ($role === 'santri') {
            $menus = [
                ['icon'=>'fa-gauge',       'label'=>'Dashboard',     'url'=>$base.'/dashboard.php'],
                ['icon'=>'fa-book-open',   'label'=>'Materi',        'url'=>$base.'/materi.php'],
                ['icon'=>'fa-tasks',       'label'=>'Tugas Saya',    'url'=>$base.'/tugas.php'],
                ['icon'=>'fa-star',        'label'=>'Nilai Saya',    'url'=>$base.'/nilai.php'],
                ['icon'=>'fa-scroll',      'label'=>'Hafalan',       'url'=>$base.'/hafalan.php'],
                ['icon'=>'fa-calendar-check','label'=>'Absensi',     'url'=>$base.'/absensi.php'],
                ['icon'=>'fa-bullhorn',    'label'=>'Pengumuman',    'url'=>$base.'/pengumuman.php'],
            ];
        }

        foreach ($menus as $menu):
            $active = (basename($menu['url']) === $currentPage) ? 'active' : '';
        ?>
        <a href="<?= $menu['url'] ?>" class="nav-item <?= $active ?>">
            <i class="fas <?= $menu['icon'] ?> nav-icon"></i>
            <span><?= $menu['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= SITE_URL ?>/logout.php" class="nav-item text-danger">
            <i class="fas fa-right-from-bracket nav-icon"></i>
            <span>Keluar</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-wrapper">
    <!-- Topbar -->
    <header class="topbar">
        <button class="btn btn-icon sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <div class="topbar-right">
            <?php if ($flash): ?>
            <div id="flashToast" class="toast-inline toast-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
                <?= sanitize($flash['message']) ?>
            </div>
            <?php endif; ?>

            <div class="role-badge role-<?= $role ?>">
                <i class="fas fa-<?= ['admin'=>'shield-halved','ustad'=>'chalkboard-user','parent'=>'user-tie','santri'=>'user-graduate'][$role] ?>"></i>
                <?= ucfirst($role) ?>
            </div>

            <div class="user-menu dropdown">
                <button class="user-avatar-btn dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="avatar-circle">
                        <?= avatarInitial($user['nama']) ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= sanitize($user['nama']) ?></span>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= SITE_URL ?>/pages/<?= $role ?>/profil.php">
                        <i class="fas fa-user me-2"></i>Profil Saya</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/logout.php">
                        <i class="fas fa-right-from-bracket me-2"></i>Keluar</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="page-content">
