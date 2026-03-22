<?php
// pages/ustad/absensi.php
require_once '../../includes/auth_check.php';
requireRole('ustad');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Absensi';

$kelasList = $db->prepare("SELECT * FROM kelas WHERE ustad_id=? AND status='aktif' ORDER BY nama_kelas");
$kelasList->execute([$uid]);
$kelasList = $kelasList->fetchAll();

$kelasId = (int)($_GET['kelas_id'] ?? ($kelasList[0]['id'] ?? 0));
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Santri di kelas
$santriList = [];
if ($kelasId) {
    $stmt = $db->prepare("SELECT u.id, u.nama FROM users u 
        JOIN santri_kelas sk ON u.id=sk.santri_id 
        WHERE sk.kelas_id=? ORDER BY u.nama");
    $stmt->execute([$kelasId]);
    $santriList = $stmt->fetchAll();
}

// Ambil absensi hari ini
$absensiHariIni = [];
if ($kelasId && $tanggal) {
    $stmt = $db->prepare("SELECT * FROM absensi WHERE kelas_id=? AND tanggal=?");
    $stmt->execute([$kelasId,$tanggal]);
    foreach ($stmt->fetchAll() as $a) {
        $absensiHariIni[$a['santri_id']] = $a;
    }
}

// SIMPAN ABSENSI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act']??'') === 'simpan_absensi') {
    $tgl = $_POST['tanggal'];
    $kid = (int)$_POST['kelas_id'];
    foreach ($_POST['status'] as $sid => $sts) {
        $ket = sanitize($_POST['keterangan'][$sid] ?? '');
        $stmt = $db->prepare("INSERT INTO absensi (santri_id,kelas_id,tanggal,status,keterangan,dicatat_oleh)
            VALUES (?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE status=VALUES(status), keterangan=VALUES(keterangan)");
        $stmt->execute([(int)$sid,$kid,$tgl,$sts,$ket,$uid]);
    }
    flashMessage('success', 'Absensi berhasil disimpan.');
    redirect("absensi.php?kelas_id=$kid&tanggal=$tgl");
}

// Rekap absensi bulan ini
$rekapBulan = [];
if ($kelasId) {
    $bulan = date('Y-m', strtotime($tanggal));
    $stmt = $db->prepare("SELECT u.nama, 
        SUM(a.status='hadir') as hadir,
        SUM(a.status='izin') as izin,
        SUM(a.status='sakit') as sakit,
        SUM(a.status='alpha') as alpha
        FROM absensi a
        JOIN users u ON a.santri_id=u.id
        WHERE a.kelas_id=? AND DATE_FORMAT(a.tanggal,'%Y-%m')=?
        GROUP BY u.id, u.nama ORDER BY u.nama");
    $stmt->execute([$kelasId,$bulan]);
    $rekapBulan = $stmt->fetchAll();
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Absensi Santri</h4>
        <p class="page-subtitle">Catat kehadiran santri setiap pertemuan</p>
    </div>
</div>

<!-- Pilih Kelas & Tanggal -->
<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <select name="kelas_id" class="form-select" style="max-width:220px" onchange="this.form.submit()">
                <?php foreach ($kelasList as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $kelasId==$k['id']?'selected':'' ?>>
                    <?= sanitize($k['nama_kelas']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="tanggal" class="form-control" style="max-width:180px"
                value="<?= $tanggal ?>" onchange="this.form.submit()">
            <span class="text-muted" style="font-size:13px">
                <i class="fas fa-users me-1"></i><?= count($santriList) ?> santri
            </span>
        </form>
    </div>
</div>

<div class="row g-4">
    <!-- Form Absensi -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-calendar-check me-2"></i>
                Absensi — <?= formatTanggal($tanggal, 'd F Y') ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($santriList)): ?>
                <div class="empty-state py-4"><i class="fas fa-users"></i><p>Tidak ada santri di kelas ini</p></div>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="act" value="simpan_absensi">
                    <input type="hidden" name="kelas_id" value="<?= $kelasId ?>">
                    <input type="hidden" name="tanggal" value="<?= $tanggal ?>">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>#</th><th>Nama Santri</th><th>Status</th><th>Keterangan</th></tr></thead>
                            <tbody>
                                <?php foreach ($santriList as $i => $s):
                                    $existing = $absensiHariIni[$s['id']] ?? null;
                                    $currentStatus = $existing['status'] ?? 'hadir';
                                    $currentKet    = $existing['keterangan'] ?? '';
                                ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><strong><?= sanitize($s['nama']) ?></strong></td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <?php foreach (['hadir'=>'success','izin'=>'info','sakit'=>'warning','alpha'=>'danger'] as $sts => $cls): ?>
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio"
                                                    name="status[<?= $s['id'] ?>]"
                                                    id="s<?= $s['id'] ?>_<?= $sts ?>"
                                                    value="<?= $sts ?>"
                                                    <?= $currentStatus===$sts?'checked':'' ?>>
                                                <label class="form-check-label" for="s<?= $s['id'] ?>_<?= $sts ?>">
                                                    <span class="badge bg-<?= $cls ?>"><?= ucfirst($sts) ?></span>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="keterangan[<?= $s['id'] ?>]"
                                            class="form-control form-control-sm"
                                            placeholder="Opsional"
                                            value="<?= sanitize($currentKet) ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">
                        <button type="submit" class="btn-primary-green">
                            <i class="fas fa-save"></i>Simpan Absensi
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Rekap Bulan Ini -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie me-2"></i>Rekap <?= date('F Y', strtotime($tanggal)) ?>
            </div>
            <div class="table-responsive">
                <table class="table mb-0" style="font-size:13px">
                    <thead><tr><th>Santri</th><th>H</th><th>I</th><th>S</th><th>A</th></tr></thead>
                    <tbody>
                        <?php if (empty($rekapBulan)): ?>
                        <tr><td colspan="5"><div class="empty-state py-3"><p>Belum ada data bulan ini</p></div></td></tr>
                        <?php else: ?>
                        <?php foreach ($rekapBulan as $r): ?>
                        <tr>
                            <td><?= sanitize($r['nama']) ?></td>
                            <td><span class="badge bg-success"><?= $r['hadir'] ?></span></td>
                            <td><span class="badge bg-info"><?= $r['izin'] ?></span></td>
                            <td><span class="badge bg-warning"><?= $r['sakit'] ?></span></td>
                            <td><span class="badge bg-danger"><?= $r['alpha'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
