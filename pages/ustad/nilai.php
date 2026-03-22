<?php
// pages/ustad/nilai.php
require_once '../../includes/auth_check.php';
requireRole('ustad');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Input Nilai';

// Kelas milik ustad ini
$kelasList = $db->prepare("SELECT * FROM kelas WHERE ustad_id=? AND status='aktif' ORDER BY nama_kelas");
$kelasList->execute([$uid]);
$kelasList = $kelasList->fetchAll();

$kelasId = (int)($_GET['kelas_id'] ?? ($kelasList[0]['id'] ?? 0));

// ===== PROSES =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'input_nilai') {
        $santri_id = (int)$_POST['santri_id'];
        $kelas_id  = (int)$_POST['kelas_id'];
        $jenis     = $_POST['jenis'];
        $mapel     = sanitize($_POST['mata_pelajaran'] ?? '');
        $nilai_ang = (float)$_POST['nilai_angka'];
        $ket       = sanitize($_POST['keterangan'] ?? '');
        $tgl       = $_POST['tanggal'];

        $stmt = $db->prepare("INSERT INTO nilai (santri_id,kelas_id,ustad_id,jenis,mata_pelajaran,nilai_angka,keterangan,tanggal) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$santri_id,$kelas_id,$uid,$jenis,$mapel,$nilai_ang,$ket,$tgl]);
        flashMessage('success', 'Nilai berhasil disimpan.');
        redirect('nilai.php?kelas_id='.$kelas_id);
    }

    if ($act === 'hapus_nilai') {
        $db->prepare("DELETE FROM nilai WHERE id=? AND ustad_id=?")->execute([(int)$_POST['id'],$uid]);
        flashMessage('success', 'Nilai dihapus.');
        redirect('nilai.php?kelas_id='.$kelasId);
    }

    if ($act === 'beri_nilai_tugas') {
        $pt_id  = (int)$_POST['pt_id'];
        $nilai  = (int)$_POST['nilai'];
        $catatan= sanitize($_POST['catatan'] ?? '');
        $stmt = $db->prepare("UPDATE pengumpulan_tugas SET nilai=?,catatan_ustad=?,status='dinilai' WHERE id=?");
        $stmt->execute([$nilai,$catatan,$pt_id]);
        flashMessage('success', 'Nilai tugas berhasil disimpan.');
        redirect('nilai.php?kelas_id='.$kelasId.'&tab=tugas');
    }
}

// Santri di kelas ini
$santriList = [];
if ($kelasId) {
    $stmt = $db->prepare("SELECT u.id, u.nama FROM users u 
        JOIN santri_kelas sk ON u.id = sk.santri_id 
        WHERE sk.kelas_id=? ORDER BY u.nama");
    $stmt->execute([$kelasId]);
    $santriList = $stmt->fetchAll();
}

// Riwayat nilai
$nilaiList = [];
if ($kelasId) {
    $stmt = $db->prepare("SELECT n.*, u.nama as nama_santri FROM nilai n 
        JOIN users u ON n.santri_id = u.id 
        WHERE n.kelas_id=? AND n.ustad_id=? ORDER BY n.tanggal DESC, u.nama");
    $stmt->execute([$kelasId,$uid]);
    $nilaiList = $stmt->fetchAll();
}

// Tugas yang perlu dinilai
$tugasDinilai = [];
if ($kelasId) {
    $stmt = $db->prepare("SELECT pt.*, t.judul as judul_tugas, u.nama as nama_santri
        FROM pengumpulan_tugas pt
        JOIN tugas t ON pt.tugas_id = t.id
        JOIN users u ON pt.santri_id = u.id
        WHERE t.kelas_id=? AND t.ustad_id=? AND pt.status='dikumpulkan'
        ORDER BY pt.waktu_kumpul");
    $stmt->execute([$kelasId,$uid]);
    $tugasDinilai = $stmt->fetchAll();
}

$activeTab = $_GET['tab'] ?? 'manual';
require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Nilai Santri</h4>
        <p class="page-subtitle">Input nilai harian, ulangan, ujian, dan hafalan</p>
    </div>
</div>

<!-- Pilih Kelas -->
<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <label class="form-label mb-0 fw-bold">Kelas:</label>
            <select name="kelas_id" class="form-select" style="max-width:260px" onchange="this.form.submit()">
                <?php foreach ($kelasList as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $kelasId==$k['id']?'selected':'' ?>>
                    <?= sanitize($k['nama_kelas']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php if ($tugasDinilai): ?>
            <span class="badge bg-danger"><?= count($tugasDinilai) ?> tugas perlu dinilai</span>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab==='manual'?'active':'' ?>" href="?kelas_id=<?= $kelasId ?>&tab=manual">
            <i class="fas fa-pen me-1"></i>Input Nilai
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab==='riwayat'?'active':'' ?>" href="?kelas_id=<?= $kelasId ?>&tab=riwayat">
            <i class="fas fa-list me-1"></i>Riwayat Nilai
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab==='tugas'?'active':'' ?>" href="?kelas_id=<?= $kelasId ?>&tab=tugas">
            <i class="fas fa-tasks me-1"></i>Nilai Tugas
            <?php if ($tugasDinilai): ?>
            <span class="badge bg-danger ms-1"><?= count($tugasDinilai) ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab==='rekap'?'active':'' ?>" href="?kelas_id=<?= $kelasId ?>&tab=rekap">
            <i class="fas fa-chart-bar me-1"></i>Rekap Santri
        </a>
    </li>
</ul>

<!-- Tab: Input Nilai -->
<?php if ($activeTab === 'manual'): ?>
<div class="card">
    <div class="card-header"><i class="fas fa-pen me-2"></i>Input Nilai Baru</div>
    <div class="card-body">
        <?php if (empty($santriList)): ?>
        <div class="empty-state"><i class="fas fa-user-graduate"></i><p>Tidak ada santri di kelas ini.</p></div>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="act" value="input_nilai">
            <input type="hidden" name="kelas_id" value="<?= $kelasId ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Santri *</label>
                    <select name="santri_id" class="form-select" required>
                        <option value="">— Pilih Santri —</option>
                        <?php foreach ($santriList as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= sanitize($s['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis Penilaian *</label>
                    <select name="jenis" class="form-select" required>
                        <option value="harian">Harian</option>
                        <option value="ulangan">Ulangan</option>
                        <option value="ujian">Ujian</option>
                        <option value="hafalan">Hafalan</option>
                        <option value="praktik">Praktik</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mata Pelajaran</label>
                    <input type="text" name="mata_pelajaran" class="form-control"
                        placeholder="cth: Tajwid, Fiqih...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nilai (0–100) *</label>
                    <input type="number" name="nilai_angka" class="form-control"
                        min="0" max="100" step="0.5" required placeholder="85">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal *</label>
                    <input type="date" name="tanggal" class="form-control"
                        value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" class="form-control"
                        placeholder="Catatan tambahan (opsional)">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-primary-green">
                        <i class="fas fa-save"></i>Simpan Nilai
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Tab: Riwayat -->
<?php elseif ($activeTab === 'riwayat'): ?>
<div class="card">
    <div class="card-header"><i class="fas fa-list me-2"></i>Riwayat Nilai</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Santri</th><th>Jenis</th><th>Mapel</th><th>Nilai</th><th>Grade</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php if (empty($nilaiList)): ?>
                <tr><td colspan="7"><div class="empty-state py-3"><i class="fas fa-star"></i><p>Belum ada nilai</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($nilaiList as $n): ?>
                <tr>
                    <td><small><?= formatTanggal($n['tanggal']) ?></small></td>
                    <td><strong><?= sanitize($n['nama_santri']) ?></strong></td>
                    <td><span class="badge bg-info"><?= ucfirst($n['jenis']) ?></span></td>
                    <td><?= sanitize($n['mata_pelajaran'] ?: '-') ?></td>
                    <td>
                        <span class="fw-bold text-<?= nilaiToWarna($n['nilai_angka']) ?>">
                            <?= $n['nilai_angka'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= nilaiToWarna($n['nilai_angka']) ?>">
                            <?= nilaiToHuruf($n['nilai_angka']) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="act" value="hapus_nilai">
                            <input type="hidden" name="id" value="<?= $n['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                data-confirm="Hapus nilai ini?">
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

<!-- Tab: Nilai Tugas -->
<?php elseif ($activeTab === 'tugas'): ?>
<div class="card">
    <div class="card-header"><i class="fas fa-tasks me-2"></i>Tugas Dikumpulkan — Perlu Dinilai</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Santri</th><th>Tugas</th><th>Dikumpulkan</th><th>Nilai</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php if (empty($tugasDinilai)): ?>
                <tr><td colspan="5"><div class="empty-state py-3"><i class="fas fa-check-circle text-success"></i><p>Semua tugas sudah dinilai!</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($tugasDinilai as $t): ?>
                <tr>
                    <td><strong><?= sanitize($t['nama_santri']) ?></strong></td>
                    <td><?= sanitize($t['judul_tugas']) ?></td>
                    <td><small><?= formatTanggal($t['waktu_kumpul']) ?></small></td>
                    <td>
                        <form method="POST" class="d-flex gap-1 align-items-center" style="min-width:240px">
                            <input type="hidden" name="act" value="beri_nilai_tugas">
                            <input type="hidden" name="pt_id" value="<?= $t['id'] ?>">
                            <input type="number" name="nilai" class="form-control form-control-sm"
                                min="0" max="100" placeholder="0–100" style="width:80px" required>
                            <input type="text" name="catatan" class="form-control form-control-sm"
                                placeholder="Catatan" style="width:140px">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                    </td>
                    <td>
                        <?php if ($t['file_jawaban']): ?>
                        <a href="<?= UPLOAD_URL ?>tugas/<?= $t['file_jawaban'] ?>" target="_blank"
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tab: Rekap -->
<?php elseif ($activeTab === 'rekap'): ?>
<div class="card">
    <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Rekap Nilai per Santri</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Santri</th><th>Rata-rata</th><th>Grade</th><th>Jumlah Nilai</th><th>Terbaik</th><th>Terendah</th></tr></thead>
            <tbody>
                <?php if (empty($santriList)): ?>
                <tr><td colspan="6"><div class="empty-state py-3"><p>Tidak ada santri</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($santriList as $s):
                    $stmt2 = $db->prepare("SELECT AVG(nilai_angka) as avg, COUNT(*) as cnt, MAX(nilai_angka) as maks, MIN(nilai_angka) as min FROM nilai WHERE santri_id=? AND kelas_id=?");
                    $stmt2->execute([$s['id'],$kelasId]);
                    $r = $stmt2->fetch();
                    $avg = round($r['avg'] ?? 0, 1);
                ?>
                <tr>
                    <td><strong><?= sanitize($s['nama']) ?></strong></td>
                    <td>
                        <span class="nilai-big" style="font-size:20px;color:<?= $avg>=80?'#2E7D32':($avg>=60?'#F57F17':'#C62828') ?>">
                            <?= $avg ?: '-' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($r['cnt'] > 0): ?>
                        <span class="badge bg-<?= nilaiToWarna($avg) ?>"><?= nilaiToHuruf($avg) ?></span>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $r['cnt'] ?></td>
                    <td><?= $r['maks'] ?? '-' ?></td>
                    <td><?= $r['min'] ?? '-' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
