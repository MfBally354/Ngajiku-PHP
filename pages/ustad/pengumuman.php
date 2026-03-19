<?php
// pages/ustad/pengumuman.php  (juga berlaku untuk admin dengan path berbeda)
session_start();
require_once '../../includes/auth_check.php';
requireRole(['admin','ustad']);

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
        if (empty($judul) || empty($isi)) {
            flashMessage('error', 'Judul dan isi wajib diisi.');
        } else {
            $stmt = $db->prepare("INSERT INTO pengumuman (judul,isi,penulis_id,target_role,kelas_id,prioritas) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$judul,$isi,$uid,$target,$kelas_id,$prioritas]);
            flashMessage('success', 'Pengumuman berhasil dikirim.');
        }
        redirect("pengumuman.php");
    }

    if ($act === 'hapus') {
        $db->prepare("DELETE FROM pengumuman WHERE id=? AND penulis_id=?")->execute([(int)$_POST['id'],$uid]);
        flashMessage('success', 'Pengumuman dihapus.');
        redirect("pengumuman.php");
    }
}

$stmt = $db->prepare("SELECT p.*, u.nama as penulis FROM pengumuman p
    JOIN users u ON p.penulis_id=u.id
    ORDER BY p.created_at DESC");
$stmt->execute();
$list = $stmt->fetchAll();

// Kelas untuk filter kelas
$kelasList = $db->prepare("SELECT * FROM kelas WHERE ustad_id=? OR ? = 'admin' ORDER BY nama_kelas");
$kelasList->execute([$uid, $role]);
$kelasList = $kelasList->fetchAll();

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Pengumuman</h4>
        <p class="page-subtitle">Kirim informasi penting ke santri dan orang tua</p>
    </div>
    <button class="btn-primary-green" data-bs-toggle="modal" data-bs-target="#modalAnnounce">
        <i class="fas fa-bullhorn"></i>Buat Pengumuman
    </button>
</div>

<div class="row g-3">
    <?php if (empty($list)): ?>
    <div class="col-12"><div class="card"><div class="empty-state py-5">
        <i class="fas fa-bullhorn"></i><p>Belum ada pengumuman</p>
    </div></div></div>
    <?php else: ?>
    <?php foreach ($list as $p): ?>
    <div class="col-md-6">
        <div class="card h-100" style="border-left:4px solid <?= $p['prioritas']==='darurat'?'#c62828':($p['prioritas']==='penting'?'#f57f17':'#2E7D32') ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-<?= $p['prioritas']==='darurat'?'danger':($p['prioritas']==='penting'?'warning':'success') ?>">
                        <?= ucfirst($p['prioritas']) ?>
                    </span>
                    <small class="text-muted"><?= formatTanggal($p['created_at']) ?></small>
                </div>
                <h6 style="font-weight:700;margin-bottom:8px"><?= sanitize($p['judul']) ?></h6>
                <p style="font-size:13px;color:#555;margin-bottom:12px;line-height:1.6">
                    <?= nl2br(sanitize(substr($p['isi'],0,160))) ?><?= strlen($p['isi'])>160?'...':'' ?>
                </p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="fas fa-user me-1"></i><?= sanitize($p['penulis']) ?>
                        &nbsp;·&nbsp;
                        <i class="fas fa-users me-1"></i><?= sanitize($p['target_role']) ?>
                    </small>
                    <?php if ($p['penulis_id'] == $uid || $role === 'admin'): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="act" value="hapus">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                            data-confirm="Hapus pengumuman ini?">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal -->
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
                        <div class="col-12">
                            <label class="form-label">Judul Pengumuman *</label>
                            <input type="text" name="judul" class="form-control" required
                                placeholder="cth: Libur Hari Raya, Jadwal Ujian...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prioritas</label>
                            <select name="prioritas" class="form-select">
                                <option value="normal">Normal</option>
                                <option value="penting">Penting</option>
                                <option value="darurat">Darurat</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kirim ke Kelas <small class="text-muted">(opsional)</small></label>
                            <select name="kelas_id" class="form-select">
                                <option value="">Semua</option>
                                <?php foreach ($kelasList as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= sanitize($k['nama_kelas']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Target Penerima</label>
                            <div class="d-flex flex-column gap-1 pt-1">
                                <?php foreach (['santri','parent','ustad','admin'] as $r): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox"
                                        name="target_role[]" value="<?= $r ?>"
                                        id="role_<?= $r ?>" checked>
                                    <label class="form-check-label" for="role_<?= $r ?>"><?= ucfirst($r) ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Isi Pengumuman *</label>
                            <textarea name="isi" class="form-control" rows="5" required
                                placeholder="Tulis isi pengumuman di sini..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-green">
                        <i class="fas fa-paper-plane"></i>Kirim Pengumuman
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
