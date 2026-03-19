<?php
// profil.php — template universal, disimpan di masing-masing folder role
// Deteksi folder berdasarkan path
session_start();
require_once '../../includes/auth_check.php';

$db   = getDB();
$uid  = $user['id'];
$pageTitle = 'Profil Saya';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'update_profil') {
        $nama    = sanitize($_POST['nama'] ?? '');
        $telepon = sanitize($_POST['telepon'] ?? '');
        $alamat  = sanitize($_POST['alamat'] ?? '');

        if (empty($nama)) {
            flashMessage('error', 'Nama tidak boleh kosong.');
        } else {
            $stmt = $db->prepare("UPDATE users SET nama=?,telepon=?,alamat=? WHERE id=?");
            $stmt->execute([$nama,$telepon,$alamat,$uid]);
            // Update session
            $_SESSION['user']['nama'] = $nama;
            flashMessage('success', 'Profil berhasil diperbarui.');
        }
        redirect($_SERVER['PHP_SELF']);
    }

    if ($act === 'ganti_password') {
        $old  = $_POST['password_lama'] ?? '';
        $new  = $_POST['password_baru'] ?? '';
        $conf = $_POST['konfirmasi'] ?? '';

        $stmt = $db->prepare("SELECT password FROM users WHERE id=?");
        $stmt->execute([$uid]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($old, $hash)) {
            flashMessage('error', 'Password lama salah.');
        } elseif (strlen($new) < 6) {
            flashMessage('error', 'Password baru minimal 6 karakter.');
        } elseif ($new !== $conf) {
            flashMessage('error', 'Konfirmasi password tidak cocok.');
        } else {
            $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$uid]);
            flashMessage('success', 'Password berhasil diubah.');
        }
        redirect($_SERVER['PHP_SELF']);
    }
}

// Ambil data terbaru dari DB
$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$uid]);
$userData = $stmt->fetch();

require_once '../../includes/header.php';
?>

<h4 class="page-title mb-1">Profil Saya</h4>
<p class="page-subtitle mb-4">Kelola informasi akun Anda</p>

<div class="row g-4">
    <!-- Info Profil -->
    <div class="col-md-4">
        <div class="card text-center p-4">
            <div class="avatar-circle mx-auto mb-3" style="width:80px;height:80px;font-size:32px">
                <?= avatarInitial($userData['nama']) ?>
            </div>
            <h5 style="font-weight:700"><?= sanitize($userData['nama']) ?></h5>
            <p style="font-size:13px;color:#888"><?= sanitize($userData['email']) ?></p>
            <div class="mb-2">
                <?php
                $roleBadge = ['admin'=>['primary','Admin'],'ustad'=>['success','Ustad'],'parent'=>['warning','Orang Tua'],'santri'=>['info','Santri']];
                $rb = $roleBadge[$userData['role']] ?? ['secondary',ucfirst($userData['role'])];
                ?>
                <span class="badge bg-<?= $rb[0] ?>"><?= $rb[1] ?></span>
                <?= getStatusBadge($userData['status']) ?>
            </div>
            <p style="font-size:12px;color:#aaa">Bergabung: <?= formatTanggal($userData['created_at']) ?></p>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Form Edit Profil -->
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user-pen me-2"></i>Edit Profil</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="act" value="update_profil">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="nama" class="form-control"
                                value="<?= sanitize($userData['nama']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control"
                                value="<?= sanitize($userData['email']) ?>" disabled>
                            <div class="form-text">Email tidak dapat diubah. Hubungi admin jika perlu.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" name="telepon" class="form-control"
                                value="<?= sanitize($userData['telepon'] ?? '') ?>"
                                placeholder="cth: 0812345678">
                        </div>
                        <div class="col-md-6"></div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2"
                                placeholder="Alamat lengkap..."><?= sanitize($userData['alamat'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-primary-green">
                                <i class="fas fa-save"></i>Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ganti Password -->
        <div class="card">
            <div class="card-header"><i class="fas fa-lock me-2"></i>Ganti Password</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="act" value="ganti_password">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Password Lama *</label>
                            <input type="password" name="password_lama" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password Baru * <small class="text-muted">(min. 6 karakter)</small></label>
                            <input type="password" name="password_baru" class="form-control" required minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Konfirmasi Password Baru *</label>
                            <input type="password" name="konfirmasi" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-outline-green">
                                <i class="fas fa-key"></i>Ganti Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
