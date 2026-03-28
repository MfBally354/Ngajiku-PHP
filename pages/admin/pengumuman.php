<?php
// pages/admin/pengumuman.php - mengacu ke ustad/pengumuman.php tapi dengan path relatif yang benar
require_once '../../includes/auth_check.php';
requireRole(['admin','ustad']);

// Sama persis dengan ustad/pengumuman.php, hanya include path yang berbeda
$db   = getDB();
$uid  = $user['id'];
$role = $user['role'];
$pageTitle = 'Pengumuman';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'tambah') {
        $judul    = sanitize($_POST['judul'] ?? '');
        $isi      = sanitize($_POST['isi'] ?? '');
        $target   = implode(',', (array)($_POST['target_role'] ?? ['santri']));
        $prioritas= $_POST['prioritas'] ?? 'normal';
        $kelas_id = (int)($_POST['kelas_id'] ?? 0) ?: null;
        if (!empty($judul) && !empty($isi)) {
            $stmt = $db->prepare("INSERT INTO pengumuman (judul,isi,penulis_id,target_role,kelas_id,prioritas) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$judul,$isi,$uid,$target,$kelas_id,$prioritas]);
            flashMessage('success', 'Pengumuman berhasil dikirim.');
        }
        redirect("pengumuman.php");
    }
    if ($act === 'hapus') {
        $db->prepare("DELETE FROM pengumuman WHERE id=?")->execute([(int)$_POST['id']]);
        flashMessage('success', 'Pengumuman dihapus.');
        redirect("pengumuman.php");
    }
}

$stmt = $db->prepare("SELECT p.*, u.nama as penulis FROM pengumuman p
    JOIN users u ON p.penulis_id=u.id ORDER BY p.created_at DESC");
$stmt->execute();
$list = $stmt->fetchAll();

$kelasList = $db->query("SELECT * FROM kelas WHERE status='aktif' ORDER BY nama_kelas")->fetchAll();

require_once '../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Pengumuman</h4>
        <p class="page-subtitle">Kelola semua pengumuman platform</p>
    </div>
    <button class="btn-primary-green" data-bs-toggle="modal" data-bs-target="#modalAnnounce">
        <i class="fas fa-bullhorn"></i>Buat Pengumuman
    </button>
</div>
<div class="row g-3">
    <?php if (empty($list)): ?>
    <div class="col-12"><div class="card"><div class="empty-state py-5"><i class="fas fa-bullhorn"></i><p>Belum ada pengumuman</p></div></div></div>
    <?php else: ?>
    <?php foreach ($list as $p): ?>
    <div class="col-md-6">
        <div class="card h-100" style="border-left:4px solid <?= $p['prioritas']==='darurat'?'#c62828':($p['prioritas']==='penting'?'#f57f17':'#2E7D32') ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-<?= $p['prioritas']==='darurat'?'danger':($p['prioritas']==='penting'?'warning':'success') ?>"><?= ucfirst($p['prioritas']) ?></span>
                    <small class="text-muted"><?= formatTanggal($p['created_at']) ?></small>
                </div>
                <h6 style="font-weight:700;margin-bottom:8px"><?= sanitize($p['judul']) ?></h6>
                <p style="font-size:13px;color:#555;margin-bottom:12px;line-height:1.6"><?= nl2br(sanitize($p['isi'])) ?></p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted"><i class="fas fa-user me-1"></i><?= sanitize($p['penulis']) ?></small>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="act" value="hapus">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Hapus pengumuman ini?"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<div class="modal fade" id="modalAnnounce" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">Buat Pengumuman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="act" value="tambah">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Judul *</label><input type="text" name="judul" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Prioritas</label>
                            <select name="prioritas" class="form-select">
                                <option value="normal">Normal</option><option value="penting">Penting</option><option value="darurat">Darurat</option>
                            </select></div>
                        <div class="col-md-8"><label class="form-label">Target</label>
                            <div class="d-flex gap-3 pt-1">
                                <?php foreach (['santri','parent','ustad','admin'] as $r): ?>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="target_role[]" value="<?= $r ?>" id="r_<?= $r ?>" checked><label class="form-check-label" for="r_<?= $r ?>"><?= ucfirst($r) ?></label></div>
                                <?php endforeach; ?>
                            </div></div>
                        <div class="col-12"><label class="form-label">Isi *</label><textarea name="isi" class="form-control" rows="5" required></textarea></div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-green"><i class="fas fa-paper-plane"></i>Kirim</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
