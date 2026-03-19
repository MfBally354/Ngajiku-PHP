<?php
// pages/santri/materi.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('santri');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Materi Pembelajaran';

$kategoriList = $db->query("SELECT * FROM kategori_materi ORDER BY nama")->fetchAll();

// Jika lihat detail
$detailId = (int)($_GET['id'] ?? 0);
if ($detailId) {
    $stmt = $db->prepare("SELECT m.*, km.nama as nama_kat, u.nama as nama_ustad
        FROM materi m
        LEFT JOIN kategori_materi km ON m.kategori_id=km.id
        LEFT JOIN users u ON m.ustad_id=u.id
        WHERE m.id=? AND m.status='publik'");
    $stmt->execute([$detailId]);
    $detail = $stmt->fetch();
}

// Filter
$filterKat = (int)($_GET['kategori'] ?? 0);
$search    = sanitize($_GET['q'] ?? '');
$where = "WHERE m.status='publik' AND (m.kelas_id IS NULL OR m.kelas_id IN (SELECT kelas_id FROM santri_kelas WHERE santri_id=?))";
$params = [$uid];
if ($filterKat) { $where .= " AND m.kategori_id=?"; $params[] = $filterKat; }
if ($search)    { $where .= " AND m.judul LIKE ?";    $params[] = "%$search%"; }

$stmt = $db->prepare("SELECT m.*, km.nama as nama_kat, u.nama as nama_ustad
    FROM materi m
    LEFT JOIN kategori_materi km ON m.kategori_id=km.id
    LEFT JOIN users u ON m.ustad_id=u.id
    $where ORDER BY m.created_at DESC");
$stmt->execute($params);
$materiList = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<?php if (!empty($detail)): ?>
<!-- ===== DETAIL MATERI ===== -->
<div class="mb-3">
    <a href="materi.php" class="btn-outline-green" style="padding:6px 14px">
        <i class="fas fa-arrow-left"></i>Kembali
    </a>
</div>
<div class="card">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
            <?php if ($detail['nama_kat']): ?>
            <span class="kategori-pill"><?= sanitize($detail['nama_kat']) ?></span>
            <?php endif; ?>
            <small class="text-muted"><i class="fas fa-user me-1"></i><?= sanitize($detail['nama_ustad']) ?></small>
            <small class="text-muted"><i class="fas fa-calendar me-1"></i><?= formatTanggal($detail['created_at']) ?></small>
        </div>
        <h3 style="font-weight:800;color:#222;margin-bottom:20px"><?= sanitize($detail['judul']) ?></h3>

        <?php if ($detail['tipe_file'] === 'teks'): ?>
        <div class="materi-konten" style="line-height:1.9;font-size:15px">
            <?= nl2br(sanitize($detail['konten'])) ?>
        </div>

        <?php elseif ($detail['tipe_file'] === 'pdf' && $detail['file_path']): ?>
        <div class="ratio ratio-16x9" style="min-height:500px">
            <embed src="<?= UPLOAD_URL ?>materi/<?= $detail['file_path'] ?>"
                type="application/pdf" style="width:100%;height:100%;border-radius:8px">
        </div>
        <a href="<?= UPLOAD_URL ?>materi/<?= $detail['file_path'] ?>" download
           class="btn-primary-green mt-3 d-inline-flex">
            <i class="fas fa-download"></i>Unduh PDF
        </a>

        <?php elseif ($detail['tipe_file'] === 'video' && $detail['file_path']): ?>
        <video controls class="w-100" style="border-radius:8px">
            <source src="<?= UPLOAD_URL ?>materi/<?= $detail['file_path'] ?>">
        </video>

        <?php elseif ($detail['tipe_file'] === 'gambar' && $detail['file_path']): ?>
        <img src="<?= UPLOAD_URL ?>materi/<?= $detail['file_path'] ?>"
             class="img-fluid" style="border-radius:8px;max-height:600px">

        <?php elseif ($detail['tipe_file'] === 'link' && $detail['link_eksternal']): ?>
        <?php
        $link = $detail['link_eksternal'];
        $isYoutube = preg_match('/youtube\.com|youtu\.be/', $link);
        if ($isYoutube):
            preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $link, $matches);
            $vid = $matches[1] ?? '';
        ?>
        <div class="ratio ratio-16x9">
            <iframe src="https://www.youtube.com/embed/<?= $vid ?>"
                allowfullscreen style="border-radius:8px"></iframe>
        </div>
        <?php else: ?>
        <a href="<?= sanitize($link) ?>" target="_blank" class="btn-primary-green d-inline-flex">
            <i class="fas fa-external-link-alt"></i>Buka Link Materi
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- ===== LIST MATERI ===== -->
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Materi Pembelajaran</h4>
        <p class="page-subtitle">Pelajari semua materi dari ustadzmu</p>
    </div>
</div>

<!-- Kategori Pills -->
<div class="d-flex gap-2 flex-wrap mb-3">
    <a href="materi.php" class="kategori-pill <?= !$filterKat?'active':'' ?>">
        <i class="fas fa-th"></i>Semua
    </a>
    <?php foreach ($kategoriList as $k): ?>
    <a href="materi.php?kategori=<?= $k['id'] ?>" class="kategori-pill <?= $filterKat==$k['id']?'active':'' ?>">
        <i class="fas <?= $k['ikon'] ?>"></i><?= sanitize($k['nama']) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Search -->
<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="kategori" value="<?= $filterKat ?>">
            <input type="text" name="q" class="form-control" placeholder="Cari materi..."
                value="<?= sanitize($search) ?>">
            <button type="submit" class="btn-primary-green"><i class="fas fa-search"></i></button>
            <a href="materi.php" class="btn-outline-green">Reset</a>
        </form>
    </div>
</div>

<!-- Grid Materi -->
<?php if (empty($materiList)): ?>
<div class="card"><div class="empty-state py-5">
    <i class="fas fa-book-open"></i><p>Tidak ada materi ditemukan.</p>
</div></div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($materiList as $m): ?>
    <div class="col-sm-6 col-lg-4">
        <a href="materi.php?id=<?= $m['id'] ?>" class="text-decoration-none">
            <div class="materi-card">
                <div class="d-flex justify-content-between mb-2">
                    <div class="materi-icon">
                        <?php $icons=['pdf'=>'fa-file-pdf','video'=>'fa-play-circle',
                                      'gambar'=>'fa-image','link'=>'fa-link','teks'=>'fa-file-lines'];
                        echo '<i class="fas '.($icons[$m['tipe_file']]??'fa-file').'"></i>'; ?>
                    </div>
                    <?php if ($m['nama_kat']): ?>
                    <span class="kategori-pill" style="font-size:11px;padding:2px 8px">
                        <?= sanitize($m['nama_kat']) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="materi-title"><?= sanitize($m['judul']) ?></div>
                <div class="materi-meta">
                    <i class="fas fa-user me-1"></i><?= sanitize($m['nama_ustad']) ?>
                    · <?= formatTanggal($m['created_at']) ?>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
