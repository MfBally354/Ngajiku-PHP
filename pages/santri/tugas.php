<?php
// pages/santri/tugas.php
require_once '../../includes/auth_check.php';
requireRole('santri');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Tugas Saya';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act']??'') === 'kumpulkan') {
    $tugas_id = (int)$_POST['tugas_id'];
    $teks     = sanitize($_POST['teks_jawaban'] ?? '');
    $file     = null;
    if (!empty($_FILES['file_jawaban']['name'])) {
        $file = uploadFile($_FILES['file_jawaban'], 'tugas');
    }
    // Cek deadline
    $stmt = $db->prepare("SELECT * FROM tugas WHERE id=?");
    $stmt->execute([$tugas_id]);
    $tugasData = $stmt->fetch();
    $terlambat = $tugasData['deadline'] && strtotime($tugasData['deadline']) < time();

    $stmt = $db->prepare("INSERT INTO pengumpulan_tugas (tugas_id,santri_id,file_jawaban,teks_jawaban,status)
        VALUES (?,?,?,?,?)
        ON DUPLICATE KEY UPDATE file_jawaban=COALESCE(VALUES(file_jawaban),file_jawaban),
        teks_jawaban=VALUES(teks_jawaban), status=VALUES(status)");
    $stmt->execute([$tugas_id,$uid,$file,$teks,$terlambat?'terlambat':'dikumpulkan']);
    flashMessage('success', $terlambat ? 'Tugas dikumpulkan (terlambat).' : 'Tugas berhasil dikumpulkan!');
    redirect('tugas.php');
}

// Tugas aktif yang bisa dikerjakan
$stmt = $db->prepare("SELECT t.*, k.nama_kelas, u.nama as nama_ustad,
    pt.id as kumpul_id, pt.status as status_kumpul, pt.nilai, pt.waktu_kumpul
    FROM tugas t
    JOIN kelas k ON t.kelas_id=k.id
    JOIN santri_kelas sk ON k.id=sk.kelas_id
    JOIN users u ON k.ustad_id=u.id
    LEFT JOIN pengumpulan_tugas pt ON t.id=pt.tugas_id AND pt.santri_id=?
    WHERE sk.santri_id=? AND t.status='aktif'
    ORDER BY t.deadline ASC");
$stmt->execute([$uid,$uid]);
$tugasList = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<h4 class="page-title mb-1">Tugas Saya</h4>
<p class="page-subtitle mb-4">Kumpulkan tugasmu sebelum deadline</p>

<!-- Summary -->
<div class="row g-3 mb-4">
    <?php
    $pending   = array_filter($tugasList, fn($t) => !$t['kumpul_id']);
    $terkumpul = array_filter($tugasList, fn($t) => $t['kumpul_id'] && $t['status_kumpul']!=='dinilai');
    $dinilai   = array_filter($tugasList, fn($t) => $t['status_kumpul']==='dinilai');
    ?>
    <div class="col-4">
        <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-clock"></i></div>
            <div><div class="stat-value"><?= count($pending) ?></div><div class="stat-label">Belum Dikumpulkan</div></div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-paper-plane"></i></div>
            <div><div class="stat-value"><?= count($terkumpul) ?></div><div class="stat-label">Menunggu Nilai</div></div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div><div class="stat-value"><?= count($dinilai) ?></div><div class="stat-label">Sudah Dinilai</div></div>
        </div>
    </div>
</div>

<?php if (empty($tugasList)): ?>
<div class="card"><div class="empty-state py-5"><i class="fas fa-tasks"></i><p>Tidak ada tugas aktif saat ini.</p></div></div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($tugasList as $t):
        $sudahKumpul = !empty($t['kumpul_id']);
        $isLewat = $t['deadline'] && strtotime($t['deadline']) < time() && !$sudahKumpul;
    ?>
    <div class="col-md-6">
        <div class="card h-100" style="border-left:4px solid <?= $sudahKumpul?'#2E7D32':($isLewat?'#c62828':'#F57F17') ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <?php if ($sudahKumpul): ?>
                        <?php if ($t['status_kumpul'] === 'dinilai'): ?>
                        <span class="badge bg-success">Sudah Dinilai</span>
                        <?php else: ?>
                        <span class="badge bg-info">Terkumpul</span>
                        <?php endif; ?>
                    <?php elseif ($isLewat): ?>
                        <span class="badge bg-danger">Terlambat</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Belum Dikumpulkan</span>
                    <?php endif; ?>
                    <small class="text-muted"><?= sanitize($t['nama_kelas']) ?></small>
                </div>
                <h6 style="font-weight:700;margin-bottom:6px"><?= sanitize($t['judul']) ?></h6>
                <p style="font-size:13px;color:#666;margin-bottom:8px"><?= sanitize(substr($t['deskripsi'],0,100)) ?></p>

                <div style="font-size:12px;color:#888;margin-bottom:10px">
                    <i class="fas fa-chalkboard-user me-1"></i><?= sanitize($t['nama_ustad']) ?>
                    <?php if ($t['deadline']): ?>
                    &nbsp;·&nbsp;
                    <i class="fas fa-clock me-1"></i>Deadline: <?= formatTanggal($t['deadline'], 'd M Y H:i') ?>
                    <?php endif; ?>
                </div>

                <?php if ($t['file_soal']): ?>
                <a href="<?= UPLOAD_URL ?>tugas/<?= $t['file_soal'] ?>" class="btn btn-sm btn-outline-primary mb-2" download>
                    <i class="fas fa-download"></i> Unduh Soal
                </a>
                <?php endif; ?>

                <?php if ($sudahKumpul && $t['status_kumpul'] === 'dinilai'): ?>
                <div class="alert alert-success py-2 px-3" style="font-size:13px;border-radius:8px">
                    <strong>Nilai: <?= $t['nilai'] ?></strong>
                    (<?= nilaiToHuruf($t['nilai']) ?>)
                    — dikumpulkan <?= formatTanggal($t['waktu_kumpul']) ?>
                </div>

                <?php elseif (!$sudahKumpul || $t['status_kumpul'] === 'terlambat'): ?>
                <!-- Form kumpulkan -->
                <details>
                    <summary class="btn-primary-green d-inline-flex" style="cursor:pointer;list-style:none;padding:6px 14px">
                        <i class="fas fa-paper-plane me-1"></i>
                        <?= $sudahKumpul ? 'Kumpul Ulang' : 'Kumpulkan Tugas' ?>
                    </summary>
                    <form method="POST" enctype="multipart/form-data" class="mt-2">
                        <input type="hidden" name="act" value="kumpulkan">
                        <input type="hidden" name="tugas_id" value="<?= $t['id'] ?>">
                        <div class="mb-2">
                            <label class="form-label">Jawaban Teks</label>
                            <textarea name="teks_jawaban" class="form-control" rows="3"
                                placeholder="Tulis jawaban..."></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Atau Upload File</label>
                            <input type="file" name="file_jawaban" class="form-control"
                                accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>
                        <button type="submit" class="btn-primary-green">
                            <i class="fas fa-check"></i>Kirim
                        </button>
                    </form>
                </details>
                <?php else: ?>
                <div class="text-muted" style="font-size:13px">
                    <i class="fas fa-check-circle text-success me-1"></i>
                    Dikumpulkan <?= formatTanggal($t['waktu_kumpul']) ?> — menunggu penilaian
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
