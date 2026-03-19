<?php
// pages/admin/materi.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('admin');

$db = getDB();
$pageTitle = 'Kelola Materi';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act']??'') === 'hapus') {
    $db->prepare("DELETE FROM materi WHERE id=?")->execute([(int)$_POST['id']]);
    flashMessage('success', 'Materi dihapus.');
    redirect('materi.php');
}

$kategoriList = $db->query("SELECT * FROM kategori_materi ORDER BY nama")->fetchAll();
$filterKat = (int)($_GET['kategori'] ?? 0);
$search    = sanitize($_GET['q'] ?? '');

$where = "WHERE 1=1";
$params = [];
if ($filterKat) { $where .= " AND m.kategori_id=?"; $params[] = $filterKat; }
if ($search)    { $where .= " AND m.judul LIKE ?";    $params[] = "%$search%"; }

$stmt = $db->prepare("SELECT m.*, km.nama as nama_kat, u.nama as nama_ustad, k.nama_kelas
    FROM materi m
    LEFT JOIN kategori_materi km ON m.kategori_id=km.id
    LEFT JOIN users u ON m.ustad_id=u.id
    LEFT JOIN kelas k ON m.kelas_id=k.id
    $where ORDER BY m.created_at DESC");
$stmt->execute($params);
$materiList = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Kelola Materi</h4>
        <p class="page-subtitle">Lihat dan hapus materi yang dibuat ustad</p>
    </div>
</div>

<div class="card mb-3"><div class="card-body p-3">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
        <input type="text" name="q" class="form-control" style="max-width:220px" placeholder="Cari judul..." value="<?= sanitize($search) ?>">
        <select name="kategori" class="form-select" style="max-width:200px">
            <option value="">Semua Kategori</option>
            <?php foreach ($kategoriList as $k): ?>
            <option value="<?= $k['id'] ?>" <?= $filterKat==$k['id']?'selected':'' ?>><?= sanitize($k['nama']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary-green"><i class="fas fa-filter"></i>Filter</button>
        <a href="materi.php" class="btn-outline-green">Reset</a>
        <span class="text-muted ms-2" style="font-size:13px"><?= count($materiList) ?> materi</span>
    </form>
</div></div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>#</th><th>Judul</th><th>Kategori</th><th>Ustad</th><th>Kelas</th><th>Tipe</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php if (empty($materiList)): ?>
                <tr><td colspan="9"><div class="empty-state py-4"><i class="fas fa-book-open"></i><p>Belum ada materi</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($materiList as $i => $m): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><strong><?= sanitize($m['judul']) ?></strong></td>
                    <td><?= $m['nama_kat'] ? sanitize($m['nama_kat']) : '<span class="text-muted">-</span>' ?></td>
                    <td><?= sanitize($m['nama_ustad']) ?></td>
                    <td><?= $m['nama_kelas'] ? sanitize($m['nama_kelas']) : '<span class="text-muted">Semua</span>' ?></td>
                    <td><span class="badge bg-secondary"><?= ucfirst($m['tipe_file']) ?></span></td>
                    <td><span class="badge bg-<?= $m['status']==='publik'?'success':'secondary' ?>"><?= ucfirst($m['status']) ?></span></td>
                    <td><small><?= formatTanggal($m['created_at']) ?></small></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="act" value="hapus">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Hapus materi ini?">
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

<?php require_once '../../includes/footer.php'; ?>
