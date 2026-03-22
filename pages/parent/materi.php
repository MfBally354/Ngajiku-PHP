<?php
// pages/parent/materi.php
require_once '../../includes/auth_check.php';
requireRole('parent');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Materi';

// Ambil kelas anak-anak yang terkait dengan parent ini
$kelasAnak = $db->prepare("SELECT DISTINCT k.id FROM kelas k JOIN santri_kelas sk ON k.id=sk.kelas_id JOIN parent_santri ps ON sk.santri_id=ps.santri_id WHERE ps.parent_id=?");
$kelasAnak->execute([$uid]);
$kelasIds = array_column($kelasAnak->fetchAll(), 'id');

$kategoriList = $db->query("SELECT * FROM kategori_materi ORDER BY nama")->fetchAll();

$filterKat = (int)($_GET['kategori'] ?? 0);
$search    = sanitize($_GET['q'] ?? '');

$where = "WHERE m.status='publik'";
$params = [];
if ($kelasIds) {
    $in = implode(',', $kelasIds);
    $where .= " AND (m.kelas_id IS NULL OR m.kelas_id IN ($in))";
}
if ($filterKat) { $where .= " AND m.kategori_id=?"; $params[] = $filterKat; }
if ($search)    { $where .= " AND m.judul LIKE ?";    $params[] = "%$search%"; }

$stmt = $db->prepare("SELECT m.*, km.nama as nama_kat, u.nama as nama_ustad FROM materi m LEFT JOIN kategori_materi km ON m.kategori_id=km.id LEFT JOIN users u ON m.ustad_id=u.id $where ORDER BY m.created_at DESC");
$stmt->execute($params);
$materiList = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<h4 class="page-title mb-1">Materi Pembelajaran</h4>
<p class="page-subtitle mb-4">Lihat materi yang diajarkan kepada anak Anda</p>

<div class="d-flex gap-2 flex-wrap mb-3">
    <a href="materi.php" class="kategori-pill <?= !$filterKat?'active':'' ?>"><i class="fas fa-th"></i>Semua</a>
    <?php foreach ($kategoriList as $k): ?>
    <a href="materi.php?kategori=<?= $k['id'] ?>" class="kategori-pill <?= $filterKat==$k['id']?'active':'' ?>">
        <i class="fas <?= $k['ikon'] ?>"></i><?= sanitize($k['nama']) ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="card mb-3"><div class="card-body p-3">
    <form method="GET" class="d-flex gap-2">
        <input type="hidden" name="kategori" value="<?= $filterKat ?>">
        <input type="text" name="q" class="form-control" placeholder="Cari materi..." value="<?= sanitize($search) ?>">
        <button type="submit" class="btn-primary-green"><i class="fas fa-search"></i></button>
        <a href="materi.php" class="btn-outline-green">Reset</a>
    </form>
</div></div>

<?php if (empty($materiList)): ?>
<div class="card"><div class="empty-state py-5"><i class="fas fa-book-open"></i><p>Tidak ada materi.</p></div></div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($materiList as $m): ?>
    <div class="col-sm-6 col-lg-4">
        <div class="materi-card">
            <div class="d-flex justify-content-between mb-2">
                <div class="materi-icon">
                    <?php $icons=['pdf'=>'fa-file-pdf','video'=>'fa-play-circle','gambar'=>'fa-image','link'=>'fa-link','teks'=>'fa-file-lines'];
                    echo '<i class="fas '.($icons[$m['tipe_file']]??'fa-file').'"></i>'; ?>
                </div>
                <?php if ($m['nama_kat']): ?>
                <span class="kategori-pill" style="font-size:11px;padding:2px 8px"><?= sanitize($m['nama_kat']) ?></span>
                <?php endif; ?>
            </div>
            <div class="materi-title"><?= sanitize($m['judul']) ?></div>
            <div class="materi-meta">
                <i class="fas fa-chalkboard-user me-1"></i><?= sanitize($m['nama_ustad']) ?>
                · <?= formatTanggal($m['created_at']) ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
