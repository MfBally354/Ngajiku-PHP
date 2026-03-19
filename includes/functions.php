<?php
// includes/functions.php

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function hasRole(string|array $roles): bool {
    $user = currentUser();
    if (!$user) return false;
    $roles = is_array($roles) ? $roles : [$roles];
    return in_array($user['role'], $roles);
}

function requireRole(string|array $roles): void {
    if (!isLoggedIn()) {
        redirect(SITE_URL . '/login.php');
    }
    if (!hasRole($roles)) {
        redirect(SITE_URL . '/login.php?error=akses_ditolak');
    }
}

function flashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function formatTanggal(string $tanggal, string $format = 'd M Y'): string {
    $bulan = [
        '01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr',
        '05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agu',
        '09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'
    ];
    $dt = date($format, strtotime($tanggal));
    foreach ($bulan as $en => $id) {
        $dt = str_replace(date('M', mktime(0,0,0,(int)$en,1)), $id, $dt);
    }
    return $dt;
}

function nilaiToHuruf(float $nilai): string {
    if ($nilai >= 90) return 'A';
    if ($nilai >= 80) return 'B';
    if ($nilai >= 70) return 'C';
    if ($nilai >= 60) return 'D';
    return 'E';
}

function nilaiToWarna(float $nilai): string {
    if ($nilai >= 80) return 'success';
    if ($nilai >= 60) return 'warning';
    return 'danger';
}

function uploadFile(array $file, string $folder): string|false {
    $allowedTypes = ['application/pdf','image/jpeg','image/png','image/gif',
                     'video/mp4','application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;
    if (!in_array($file['type'], $allowedTypes)) return false;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $dest = UPLOAD_PATH . $folder . '/' . $filename;

    if (!is_dir(UPLOAD_PATH . $folder)) {
        mkdir(UPLOAD_PATH . $folder, 0755, true);
    }
    return move_uploaded_file($file['tmp_name'], $dest) ? $filename : false;
}

function avatarInitial(string $nama): string {
    $parts = explode(' ', trim($nama));
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $initials .= strtoupper(substr(end($parts), 0, 1));
    return $initials;
}

function getStatusBadge(string $status): string {
    $map = [
        'hadir'  => ['success', 'Hadir'],
        'izin'   => ['info',    'Izin'],
        'sakit'  => ['warning', 'Sakit'],
        'alpha'  => ['danger',  'Alpha'],
        'aktif'  => ['success', 'Aktif'],
        'nonaktif' => ['secondary', 'Non-aktif'],
    ];
    $s = $map[$status] ?? ['secondary', ucfirst($status)];
    return '<span class="badge bg-' . $s[0] . '">' . $s[1] . '</span>';
}
