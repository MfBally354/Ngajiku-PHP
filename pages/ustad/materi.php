<?php
// pages/ustad/materi.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('ustad');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Kelola Materi';

// Ambil kelas milik ustad ini
$kelasList = $db->prepare("SELECT * FROM kelas WHERE ustad_id = ? AND status='aktif' ORDER BY nama_kelas");
$kelasList->execute([$uid]);
$kelasList = $kelasList->fetchAll();

$kategoriList = $db->query("SELECT * FROM kategori_materi ORDER BY nama")->fetchAll();

// ===== PROSES =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'tambah' || $act === 'edit') {
        $judul       = sanitize($_POST['judul'] ?? '');
        $konten      = $_POST['konten'] ?? '';
        $kategori_id = (int)($_POST['kategori_id'] ?? 0);
        $kelas_id    = (int)($_POST['kelas_id'] ?? 0) ?: null;
        $tipe        = $_POST['tipe_file'] ?? 'teks';
        $link        = sanitize($_POST['link_eksternal'] ?? '');
        $status      = $_POST['status'] ?? 'publik';
        $file_path   = null;

        if (!empty($_FILES['file']['name'])) {
            $uploaded = uploadFile($_FILES['file'], 'materi');
            if ($uploaded) $file_path = $uploaded;
        }

        if (empty($judul)) {
            flashMessage('error', 'Judul materi wajib diisi.');
            redirect('materi.php');
        }

        if ($act === 'tambah') {
            $stmt = $db->prepare("INSERT INTO materi (judul,konten,kategori_id,kelas_id,ustad_id,file_path,tipe_file,link_eksternal,status) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$judul,$konten,$kategori_id,$kelas_id,$uid,$file_path,$tipe,$link,$status]);
            flashMessage('success', 'Materi berhasil ditambahkan.');
        } else {
            $id = (int)($_POST['id'] ?? 0);
            if ($file_path) {
                $stmt = $db->prepare("UPDATE materi SET judul=?,konten=?,kategori_id=?,kelas_id=?,file_path=?,tipe_file=?,link_eksternal=?,status=? WHERE id=? AND ustad_id=?");
                $stmt->execute([$judul,$konten,$kategori_id,$kelas_id,$file_path,$tipe,$link,$status,$id,$uid]);
            } else {
                $stmt = $db->prepare("UPDATE materi SET judul=?,konten=?,kategori_id=?,kelas_id=?,tipe_file=?,link_eksternal=?,status=? WHERE id=? AND ustad_id=?");
                $stmt->execute([$judul,$konten,$kategori_id,$kelas_id,$tipe,$link,$status,$id,$uid]);
            }
            flashMessage('success', 'Materi berhasil diperbarui.');
        }
        redirect('materi.php');

    } elseif ($act === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM materi WHERE id=? AND ustad_id=?")->execute([$id,$uid]);
        flashMessage('success', 'Materi berhasil dihapus.');
        redirect('materi.php');
    }
}

// ===== FETCH DATA =====
$filterKat  = (int)($_GET['kategori'] ?? 0);
$filterKls  = (int)($_GET['kelas'] ?? 0);
$search     = sanitize($_GET['q'] ?? '');
$where = "WHERE m.ustad_id = ?";
$params = [$uid];
if ($filterKat) { $where .= " AND m.kategori_id = ?"; $params[] = $filterKat; }
if ($filterKls) { $where .= " AND m.kelas_id = ?";    $params[] = $filterKls; }
if ($search)    { $where .= " AND m.judul LIKE ?";      $params[] = "%$search%"; }

$stmt = $db->prepare("SELECT m.*, k.nama as nama_kategori, kl.nama_kelas 
    FROM materi m
    LEFT JOIN kategori_materi k ON m.kategori_id = k.id
    LEFT JOIN kelas kl ON m.kelas_id = kl.id
    $where ORDER BY m.created_at DESC");
$stmt->execute($params);
$materiList = $stmt->fetchAll();

$editMateri = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM materi WHERE id=? AND ustad_id=?");
    $stmt->execute([(int)$_GET['edit'], $uid]);
    $editMateri = $stmt->fetch();
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Kelola Materi</h4>
        <p class="page-subtitle">Unggah dan atur materi pembelajaran santri</p>
    </div>
    <button class="btn-primary-green" data-bs-toggle="modal" data-bs-target="#modalMateri">
        <i class="fas fa-plus"></i>Tambah Materi
    </button>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="text" name="q" class="form-control" style="max-width:220px"
                placeholder="Cari judul..." value="<?= sanitize($search) ?>">
            <select name="kategori" class="form-select" style="max-width:180px">
                <option value="">Semua Kategori</option>
                <?php foreach ($kategoriList as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $filterKat==$k['id']?'selected':'' ?>>
                    <?= sanitize($k['nama']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select name="kelas" class="form-select" style="max-width:180px">
                <option value="">Semua Kelas</option>
                <?php foreach ($kelasList as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $filterKls==$k['id']?'selected':'' ?>>
                    <?= sanitize($k['nama_kelas']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary-green"><i class="fas fa-filter"></i>Filter</button>
            <a href="materi.php" class="btn-outline-green">Reset</a>
        </form>
    </div>
</div>

<!-- Daftar Materi -->
<?php if (empty($materiList)): ?>
<div class="card"><div class="empty-state py-5">
    <i class="fas fa-book-open"></i>
    <p>Belum ada materi. Klik <strong>Tambah Materi</strong> untuk memulai.</p>
</div></div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($materiList as $m): ?>
    <div class="col-md-6 col-lg-4">
        <div class="materi-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="materi-icon">
                    <?php
                    $icons = ['pdf'=>'fa-file-pdf','video'=>'fa-play-circle','gambar'=>'fa-image',
                              'link'=>'fa-link','teks'=>'fa-file-lines'];
                    echo '<i class="fas '.($icons[$m['tipe_file']]??'fa-file').'"></i>';
                    ?>
                </div>
                <span class="badge bg-<?= $m['status']==='publik'?'success':'secondary' ?>">
                    <?= ucfirst($m['status']) ?>
                </span>
            </div>
            <div class="materi-title"><?= sanitize($m['judul']) ?></div>
            <div class="materi-meta mb-2">
                <?php if ($m['nama_kategori']): ?>
                <span class="kategori-pill" style="font-size:11px;padding:2px 8px">
                    <?= sanitize($m['nama_kategori']) ?>
                </span>
                <?php endif; ?>
                <?php if ($m['nama_kelas']): ?>
                · <small><?= sanitize($m['nama_kelas']) ?></small>
                <?php endif; ?>
            </div>
            <div class="materi-meta"><?= formatTanggal($m['created_at']) ?></div>
            <div class="d-flex gap-1 mt-3">
                <a href="materi.php?edit=<?= $m['id'] ?>" class="btn btn-sm btn-outline-secondary flex-fill">
                    <i class="fas fa-pencil"></i> Edit
                </a>
                <form method="POST" class="flex-fill">
                    <input type="hidden" name="act" value="hapus">
                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100"
                        data-confirm="Hapus materi ini?">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal Tambah/Edit Materi -->
<?php $isEdit = $editMateri !== null; ?>
<div class="modal fade <?= $isEdit ? 'show' : '' ?>" id="modalMateri" tabindex="-1"
    <?= $isEdit ? 'style="display:block"' : '' ?>>
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><?= $isEdit ? 'Edit Materi' : 'Tambah Materi Baru' ?></h5>
                <a href="materi.php" class="btn-close"></a>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="act" value="<?= $isEdit ? 'edit' : 'tambah' ?>">
                <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $editMateri['id'] ?>">
                <?php endif; ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Judul Materi *</label>
                            <input type="text" name="judul" class="form-control" required
                                value="<?= sanitize($editMateri['judul'] ?? '') ?>"
                                placeholder="cth: Tajwid — Hukum Nun Mati">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select name="kategori_id" class="form-select">
                                <option value="">— Pilih Kategori —</option>
                                <?php foreach ($kategoriList as $k): ?>
                                <option value="<?= $k['id'] ?>"
                                    <?= ($editMateri['kategori_id']??'') == $k['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($k['nama']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kelas <small class="text-muted">(opsional)</small></label>
                            <select name="kelas_id" class="form-select">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($kelasList as $k): ?>
                                <option value="<?= $k['id'] ?>"
                                    <?= ($editMateri['kelas_id']??'') == $k['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($k['nama_kelas']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipe Konten</label>
                            <select name="tipe_file" class="form-select" id="tipeSelect">
                                <?php
                                $tipes = ['teks'=>'Teks/HTML','pdf'=>'File PDF','video'=>'Video',
                                          'gambar'=>'Gambar','link'=>'Link Eksternal'];
                                foreach ($tipes as $val => $lbl): ?>
                                <option value="<?= $val ?>"
                                    <?= ($editMateri['tipe_file']??'teks') === $val ? 'selected' : '' ?>>
                                    <?= $lbl ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="publik"  <?= ($editMateri['status']??'publik')==='publik'?'selected':'' ?>>Publik</option>
                                <option value="draft"   <?= ($editMateri['status']??'')==='draft'?'selected':'' ?>>Draft</option>
                            </select>
                        </div>
                        <div class="col-12" id="kontenArea">
                            <label class="form-label">Isi Materi</label>
                            <textarea name="konten" class="form-control" rows="6"
                                placeholder="Tuliskan isi materi di sini..."><?= htmlspecialchars($editMateri['konten'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12" id="fileArea" style="display:none">
                            <label class="form-label">Upload File <small class="text-muted">(PDF, gambar, video — maks 10MB)</small></label>
                            <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.mp4">
                            <?php if (!empty($editMateri['file_path'])): ?>
                            <small class="text-muted">File saat ini: <?= sanitize($editMateri['file_path']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-12" id="linkArea" style="display:none">
                            <label class="form-label">Link Eksternal</label>
                            <input type="url" name="link_eksternal" class="form-control"
                                placeholder="https://youtube.com/..."
                                value="<?= sanitize($editMateri['link_eksternal'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <a href="materi.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary-green">
                        <i class="fas fa-save"></i>Simpan Materi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if ($isEdit): ?>
<div class="modal-backdrop fade show"></div>
<style>body { overflow: hidden; }</style>
<?php endif; ?>

<script>
const tipeSelect = document.getElementById('tipeSelect');
function updateFields() {
    const v = tipeSelect.value;
    document.getElementById('kontenArea').style.display = (v==='teks') ? '' : 'none';
    document.getElementById('fileArea').style.display   = ['pdf','video','gambar'].includes(v) ? '' : 'none';
    document.getElementById('linkArea').style.display   = (v==='link') ? '' : 'none';
}
tipeSelect?.addEventListener('change', updateFields);
updateFields();
</script>

<?php require_once '../../includes/footer.php'; ?>
