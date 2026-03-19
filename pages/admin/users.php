<?php
// pages/admin/users.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('admin');

$db = getDB();
$pageTitle = 'Kelola Pengguna';
$action    = $_GET['action'] ?? 'list';
$msg       = '';

// ===== PROSES FORM =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'tambah' || $act === 'edit') {
        $nama     = sanitize($_POST['nama'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? 'santri';
        $telepon  = sanitize($_POST['telepon'] ?? '');
        $alamat   = sanitize($_POST['alamat'] ?? '');
        $status   = $_POST['status'] ?? 'aktif';
        $password = $_POST['password'] ?? '';

        if (empty($nama) || empty($email)) {
            $msg = ['error', 'Nama dan email wajib diisi.'];
        } else {
            if ($act === 'tambah') {
                if (empty($password)) { $msg = ['error', 'Password wajib diisi untuk pengguna baru.']; }
                else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO users (nama,email,password,role,telepon,alamat,status) VALUES (?,?,?,?,?,?,?)");
                    $stmt->execute([$nama,$email,$hash,$role,$telepon,$alamat,$status]);
                    flashMessage('success', "Pengguna $nama berhasil ditambahkan.");
                    redirect('users.php');
                }
            } else {
                $id = (int)($_POST['id'] ?? 0);
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET nama=?,email=?,password=?,role=?,telepon=?,alamat=?,status=? WHERE id=?");
                    $stmt->execute([$nama,$email,$hash,$role,$telepon,$alamat,$status,$id]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET nama=?,email=?,role=?,telepon=?,alamat=?,status=? WHERE id=?");
                    $stmt->execute([$nama,$email,$role,$telepon,$alamat,$status,$id]);
                }
                flashMessage('success', "Data $nama berhasil diperbarui.");
                redirect('users.php');
            }
        }
    } elseif ($act === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        flashMessage('success', 'Pengguna berhasil dihapus.');
        redirect('users.php');
    }
}

// ===== DATA =====
$roleFilter = $_GET['role'] ?? '';
$search     = sanitize($_GET['q'] ?? '');
$where = "WHERE 1=1";
$params = [];
if ($roleFilter) { $where .= " AND role = ?"; $params[] = $roleFilter; }
if ($search)     { $where .= " AND (nama LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $db->prepare("SELECT * FROM users $where ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$editUser = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $editUser = $stmt->fetch();
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Kelola Pengguna</h4>
        <p class="page-subtitle">Tambah, edit, dan kelola semua pengguna platform</p>
    </div>
    <button class="btn-primary-green" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-user-plus"></i>Tambah Pengguna
    </button>
</div>

<!-- Filter & Search -->
<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <input type="text" name="q" class="form-control" style="max-width:240px"
                placeholder="Cari nama / email..." value="<?= sanitize($search) ?>">
            <select name="role" class="form-select" style="max-width:160px">
                <option value="">Semua Role</option>
                <option value="admin"  <?= $roleFilter==='admin'  ?'selected':'' ?>>Admin</option>
                <option value="ustad"  <?= $roleFilter==='ustad'  ?'selected':'' ?>>Ustad</option>
                <option value="parent" <?= $roleFilter==='parent' ?'selected':'' ?>>Orang Tua</option>
                <option value="santri" <?= $roleFilter==='santri' ?'selected':'' ?>>Santri</option>
            </select>
            <button type="submit" class="btn-primary-green"><i class="fas fa-search"></i>Cari</button>
            <a href="users.php" class="btn-outline-green">Reset</a>
        </form>
    </div>
</div>

<!-- Tabel -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pengguna</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Telepon</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="7">
                    <div class="empty-state"><i class="fas fa-users"></i><p>Tidak ada pengguna</p></div>
                </td></tr>
                <?php else: ?>
                <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle" style="width:34px;height:34px;font-size:12px;flex-shrink:0">
                                <?= avatarInitial($u['nama']) ?>
                            </div>
                            <strong><?= sanitize($u['nama']) ?></strong>
                        </div>
                    </td>
                    <td><?= sanitize($u['email']) ?></td>
                    <td>
                        <?php
                        $roleColors = ['admin'=>'primary','ustad'=>'success','parent'=>'warning','santri'=>'info'];
                        echo '<span class="badge bg-'.($roleColors[$u['role']]??'secondary').'">'.ucfirst($u['role']).'</span>';
                        ?>
                    </td>
                    <td><?= sanitize($u['telepon'] ?: '-') ?></td>
                    <td><?= getStatusBadge($u['status']) ?></td>
                    <td>
                        <a href="users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-secondary me-1">
                            <i class="fas fa-pencil"></i>
                        </a>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="act" value="hapus">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                data-confirm="Hapus pengguna <?= sanitize($u['nama']) ?>?">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-700">Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="act" value="tambah">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role *</label>
                            <select name="role" class="form-select">
                                <option value="santri">Santri</option>
                                <option value="ustad">Ustad</option>
                                <option value="parent">Orang Tua</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="telepon" class="form-control" placeholder="08xx">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Non-aktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-green">
                        <i class="fas fa-save"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editUser): ?>
<!-- Modal Edit (auto-open) -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">Edit Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="act" value="edit">
                <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="nama" class="form-control" value="<?= sanitize($editUser['nama']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" value="<?= sanitize($editUser['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <?php foreach (['santri','ustad','parent','admin'] as $r): ?>
                                <option value="<?= $r ?>" <?= $editUser['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Password Baru <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="telepon" class="form-control" value="<?= sanitize($editUser['telepon']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="aktif"    <?= $editUser['status']==='aktif'?'selected':'' ?>>Aktif</option>
                                <option value="nonaktif" <?= $editUser['status']==='nonaktif'?'selected':'' ?>>Non-aktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <a href="users.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary-green"><i class="fas fa-save"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
});
</script>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
