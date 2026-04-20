<?php
// pages/admin/keuangan.php
require_once '../../includes/auth_check.php';
requireRole('admin');

$db  = getDB();
$uid = $user['id'];
$pageTitle = 'Manajemen Keuangan';

$bulan = $_GET['bulan'] ?? date('Y-m');
$tab   = $_GET['tab'] ?? 'dashboard';

// ===== PROSES POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'tambah_transaksi') {
        $kategori_id = (int)$_POST['kategori_id'];
        $keterangan  = sanitize($_POST['keterangan'] ?? '');
        $jumlah      = (float)str_replace(['.', ','], ['', '.'], $_POST['jumlah'] ?? 0);
        $tanggal     = $_POST['tanggal'];
        $catatan     = sanitize($_POST['catatan'] ?? '');
        $santri_id   = (int)($_POST['santri_id'] ?? 0) ?: null;
        $ustad_id    = (int)($_POST['ustad_id'] ?? 0) ?: null;
        $bulan_trx   = substr($tanggal, 0, 7);

        $stmt = $db->prepare("SELECT tipe FROM keuangan_kategori WHERE id=?");
        $stmt->execute([$kategori_id]);
        $kat = $stmt->fetch();
        $tipe = $kat['tipe'] ?? 'masuk';

        $stmt = $db->prepare("INSERT INTO keuangan_transaksi (kategori_id,keterangan,jumlah,tipe,tanggal,bulan,santri_id,ustad_id,dicatat_oleh,catatan) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$kategori_id,$keterangan,$jumlah,$tipe,$tanggal,$bulan_trx,$santri_id,$ustad_id,$uid,$catatan]);
        flashMessage('success', 'Transaksi berhasil dicatat.');
        redirect("keuangan.php?tab=transaksi&bulan=$bulan_trx");
    }

    if ($act === 'hapus_transaksi') {
        $db->prepare("DELETE FROM keuangan_transaksi WHERE id=?")->execute([(int)$_POST['id']]);
        flashMessage('success', 'Transaksi dihapus.');
        redirect("keuangan.php?tab=transaksi&bulan=$bulan");
    }

    if ($act === 'simpan_gaji') {
        $ustad_id   = (int)$_POST['ustad_id'];
        $gaji_pokok = (float)str_replace(['.', ','], ['', '.'], $_POST['gaji_pokok'] ?? 0);
        $stmt = $db->prepare("INSERT INTO gaji_ustad (ustad_id, gaji_pokok) VALUES (?,?) ON DUPLICATE KEY UPDATE gaji_pokok=VALUES(gaji_pokok)");
        $stmt->execute([$ustad_id, $gaji_pokok]);
        flashMessage('success', 'Gaji ustad berhasil disimpan.');
        redirect("keuangan.php?tab=gaji");
    }

    if ($act === 'bayar_gaji') {
        $ustad_id  = (int)$_POST['ustad_id'];
        $jumlah    = (float)$_POST['jumlah'];
        $bulan_gaji= $_POST['bulan_gaji'];
        $nama_ustad= sanitize($_POST['nama_ustad'] ?? '');

        $stmt = $db->prepare("SELECT id FROM keuangan_kategori WHERE nama='Gaji Ustad' LIMIT 1");
        $stmt->execute();
        $kat = $stmt->fetch();
        $kat_id = $kat['id'] ?? 3;

        $tanggal = $bulan_gaji . '-01';
        $stmt = $db->prepare("INSERT INTO keuangan_transaksi (kategori_id,keterangan,jumlah,tipe,tanggal,bulan,ustad_id,dicatat_oleh) VALUES (?,?,?,'keluar',?,?,?,?)");
        $stmt->execute([$kat_id, "Gaji Ustad: $nama_ustad ($bulan_gaji)", $jumlah, $tanggal, $bulan_gaji, $ustad_id, $uid]);
        flashMessage('success', "Gaji $nama_ustad berhasil dibayar.");
        redirect("keuangan.php?tab=gaji");
    }
}

// ===== DATA =====
$kategoriList = $db->query("SELECT * FROM keuangan_kategori ORDER BY tipe DESC, nama")->fetchAll();
$ustadList    = $db->query("SELECT id, nama FROM users WHERE role='ustad' AND status='aktif' ORDER BY nama")->fetchAll();
$santriList   = $db->query("SELECT id, nama FROM users WHERE role='santri' AND status='aktif' ORDER BY nama")->fetchAll();

// Ringkasan bulan ini
$totalMasuk  = $db->prepare("SELECT COALESCE(SUM(jumlah),0) FROM keuangan_transaksi WHERE tipe='masuk' AND bulan=?");
$totalMasuk->execute([$bulan]); $totalMasuk = $totalMasuk->fetchColumn();

$totalKeluar = $db->prepare("SELECT COALESCE(SUM(jumlah),0) FROM keuangan_transaksi WHERE tipe='keluar' AND bulan=?");
$totalKeluar->execute([$bulan]); $totalKeluar = $totalKeluar->fetchColumn();

$saldo = $totalMasuk - $totalKeluar;

// Ringkasan all time
$totalMasukAll  = $db->query("SELECT COALESCE(SUM(jumlah),0) FROM keuangan_transaksi WHERE tipe='masuk'")->fetchColumn();
$totalKeluarAll = $db->query("SELECT COALESCE(SUM(jumlah),0) FROM keuangan_transaksi WHERE tipe='keluar'")->fetchColumn();
$saldoAll = $totalMasukAll - $totalKeluarAll;

// Transaksi bulan ini
$transaksiList = $db->prepare("SELECT t.*, k.nama as nama_kat, k.ikon, k.warna,
    u.nama as nama_ustad, s.nama as nama_santri, p.nama as nama_pencatat
    FROM keuangan_transaksi t
    JOIN keuangan_kategori k ON t.kategori_id=k.id
    LEFT JOIN users u ON t.ustad_id=u.id
    LEFT JOIN users s ON t.santri_id=s.id
    LEFT JOIN users p ON t.dicatat_oleh=p.id
    WHERE t.bulan=? ORDER BY t.tanggal DESC, t.created_at DESC");
$transaksiList->execute([$bulan]);
$transaksiList = $transaksiList->fetchAll();

// Per kategori bulan ini
$perKategori = $db->prepare("SELECT k.nama, k.tipe, k.ikon, k.warna, COALESCE(SUM(t.jumlah),0) as total
    FROM keuangan_kategori k
    LEFT JOIN keuangan_transaksi t ON k.id=t.kategori_id AND t.bulan=?
    GROUP BY k.id ORDER BY k.tipe DESC, total DESC");
$perKategori->execute([$bulan]);
$perKategori = $perKategori->fetchAll();

// Data gaji ustad
$gajiUstad = $db->query("SELECT g.*, u.nama FROM gaji_ustad g JOIN users u ON g.ustad_id=u.id ORDER BY u.nama")->fetchAll();

// Cek gaji sudah dibayar bulan ini
$gajiBulanIni = [];
$stmt = $db->prepare("SELECT ustad_id FROM keuangan_transaksi WHERE tipe='keluar' AND bulan=? AND ustad_id IS NOT NULL");
$stmt->execute([$bulan]);
foreach ($stmt->fetchAll() as $g) $gajiBulanIni[] = $g['ustad_id'];

// Grafik 6 bulan terakhir
$grafik = [];
for ($i = 5; $i >= 0; $i--) {
    $bln = date('Y-m', strtotime("-$i months"));
    $m = $db->prepare("SELECT COALESCE(SUM(jumlah),0) FROM keuangan_transaksi WHERE tipe='masuk' AND bulan=?");
    $m->execute([$bln]); $masukBln = $m->fetchColumn();
    $k = $db->prepare("SELECT COALESCE(SUM(jumlah),0) FROM keuangan_transaksi WHERE tipe='keluar' AND bulan=?");
    $k->execute([$bln]); $keluarBln = $k->fetchColumn();
    $grafik[] = ['bulan' => $bln, 'masuk' => $masukBln, 'keluar' => $keluarBln];
}

require_once '../../includes/header.php';

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Manajemen Keuangan</h4>
        <p class="page-subtitle">Kelola keuangan pesantren — infaq, SPP, gaji, dan operasional</p>
    </div>
    <button class="btn-primary-green" data-bs-toggle="modal" data-bs-target="#modalTambahTransaksi">
        <i class="fas fa-plus"></i>Catat Transaksi
    </button>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link <?= $tab==='dashboard'?'active':'' ?>" href="?tab=dashboard&bulan=<?= $bulan ?>"><i class="fas fa-gauge me-1"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab==='transaksi'?'active':'' ?>" href="?tab=transaksi&bulan=<?= $bulan ?>"><i class="fas fa-list me-1"></i>Transaksi</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab==='gaji'?'active':'' ?>" href="?tab=gaji&bulan=<?= $bulan ?>"><i class="fas fa-user-tie me-1"></i>Gaji Ustad</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab==='laporan'?'active':'' ?>" href="?tab=laporan&bulan=<?= $bulan ?>"><i class="fas fa-chart-bar me-1"></i>Laporan</a></li>
</ul>

<!-- Filter Bulan -->
<div class="card mb-4"><div class="card-body p-3">
    <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
        <input type="hidden" name="tab" value="<?= $tab ?>">
        <label class="form-label mb-0 fw-bold">Periode:</label>
        <input type="month" name="bulan" class="form-control" style="max-width:180px" value="<?= $bulan ?>" onchange="this.form.submit()">
        <span class="text-muted" style="font-size:13px"><?= date('F Y', strtotime($bulan.'-01')) ?></span>
    </form>
</div></div>

<?php if ($tab === 'dashboard'): ?>
<!-- ===== DASHBOARD ===== -->

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-arrow-down"></i></div>
            <div>
                <div class="stat-value" style="font-size:18px"><?= rupiah($totalMasuk) ?></div>
                <div class="stat-label">Pemasukan Bulan Ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-arrow-up"></i></div>
            <div>
                <div class="stat-value" style="font-size:18px"><?= rupiah($totalKeluar) ?></div>
                <div class="stat-label">Pengeluaran Bulan Ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon <?= $saldo>=0?'teal':'orange' ?>"><i class="fas fa-wallet"></i></div>
            <div>
                <div class="stat-value" style="font-size:18px;color:<?= $saldo>=0?'#2E7D32':'#C62828' ?>"><?= rupiah($saldo) ?></div>
                <div class="stat-label">Saldo Bulan Ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-piggy-bank"></i></div>
            <div>
                <div class="stat-value" style="font-size:18px;color:<?= $saldoAll>=0?'#1565C0':'#C62828' ?>"><?= rupiah($saldoAll) ?></div>
                <div class="stat-label">Saldo Total</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Ringkasan per Kategori -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-tags me-2"></i>Ringkasan per Kategori</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($perKategori as $pk): ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom d-flex align-items-center gap-3">
                        <div style="width:36px;height:36px;border-radius:8px;background:<?= $pk['warna'] ?>20;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fas <?= $pk['ikon'] ?>" style="color:<?= $pk['warna'] ?>"></i>
                        </div>
                        <div style="flex:1">
                            <div style="font-size:13px;font-weight:600"><?= sanitize($pk['nama']) ?></div>
                            <span class="badge bg-<?= $pk['tipe']==='masuk'?'success':'danger' ?>" style="font-size:10px"><?= ucfirst($pk['tipe']) ?></span>
                        </div>
                        <div style="font-size:13px;font-weight:700;color:<?= $pk['tipe']==='masuk'?'#2E7D32':'#C62828' ?>">
                            <?= rupiah($pk['total']) ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Transaksi Terbaru -->
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-clock-rotate-left me-2"></i>Transaksi Terbaru</span>
                <a href="?tab=transaksi&bulan=<?= $bulan ?>" class="btn-outline-green" style="padding:3px 10px;font-size:12px">Semua</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($transaksiList)): ?>
                <div class="empty-state py-4"><i class="fas fa-money-bill"></i><p>Belum ada transaksi bulan ini</p></div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach (array_slice($transaksiList, 0, 6) as $t): ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom d-flex align-items-center gap-3">
                        <div style="width:34px;height:34px;border-radius:8px;background:<?= $t['warna'] ?>20;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fas <?= $t['ikon'] ?>" style="color:<?= $t['warna'] ?>;font-size:14px"></i>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($t['keterangan']) ?></div>
                            <div style="font-size:11px;color:#888"><?= sanitize($t['nama_kat']) ?> · <?= formatTanggal($t['tanggal']) ?></div>
                        </div>
                        <div style="font-size:13px;font-weight:700;color:<?= $t['tipe']==='masuk'?'#2E7D32':'#C62828' ?>;flex-shrink:0">
                            <?= $t['tipe']==='masuk'?'+':'-' ?><?= rupiah($t['jumlah']) ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($tab === 'transaksi'): ?>
<!-- ===== TRANSAKSI ===== -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2"></i>Semua Transaksi — <?= date('F Y', strtotime($bulan.'-01')) ?></span>
        <div class="d-flex gap-2">
            <span class="badge bg-success"><?= rupiah($totalMasuk) ?> masuk</span>
            <span class="badge bg-danger"><?= rupiah($totalKeluar) ?> keluar</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Tipe</th><th>Jumlah</th><th>Dicatat</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php if (empty($transaksiList)): ?>
                <tr><td colspan="7"><div class="empty-state py-4"><i class="fas fa-money-bill-wave"></i><p>Belum ada transaksi bulan ini</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($transaksiList as $t): ?>
                <tr>
                    <td><small><?= formatTanggal($t['tanggal']) ?></small></td>
                    <td>
                        <strong><?= sanitize($t['keterangan']) ?></strong>
                        <?php if ($t['nama_santri']): ?>
                        <br><small class="text-muted"><i class="fas fa-user-graduate me-1"></i><?= sanitize($t['nama_santri']) ?></small>
                        <?php endif; ?>
                        <?php if ($t['nama_ustad'] && !$t['nama_santri']): ?>
                        <br><small class="text-muted"><i class="fas fa-chalkboard-user me-1"></i><?= sanitize($t['nama_ustad']) ?></small>
                        <?php endif; ?>
                        <?php if ($t['catatan']): ?>
                        <br><small class="text-muted"><?= sanitize($t['catatan']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;background:<?= $t['warna'] ?>20;color:<?= $t['warna'] ?>;padding:3px 8px;border-radius:6px;font-weight:600">
                            <i class="fas <?= $t['ikon'] ?>"></i><?= sanitize($t['nama_kat']) ?>
                        </span>
                    </td>
                    <td><span class="badge bg-<?= $t['tipe']==='masuk'?'success':'danger' ?>"><?= ucfirst($t['tipe']) ?></span></td>
                    <td><strong style="color:<?= $t['tipe']==='masuk'?'#2E7D32':'#C62828' ?>"><?= $t['tipe']==='masuk'?'+':'-' ?><?= rupiah($t['jumlah']) ?></strong></td>
                    <td><small class="text-muted"><?= sanitize($t['nama_pencatat'] ?? '-') ?></small></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="act" value="hapus_transaksi">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Hapus transaksi ini?">
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

<?php elseif ($tab === 'gaji'): ?>
<!-- ===== GAJI USTAD ===== -->
<div class="row g-4">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><i class="fas fa-user-tie me-2"></i>Daftar Gaji Ustad</div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Ustad</th><th>Gaji Pokok</th><th>Status Bulan Ini</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if (empty($gajiUstad)): ?>
                        <tr><td colspan="4"><div class="empty-state py-3"><p>Belum ada data gaji. Atur gaji ustad di panel kanan.</p></div></td></tr>
                        <?php else: ?>
                        <?php foreach ($gajiUstad as $g): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0"><?= avatarInitial($g['nama']) ?></div>
                                    <strong><?= sanitize($g['nama']) ?></strong>
                                </div>
                            </td>
                            <td><strong class="text-success"><?= rupiah($g['gaji_pokok']) ?></strong></td>
                            <td>
                                <?php if (in_array($g['ustad_id'], $gajiBulanIni)): ?>
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sudah Dibayar</span>
                                <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Belum Dibayar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!in_array($g['ustad_id'], $gajiBulanIni)): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="act" value="bayar_gaji">
                                    <input type="hidden" name="ustad_id" value="<?= $g['ustad_id'] ?>">
                                    <input type="hidden" name="nama_ustad" value="<?= sanitize($g['nama']) ?>">
                                    <input type="hidden" name="jumlah" value="<?= $g['gaji_pokok'] ?>">
                                    <input type="hidden" name="bulan_gaji" value="<?= $bulan ?>">
                                    <button type="submit" class="btn btn-sm btn-success" data-confirm="Bayar gaji <?= sanitize($g['nama']) ?> sebesar <?= rupiah($g['gaji_pokok']) ?>?">
                                        <i class="fas fa-money-bill-wave me-1"></i>Bayar
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted" style="font-size:12px">Terbayar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><i class="fas fa-gear me-2"></i>Atur Gaji Ustad</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="act" value="simpan_gaji">
                    <div class="mb-3">
                        <label class="form-label">Pilih Ustad *</label>
                        <select name="ustad_id" class="form-select" required>
                            <option value="">— Pilih —</option>
                            <?php foreach ($ustadList as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= sanitize($u['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gaji Pokok per Bulan *</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="gaji_pokok" class="form-control" placeholder="0" required min="0">
                        </div>
                    </div>
                    <button type="submit" class="btn-primary-green w-100 justify-content-center">
                        <i class="fas fa-save"></i>Simpan Gaji
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php elseif ($tab === 'laporan'): ?>
<!-- ===== LAPORAN ===== -->
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Grafik Keuangan 6 Bulan Terakhir</div>
            <div class="card-body">
                <canvas id="grafikKeuangan" style="max-height:320px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-arrow-down me-2 text-success"></i>Total Pemasukan All Time</div>
            <div class="card-body text-center py-4">
                <div style="font-size:36px;font-weight:800;color:#2E7D32"><?= rupiah($totalMasukAll) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-arrow-up me-2 text-danger"></i>Total Pengeluaran All Time</div>
            <div class="card-body text-center py-4">
                <div style="font-size:36px;font-weight:800;color:#C62828"><?= rupiah($totalKeluarAll) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fas fa-piggy-bank me-2"></i>Saldo Kas Keseluruhan</div>
            <div class="card-body text-center py-4">
                <div style="font-size:42px;font-weight:800;color:<?= $saldoAll>=0?'#2E7D32':'#C62828' ?>"><?= rupiah($saldoAll) ?></div>
                <p class="text-muted mt-2"><?= $saldoAll >= 0 ? 'Keuangan pesantren dalam kondisi sehat ✅' : '⚠️ Pengeluaran melebihi pemasukan' ?></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = <?= json_encode(array_column($grafik, 'bulan')) ?>;
const masukData = <?= json_encode(array_column($grafik, 'masuk')) ?>;
const keluarData = <?= json_encode(array_column($grafik, 'keluar')) ?>;

new Chart(document.getElementById('grafikKeuangan'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            { label: 'Pemasukan', data: masukData, backgroundColor: '#2E7D32', borderRadius: 6 },
            { label: 'Pengeluaran', data: keluarData, backgroundColor: '#C62828', borderRadius: 6 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } } }
    }
});
</script>
<?php endif; ?>

<!-- Modal Tambah Transaksi -->
<div class="modal fade" id="modalTambahTransaksi" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Catat Transaksi Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="act" value="tambah_transaksi">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategori *</label>
                            <select name="kategori_id" class="form-select" required>
                                <option value="">— Pilih Kategori —</option>
                                <?php foreach ($kategoriList as $k): ?>
                                <option value="<?= $k['id'] ?>">[<?= ucfirst($k['tipe']) ?>] <?= sanitize($k['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal *</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan *</label>
                            <input type="text" name="keterangan" class="form-control" required placeholder="cth: Infaq Hamba Allah, SPP Rafi bulan April...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah (Rp) *</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="jumlah" class="form-control" required min="0" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Terkait Santri <small class="text-muted">(opsional)</small></label>
                            <select name="santri_id" class="form-select">
                                <option value="">— Pilih Santri —</option>
                                <?php foreach ($santriList as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= sanitize($s['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan opsional..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-green"><i class="fas fa-save"></i>Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>