<?php
// pages/ustad/tugas.php
require_once '../../includes/auth_check.php';
requireRole('ustad');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Kelola Tugas';

$kelasList = $db->prepare("SELECT * FROM kelas WHERE ustad_id=? AND status='aktif' ORDER BY nama_kelas");
$kelasList->execute([$uid]);
$kelasList = $kelasList->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'tambah') {
        $judul    = sanitize($_POST['judul'] ?? '');
        $deskripsi= sanitize($_POST['deskripsi'] ?? '');
        $kelas_id = (int)$_POST['kelas_id'];
        $deadline = $_POST['deadline'] ?: null;
        $max      = (int)($_POST['max_nilai'] ?? 100);
        $file = null;
        if (!empty($_FILES['file_soal']['name'])) {
            $file = uploadFile($_FILES['file_soal'], 'tugas');
        }
        $stmt = $db->prepare("INSERT INTO tugas (judul,deskripsi,kelas_id,ustad_id,deadline,file_soal,max_nilai) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$judul,$deskripsi,$kelas_id,$uid,$deadline,$file,$max]);
        flashMessage('success', 'Tugas berhasil ditambahkan.');
        redirect('tugas.php');
    }
    if ($act === 'hapus') {
        $db->prepare("DELETE FROM tugas WHERE id=? AND ustad_id=?")->execute([(int)$_POST['id'],$uid]);
        flashMessage('success', 'Tugas dihapus.');
        redirect('tugas.php');
    }
    if ($act === 'ubah_status') {
        $db->prepare("UPDATE tugas SET status=? WHERE id=? AND ustad_id=?")->execute([$_POST['status'],(int)$_POST['id'],$uid]);
        redirect('tugas.php');
    }
}

$stmt = $db->prepare("SELECT t.*, k.nama_kelas,
    COUNT(pt.id) as jml_kumpul
    FROM tugas t
    JOIN kelas k ON t.kelas_id=k.id
    LEFT JOIN pengumpulan_tugas pt ON t.id=pt.tugas_id
    WHERE t.ustad_id=?
    GROUP BY t.id ORDER BY t.created_at DESC");
$stmt->execute([$uid]);
$tugasList = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Kelola Tugas</h4>
        <p class="page-subtitle">Buat dan kelola tugas untuk santri</p>
    </div>
    <button class="btn-primary-green" data-bs-toggle="modal" data-bs-target="#modalTugas">
        <i class="fas fa-plus"></i>Tambah Tugas
    </button>
</div>

<div class="row g-3">
    <?php if (empty($tugasList)): ?>
    <div class="col-12"><div class="card"><div class="empty-state py-5">
        <i class="fas fa-tasks"></i><p>Belum ada tugas. Klik <strong>Tambah Tugas</strong>.</p>
    </div></div></div>
    <?php else: ?>
    <?php foreach ($tugasList as $t): ?>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-<?= $t['status']==='aktif'?'success':($t['status']==='selesai'?'secondary':'warning') ?>">
                        <?= ucfirst($t['status']) ?>
                    </span>
                    <small class="text-muted"><?= formatTanggal($t['created_at']) ?></small>
                </div>
                <h6 style="font-weight:700;margin-bottom:6px"><?= sanitize($t['judul']) ?></h6>
                <p style="font-size:13px;color:#666;margin-bottom:10px"><?= sanitize(substr($t['deskripsi'],0,120)) ?></p>
                <div class="d-flex gap-3 mb-3" style="font-size:13px;color:#888">
                    <span><i class="fas fa-school me-1"></i><?= sanitize($t['nama_kelas']) ?></span>
                    <?php if ($t['deadline']): ?>
                    <span><i class="fas fa-clock me-1"></i><?= formatTanggal($t['deadline']) ?></span>
                    <?php endif; ?>
                    <span><i class="fas fa-clipboard-check me-1"></i><?= $t['jml_kumpul'] ?> dikumpulkan</span>
                </div>
                <div class="d-flex gap-2">
                    <a href="nilai.php?kelas_id=<?= $t['kelas_id'] ?>&tab=tugas" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-star"></i> Nilai
                    </a>
                    <?php if ($t['file_soal']): ?>
                    <a href="<?= UPLOAD_URL ?>tugas/<?= $t['file_soal'] ?>" class="btn btn-sm btn-outline-primary" download>
                        <i class="fas fa-download"></i>
                    </a>
                    <?php endif; ?>
                    <form method="POST" class="ms-auto">
                        <input type="hidden" name="act" value="hapus">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                            data-confirm="Hapus tugas ini?">
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

<!-- Modal Tambah Tugas -->
<div class="modal fade" id="modalTugas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">Tambah Tugas Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="act" value="tambah">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Judul Tugas *</label>
                            <input type="text" name="judul" class="form-control" required placeholder="cth: Hafalan Surat Al-Fatiha">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kelas *</label>
                            <select name="kelas_id" class="form-select" required>
                                <option value="">— Pilih Kelas —</option>
                                <?php foreach ($kelasList as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= sanitize($k['nama_kelas']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Deadline</label>
                            <input type="datetime-local" name="deadline" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Nilai Maks</label>
                            <input type="number" name="max_nilai" class="form-control" value="100" min="1" max="100">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Petunjuk pengerjaan tugas..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">File Soal <small class="text-muted">(opsional, maks 10MB)</small></label>
                            <input type="file" name="file_soal" class="form-control" accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-green"><i class="fas fa-save"></i>Simpan Tugas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
