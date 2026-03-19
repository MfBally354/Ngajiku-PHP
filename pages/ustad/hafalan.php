<?php
// pages/ustad/hafalan.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('ustad');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Rekap Hafalan';

$kelasList = $db->prepare("SELECT * FROM kelas WHERE ustad_id=? AND status='aktif' ORDER BY nama_kelas");
$kelasList->execute([$uid]);
$kelasList = $kelasList->fetchAll();

$kelasId = (int)($_GET['kelas_id'] ?? ($kelasList[0]['id'] ?? 0));

// PROSES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'tambah') {
        $santri_id    = (int)$_POST['santri_id'];
        $kelas_id     = (int)$_POST['kelas_id'];
        $jenis        = $_POST['jenis'];
        $nama_hafalan = sanitize($_POST['nama_hafalan'] ?? '');
        $nilai        = $_POST['nilai'] ?? 'B';
        $tanggal      = $_POST['tanggal'];
        $catatan      = sanitize($_POST['catatan'] ?? '');

        $stmt = $db->prepare("INSERT INTO hafalan (santri_id,kelas_id,ustad_id,jenis,nama_hafalan,nilai,tanggal,catatan) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$santri_id,$kelas_id,$uid,$jenis,$nama_hafalan,$nilai,$tanggal,$catatan]);
        flashMessage('success', 'Data hafalan berhasil disimpan.');
        redirect('hafalan.php?kelas_id='.$kelas_id);
    }
    if ($act === 'hapus') {
        $db->prepare("DELETE FROM hafalan WHERE id=? AND ustad_id=?")->execute([(int)$_POST['id'],$uid]);
        flashMessage('success', 'Data hafalan dihapus.');
        redirect('hafalan.php?kelas_id='.$kelasId);
    }
}

// Santri di kelas
$santriList = [];
if ($kelasId) {
    $stmt = $db->prepare("SELECT u.id, u.nama FROM users u JOIN santri_kelas sk ON u.id=sk.santri_id WHERE sk.kelas_id=? ORDER BY u.nama");
    $stmt->execute([$kelasId]);
    $santriList = $stmt->fetchAll();
}

// Daftar hafalan
$hafalanList = [];
if ($kelasId) {
    $stmt = $db->prepare("SELECT h.*, u.nama as nama_santri FROM hafalan h JOIN users u ON h.santri_id=u.id WHERE h.kelas_id=? AND h.ustad_id=? ORDER BY h.tanggal DESC, u.nama");
    $stmt->execute([$kelasId,$uid]);
    $hafalanList = $stmt->fetchAll();
}

// Rekap hafalan per santri
$rekapHafalan = [];
if ($kelasId) {
    foreach ($santriList as $s) {
        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(nilai='A') as a, SUM(nilai='B') as b, SUM(nilai='C') as c, SUM(nilai='D') as d FROM hafalan WHERE santri_id=? AND kelas_id=?");
        $stmt->execute([$s['id'],$kelasId]);
        $rekapHafalan[$s['id']] = $stmt->fetch();
    }
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Rekap Hafalan</h4>
        <p class="page-subtitle">Catat dan pantau hafalan Al-Quran, doa, dan hadits santri</p>
    </div>
</div>

<!-- Pilih Kelas -->
<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <label class="form-label mb-0 fw-bold">Kelas:</label>
            <select name="kelas_id" class="form-select" style="max-width:260px" onchange="this.form.submit()">
                <?php foreach ($kelasList as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $kelasId==$k['id']?'selected':'' ?>><?= sanitize($k['nama_kelas']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= ($_GET['tab']??'input')==='input'?'active':'' ?>" href="?kelas_id=<?= $kelasId ?>&tab=input">
            <i class="fas fa-pen me-1"></i>Input Hafalan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($_GET['tab']??'')==='rekap'?'active':'' ?>" href="?kelas_id=<?= $kelasId ?>&tab=rekap">
            <i class="fas fa-chart-bar me-1"></i>Rekap per Santri
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($_GET['tab']??'')==='riwayat'?'active':'' ?>" href="?kelas_id=<?= $kelasId ?>&tab=riwayat">
            <i class="fas fa-list me-1"></i>Riwayat
        </a>
    </li>
</ul>

<?php $activeTab = $_GET['tab'] ?? 'input'; ?>

<?php if ($activeTab === 'input'): ?>
<!-- Form Input Hafalan -->
<div class="card">
    <div class="card-header"><i class="fas fa-scroll me-2"></i>Catat Hafalan Baru</div>
    <div class="card-body">
        <?php if (empty($santriList)): ?>
        <div class="empty-state"><i class="fas fa-users"></i><p>Tidak ada santri di kelas ini.</p></div>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="act" value="tambah">
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
                    <label class="form-label">Jenis *</label>
                    <select name="jenis" class="form-select" required>
                        <option value="surah">Surah Al-Quran</option>
                        <option value="ayat">Ayat Pilihan</option>
                        <option value="doa">Doa Harian</option>
                        <option value="hadits">Hadits</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nama Hafalan *</label>
                    <input type="text" name="nama_hafalan" class="form-control" required
                        placeholder="cth: Al-Fatihah, Doa Sebelum Makan...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nilai</label>
                    <select name="nilai" class="form-select">
                        <option value="A">A — Lancar & Tajwid Benar</option>
                        <option value="B" selected>B — Lancar</option>
                        <option value="C">C — Cukup</option>
                        <option value="D">D — Perlu Ulang</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal *</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="catatan" class="form-control" placeholder="Catatan tambahan...">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-primary-green"><i class="fas fa-save"></i>Simpan Hafalan</button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($activeTab === 'rekap'): ?>
<!-- Rekap per Santri -->
<div class="card">
    <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Rekap Hafalan per Santri</div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Santri</th>
                    <th>Total</th>
                    <th><span class="badge bg-success">A</span></th>
                    <th><span class="badge bg-info">B</span></th>
                    <th><span class="badge bg-warning">C</span></th>
                    <th><span class="badge bg-danger">D</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($santriList)): ?>
                <tr><td colspan="6"><div class="empty-state py-3"><p>Tidak ada santri</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($santriList as $s):
                    $r = $rekapHafalan[$s['id']] ?? ['total'=>0,'a'=>0,'b'=>0,'c'=>0,'d'=>0];
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle" style="width:30px;height:30px;font-size:11px;flex-shrink:0"><?= avatarInitial($s['nama']) ?></div>
                            <strong><?= sanitize($s['nama']) ?></strong>
                        </div>
                    </td>
                    <td><span class="badge bg-primary"><?= $r['total'] ?></span></td>
                    <td><span class="badge bg-success"><?= $r['a'] ?></span></td>
                    <td><span class="badge bg-info"><?= $r['b'] ?></span></td>
                    <td><span class="badge bg-warning"><?= $r['c'] ?></span></td>
                    <td><span class="badge bg-danger"><?= $r['d'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($activeTab === 'riwayat'): ?>
<!-- Riwayat -->
<div class="card">
    <div class="card-header"><i class="fas fa-list me-2"></i>Riwayat Hafalan</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Santri</th><th>Jenis</th><th>Hafalan</th><th>Nilai</th><th>Catatan</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php if (empty($hafalanList)): ?>
                <tr><td colspan="7"><div class="empty-state py-3"><p>Belum ada data hafalan</p></div></td></tr>
                <?php else: ?>
                <?php
                $nilaiMap = [
                    'A' => ['success','A — Lancar & Tajwid'],
                    'B' => ['info','B — Lancar'],
                    'C' => ['warning','C — Cukup'],
                    'D' => ['danger','D — Perlu Ulang'],
                ];
                foreach ($hafalanList as $h): ?>
                <tr>
                    <td><small><?= formatTanggal($h['tanggal']) ?></small></td>
                    <td><strong><?= sanitize($h['nama_santri']) ?></strong></td>
                    <td><span class="badge bg-secondary"><?= ucfirst($h['jenis']) ?></span></td>
                    <td><?= sanitize($h['nama_hafalan']) ?></td>
                    <td>
                        <?php $nm = $nilaiMap[$h['nilai']] ?? ['secondary',$h['nilai']]; ?>
                        <span class="badge bg-<?= $nm[0] ?>"><?= $nm[1] ?></span>
                    </td>
                    <td><small class="text-muted"><?= sanitize($h['catatan'] ?: '-') ?></small></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="act" value="hapus">
                            <input type="hidden" name="id" value="<?= $h['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Hapus data hafalan ini?">
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
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
