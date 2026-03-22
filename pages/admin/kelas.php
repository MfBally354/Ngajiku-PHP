<?php
// pages/admin/kelas.php
require_once '../../includes/auth_check.php';
requireRole('admin');

$db = getDB();
$pageTitle = 'Kelola Kelas';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'tambah' || $act === 'edit') {
        $nama     = sanitize($_POST['nama_kelas'] ?? '');
        $deskripsi= sanitize($_POST['deskripsi'] ?? '');
        $ustad_id = (int)$_POST['ustad_id'];
        $jadwal   = sanitize($_POST['jadwal'] ?? '');
        $lokasi   = sanitize($_POST['lokasi'] ?? '');
        $status   = $_POST['status'] ?? 'aktif';

        if (empty($nama)) {
            flashMessage('error', 'Nama kelas wajib diisi.');
            redirect('kelas.php');
        }

        if ($act === 'tambah') {
            $stmt = $db->prepare("INSERT INTO kelas (nama_kelas,deskripsi,ustad_id,jadwal,lokasi,status) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$nama,$deskripsi,$ustad_id,$jadwal,$lokasi,$status]);
            flashMessage('success', "Kelas $nama berhasil ditambahkan.");
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE kelas SET nama_kelas=?,deskripsi=?,ustad_id=?,jadwal=?,lokasi=?,status=? WHERE id=?");
            $stmt->execute([$nama,$deskripsi,$ustad_id,$jadwal,$lokasi,$status,$id]);
            flashMessage('success', "Kelas $nama berhasil diperbarui.");
        }
        redirect('kelas.php');
    }

    if ($act === 'hapus') {
        $db->prepare("DELETE FROM kelas WHERE id=?")->execute([(int)$_POST['id']]);
        flashMessage('success', 'Kelas berhasil dihapus.');
        redirect('kelas.php');
    }

    if ($act === 'tambah_santri') {
        $kelas_id  = (int)$_POST['kelas_id'];
        $santri_id = (int)$_POST['santri_id'];
        try {
            $db->prepare("INSERT IGNORE INTO santri_kelas (santri_id,kelas_id) VALUES (?,?)")->execute([$santri_id,$kelas_id]);
            flashMessage('success', 'Santri berhasil ditambahkan ke kelas.');
        } catch (Exception $e) {
            flashMessage('error', 'Santri sudah ada di kelas ini.');
        }
        redirect('kelas.php?detail='.$kelas_id);
    }

    if ($act === 'keluarkan_santri') {
        $db->prepare("DELETE FROM santri_kelas WHERE santri_id=? AND kelas_id=?")->execute([(int)$_POST['santri_id'],(int)$_POST['kelas_id']]);
        flashMessage('success', 'Santri berhasil dikeluarkan dari kelas.');
        redirect('kelas.php?detail='.(int)$_POST['kelas_id']);
    }
}

// Daftar ustad untuk dropdown
$ustadList = $db->query("SELECT id,nama FROM users WHERE role='ustad' AND status='aktif' ORDER BY nama")->fetchAll();

// Daftar santri untuk dropdown
$santriAll = $db->query("SELECT id,nama FROM users WHERE role='santri' AND status='aktif' ORDER BY nama")->fetchAll();

// Data kelas dengan jumlah santri
$kelasList = $db->query("SELECT k.*, u.nama as nama_ustad, COUNT(sk.santri_id) as jml_santri
    FROM kelas k
    LEFT JOIN users u ON k.ustad_id=u.id
    LEFT JOIN santri_kelas sk ON k.id=sk.kelas_id
    GROUP BY k.id ORDER BY k.status DESC, k.nama_kelas")->fetchAll();

// Detail kelas (jika dipilih)
$detailKelas = null;
$santriDiKelas = [];
$santriTersedia = [];
if (isset($_GET['detail'])) {
    $kid = (int)$_GET['detail'];
    $stmt = $db->prepare("SELECT k.*, u.nama as nama_ustad FROM kelas k LEFT JOIN users u ON k.ustad_id=u.id WHERE k.id=?");
    $stmt->execute([$kid]);
    $detailKelas = $stmt->fetch();

    $stmt = $db->prepare("SELECT u.* FROM users u JOIN santri_kelas sk ON u.id=sk.santri_id WHERE sk.kelas_id=? ORDER BY u.nama");
    $stmt->execute([$kid]);
    $santriDiKelas = $stmt->fetchAll();

    // Santri yang belum di kelas ini
    $idsDiKelas = array_column($santriDiKelas, 'id');
    $santriTersedia = array_filter($santriAll, fn($s) => !in_array($s['id'], $idsDiKelas));
}

$editKelas = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM kelas WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editKelas = $stmt->fetch();
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Kelola Kelas</h4>
        <p class="page-subtitle">Buat kelas, atur ustad pengampu, dan daftarkan santri</p>
    </div>
    <button class="btn-primary-green" data-bs-toggle="modal" data-bs-target="#modalKelas">
        <i class="fas fa-plus"></i>Tambah Kelas
    </button>
</div>

<?php if ($detailKelas): ?>
<!-- ===== DETAIL KELAS ===== -->
<div class="mb-3">
    <a href="kelas.php" class="btn-outline-green" style="padding:6px 14px">
        <i class="fas fa-arrow-left"></i>Kembali
    </a>
</div>
<div class="card mb-4" style="border-left:4px solid #2E7D32">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 style="font-weight:700;margin-bottom:4px"><?= sanitize($detailKelas['nama_kelas']) ?></h5>
            <div style="font-size:13px;color:#888">
                <i class="fas fa-chalkboard-user me-1"></i><?= sanitize($detailKelas['nama_ustad'] ?? '-') ?>
                <?php if ($detailKelas['jadwal']): ?>
                &nbsp;·&nbsp;<i class="fas fa-clock me-1"></i><?= sanitize($detailKelas['jadwal']) ?>
                <?php endif; ?>
                <?php if ($detailKelas['lokasi']): ?>
                &nbsp;·&nbsp;<i class="fas fa-location-dot me-1"></i><?= sanitize($detailKelas['lokasi']) ?>
                <?php endif; ?>
            </div>
        </div>
        <?= getStatusBadge($detailKelas['status']) ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2"></i>Santri di Kelas Ini (<?= count($santriDiKelas) ?>)</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>#</th><th>Santri</th><th>Email</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if (empty($santriDiKelas)): ?>
                        <tr><td colspan="4"><div class="empty-state py-3"><p>Belum ada santri</p></div></td></tr>
                        <?php else: ?>
                        <?php foreach ($santriDiKelas as $i => $s): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle" style="width:30px;height:30px;font-size:11px;flex-shrink:0"><?= avatarInitial($s['nama']) ?></div>
                                    <strong><?= sanitize($s['nama']) ?></strong>
                                </div>
                            </td>
                            <td><?= sanitize($s['email']) ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="act" value="keluarkan_santri">
                                    <input type="hidden" name="santri_id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="kelas_id" value="<?= $detailKelas['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                        data-confirm="Keluarkan <?= sanitize($s['nama']) ?> dari kelas ini?">
                                        <i class="fas fa-user-minus"></i>
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
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-user-plus me-2"></i>Tambah Santri</div>
            <div class="card-body">
                <?php if (empty($santriTersedia)): ?>
                <p class="text-muted" style="font-size:13px">Semua santri sudah terdaftar di kelas ini.</p>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="act" value="tambah_santri">
                    <input type="hidden" name="kelas_id" value="<?= $detailKelas['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Pilih Santri</label>
                        <select name="santri_id" class="form-select" required>
                            <option value="">— Pilih —</option>
                            <?php foreach ($santriTersedia as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= sanitize($s['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary-green w-100 justify-content-center">
                        <i class="fas fa-user-plus"></i>Tambahkan
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ===== LIST KELAS ===== -->
<div class="row g-3">
    <?php if (empty($kelasList)): ?>
    <div class="col-12"><div class="card"><div class="empty-state py-5">
        <i class="fas fa-school"></i><p>Belum ada kelas. Klik <strong>Tambah Kelas</strong>.</p>
    </div></div></div>
    <?php else: ?>
    <?php foreach ($kelasList as $k): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <?= getStatusBadge($k['status']) ?>
                    <span class="badge bg-success"><?= $k['jml_santri'] ?> santri</span>
                </div>
                <h6 style="font-weight:700;margin-bottom:6px"><?= sanitize($k['nama_kelas']) ?></h6>
                <p style="font-size:13px;color:#666;margin-bottom:8px"><?= sanitize(substr($k['deskripsi'],0,80)) ?></p>
                <div style="font-size:12px;color:#888;margin-bottom:12px">
                    <i class="fas fa-chalkboard-user me-1"></i><?= sanitize($k['nama_ustad'] ?? '-') ?>
                    <?php if ($k['jadwal']): ?>
                    &nbsp;·&nbsp;<i class="fas fa-clock me-1"></i><?= sanitize($k['jadwal']) ?>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <a href="kelas.php?detail=<?= $k['id'] ?>" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-users"></i> Santri
                    </a>
                    <a href="kelas.php?edit=<?= $k['id'] ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-pencil"></i>
                    </a>
                    <form method="POST" class="ms-auto">
                        <input type="hidden" name="act" value="hapus">
                        <input type="hidden" name="id" value="<?= $k['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                            data-confirm="Hapus kelas <?= sanitize($k['nama_kelas']) ?>? Semua data terkait akan ikut terhapus!">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Modal Tambah / Edit -->
<?php $isEdit = $editKelas !== null; ?>
<div class="modal fade <?= $isEdit?'show':'' ?>" id="modalKelas" tabindex="-1"
    <?= $isEdit?'style="display:block"':'' ?>>
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><?= $isEdit?'Edit Kelas':'Tambah Kelas' ?></h5>
                <a href="kelas.php" class="btn-close"></a>
            </div>
            <form method="POST">
                <input type="hidden" name="act" value="<?= $isEdit?'edit':'tambah' ?>">
                <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $editKelas['id'] ?>"><?php endif; ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Kelas *</label>
                            <input type="text" name="nama_kelas" class="form-control" required
                                value="<?= sanitize($editKelas['nama_kelas'] ?? '') ?>"
                                placeholder="cth: Kelas Iqra Dasar, Kelas Tajwid Lanjutan">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ustad Pengampu *</label>
                            <select name="ustad_id" class="form-select" required>
                                <option value="">— Pilih Ustad —</option>
                                <?php foreach ($ustadList as $u): ?>
                                <option value="<?= $u['id'] ?>"
                                    <?= ($editKelas['ustad_id']??'') == $u['id'] ? 'selected':'' ?>>
                                    <?= sanitize($u['nama']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jadwal</label>
                            <input type="text" name="jadwal" class="form-control"
                                value="<?= sanitize($editKelas['jadwal'] ?? '') ?>"
                                placeholder="cth: Sabtu 08.00–10.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control"
                                value="<?= sanitize($editKelas['lokasi'] ?? '') ?>"
                                placeholder="cth: Masjid Al-Ikhlas">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="2"
                                placeholder="Keterangan singkat tentang kelas..."><?= sanitize($editKelas['deskripsi'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="aktif" <?= ($editKelas['status']??'aktif')==='aktif'?'selected':'' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($editKelas['status']??'')==='nonaktif'?'selected':'' ?>>Non-aktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <a href="kelas.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary-green"><i class="fas fa-save"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if ($isEdit): ?>
<div class="modal-backdrop fade show"></div>
<style>body{overflow:hidden}</style>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
