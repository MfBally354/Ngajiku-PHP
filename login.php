<?php
// login.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/router.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'aktif' LIMIT 1"); 
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user']    = [
                'id'    => $user['id'],
                'nama'  => $user['nama'],
                'email' => $user['email'],
                'role'  => $user['role'],
                'foto'  => $user['foto'],
            ];
            redirect(SITE_URL . '/router.php');
        } else {
            $error = 'Email atau password salah, atau akun tidak aktif.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — NgajiKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --green: #2E7D32; --green-light: #66BB6A; --green-bg: #E8F5E9; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #F5F5F5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 1000px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(0,0,0,.12);
            overflow: hidden;
            display: flex;
            min-height: 560px;
        }
        .login-left {
            flex: 1;
            background: linear-gradient(160deg, #2E7D32 0%, #388E3C 60%, #66BB6A 100%);
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .login-left::before {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            top: -60px; right: -80px;
        }
        .login-left::after {
            content: '';
            position: absolute;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            bottom: -40px; left: -60px;
        }
        .brand-logo {
            width: 60px; height: 60px;
            background: rgba(255,255,255,.2);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
        }
        .login-left h1 { font-size: 28px; font-weight: 800; margin-bottom: 10px; }
        .login-left p  { font-size: 14px; opacity: .85; margin-bottom: 32px; }
        .arabic-motto  { font-family: 'Amiri', serif; font-size: 20px; opacity: .9; direction: rtl; margin-bottom: 6px; }
        .role-list li  { font-size: 13px; opacity: .8; padding: 4px 0; list-style: none; }
        .role-list li i { margin-right: 8px; }
        .login-right {
            width: 400px;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-right h2 { font-size: 22px; font-weight: 700; color: #222; margin-bottom: 6px; }
        .login-right p  { font-size: 14px; color: #888; margin-bottom: 28px; }
        .form-label     { font-weight: 600; font-size: 13px; color: #444; }
        .form-control {
            border-radius: 8px;
            padding: 10px 14px;
            border: 1.5px solid #ddd;
            font-size: 14px;
            transition: border-color .2s;
        }
        .form-control:focus {
            border-color: var(--green-light);
            box-shadow: 0 0 0 3px rgba(46,125,50,.12);
        }
        .input-group .form-control { border-right: 0; }
        .input-group .btn { border: 1.5px solid #ddd; border-left: 0; border-radius: 0 8px 8px 0; background: #f9f9f9; color: #888; }
        .btn-login {
            width: 100%;
            background: var(--green);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s;
            margin-top: 8px;
        }
        .btn-login:hover { background: #388E3C; }
        .demo-box {
            background: var(--green-bg);
            border-radius: 8px;
            padding: 14px 16px;
            margin-top: 20px;
            font-size: 12px;
            color: #444;
        }
        .demo-box strong { color: var(--green); }
        @media (max-width: 640px) {
            .login-left { display: none; }
            .login-right { width: 100%; padding: 32px 24px; }
        }
    </style>
</head>
<body>
<div class="login-container">
    <!-- Left Panel -->
    <div class="login-left">
        <div class="brand-logo"><i class="fas fa-mosque"></i></div>
        <h1>NgajiKu</h1>
        <p class="arabic-motto">طَلَبُ الْعِلْمِ فَرِيضَةٌ عَلَى كُلِّ مُسْلِمٍ</p>
        <p>"Menuntut ilmu itu wajib atas setiap Muslim"</p>
        <ul class="role-list">
            <li><i class="fas fa-shield-halved"></i>Admin — kelola seluruh platform</li>
            <li><i class="fas fa-chalkboard-user"></i>Ustad — ajar, nilai, pantau santri</li>
            <li><i class="fas fa-user-tie"></i>Orang Tua — pantau perkembangan anak</li>
            <li><i class="fas fa-user-graduate"></i>Santri — belajar & lihat nilai</li>
        </ul>
    </div>

    <!-- Right Panel (Form) -->
    <div class="login-right">
        <h2>Assalamu'alaikum 👋</h2>
        <p>Silakan masuk ke akun NgajiKu Anda</p>

        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 mb-3" style="font-size:13px;border-radius:8px">
            <i class="fas fa-circle-exclamation"></i>
            <?= sanitize($error) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'akses_ditolak'): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 py-2 px-3 mb-3" style="font-size:13px;border-radius:8px">
            <i class="fas fa-triangle-exclamation"></i>
            Akses ditolak. Silakan login dengan akun yang sesuai.
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                    placeholder="contoh@email.com"
                    value="<?= sanitize($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" name="password" class="form-control" id="pwInput"
                        placeholder="Masukkan password" required>
                    <button type="button" class="btn" onclick="togglePw()">
                        <i class="fas fa-eye" id="pwIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-right-to-bracket me-2"></i>Masuk
            </button>
        </form>

        <div class="demo-box">
            <strong>Akun Demo:</strong><br>
            admin@ngajiku.id / ahmad@ngajiku.id / budi@ngajiku.id / rafi@ngajiku.id<br>
            Password semua: <strong>password123</strong>
        </div>
    </div>
</div>

<script>
function togglePw() {
    const input = document.getElementById('pwInput');
    const icon  = document.getElementById('pwIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
</body>
</html>
