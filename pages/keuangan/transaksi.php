<?php
require_once '../../includes/auth_check.php';
requireRole(['admin','ustad']);

$db   = getDB();
$uid  = $user['id'];
$role = $user['role'];
$pageTitle = 'Transaksi Keuangan';

// ===== PROSES (admin only) =====
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'tambah') {
        $jenis      = $_POST['jenis'] ?? 'masuk';
        $sumber     = $_POST['sumber'] ?? 'lainnya';
        $kat_id     = (int)($_POST['kategori_id'] ?? 0) ?: null;
        $ket        = sanitize($_POST['keterangan'] ?? '');
        $jumlah     = (int)str_replace(['.','Rp',' '], '', $_POST['jumlah'] ?? 0);
        $tgl        = $_POST['tanggal'];
        $santri_id  = (int)($_POST['santri_id'] ?? 0) ?: null;

        if ($jumlah > 0 && !empty($ket)) {
            // Jika SPP — update tagihan
            if ($sumber === 'spp' && $santri_id) {
                $bulan = date('Y-m-01', strtotime($tgl));
                $kelas_id = (int)($_POST['kelas_id'] ?? 0);
                $stmt = $db->prepare("INSERT INTO tagihan_spp (santri_id,kelas_id,bulan,nominal,status) VALUES (?,?,?,?,'lunas')
                    ON DUPLICATE KEY UPDATE status='lunas', nominal=VALUES(nominal)");
                $stmt->execute([$santri_id,$kelas_id,$bulan,$jumlah]);
            }

            $stmt = $db->prepare("INSERT INTO transaksi_keuangan (kategori_id,jenis,sumber,keterangan,jumlah,santri_id,dicatat_oleh,tanggal) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$kat_id,$jenis,$sumber,$ket,$jumlah,$santri_id,$uid,$tgl]);
            flashMessage('success', 'Transaksi berhasil dicatat.');
        } else {
            flashMessage('error', 'Jumlah dan keterangan wajib diisi.');
        }
        redirect('transaksi.php');
    }

    if ($act === 'hapus') {
        $db->prepare("DELETE FROM transaksi_keuangan WHERE id=?")->execute([(int)$_POST['id']]);
        flashMessage('success', 'Transaksi dihapus.');
        redirect('transaksi.php');
    }
}

// ===== FILTER =====
$filterJenis  = $_GET['jenis'] ?? '';
$filterSumber = $_GET['sumber'] ?? '';
$filterBulan  = $_GET['bulan'] ?? '';
$search       = sanitize($_GET['q'] ?? '');

$where  = "WHERE 1=1";
$params = [];
if ($filterJenis)  { $where .= " AND t.jenis=?";                    $params[] = $filterJenis; }
if ($filterSumber) { $where .= " AND t.sumber=?";                   $params[] = $filterSumber; }
if ($filterBulan)  { $where .= " AND DATE_FORMAT(t.tanggal,'%Y-%m')=?"; $params[] = $filterBulan; }
if ($search)       { $where .= " AND t.keterangan LIKE ?";          $params[] = "%$search%"; }

$stmt = $db->prepare("SELECT t.*, u.nama as pencatat, k.nama as nama_kat,
    s.nama as nama_santri
    FROM transaksi_keuangan t
    JOIN users u ON t.dicatat_oleh=u.id
    LEFT JOIN kategori_keuangan k ON t.kategori_id=k.id
    LEFT JOIN users s ON t.santri_id=s.id
    $where ORDER BY t.tanggal DESC, t.created_at DESC");
$stmt->execute($params);
$list = $stmt->fetchAll();

$totalMasuk  = array_sum(array_column(array_filter($list, fn($r) => $r['jenis']==='masuk'), 'jumlah'));
$totalKeluar = array_sum(array_column(array_filter($list, fn($r) => $r['jenis']==='keluar'), 'jumlah'));

$kategoriList = $db->query("SELECT * FROM kategori_keuangan ORDER BY jenis, nama")->fetchAll();
$santriList   = $db->query("SELECT id,nama FROM users WHERE role='santri' AND status='aktif' ORDER BY nama")->fetchAll();
$kelasList    = $db->query("SELECT * FROM kelas WHERE status='aktif' ORDER BY nama_kelas")->fetchAll();

$defaultSumber = $_GET['sumber'] ?? 'lainnya';
$defaultJenis  = in_array($defaultSumber,['infaq','spp']) ? 'masuk' : 'keluar';

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Transaksi Keuangan</h4>
        <p class="page-subtitle">Catat dan pantau semua arus kas pesantren</p>
    </div>
    <?php if ($role === 'admin'): ?>
    <button class="btn-primary-green" data-bs-toggle="modal" data-bs-target="#modalTransaksi">
        <i class="fas fa-plus"></i>Catat Transaksi
    </button>
    <?php endif; ?>
</div>

<!-- Ringkasan filter -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div style="font-size:11px;color:#888">PEMASUKAN</div>
            <div style="font-size:18px;font-weight:800;color:#2E7D32">+Rp <?= number_format($totalMasuk,0,',','.') ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div style="font-size:11px;color:#888">PENGELUARAN</div>
            <div style="font-size:18px;font-weight:800;color:#c62828">-Rp <?= number_format($totalKeluar,0,',','.') ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div style="font-size:11px;color:#888">SELISIH</div>
            <?php $selisih = $totalMasuk - $totalKeluar; ?>
            <div style="font-size:18px;font-weight:800;color:<?= $selisih>=0?'#2E7D32':'#c62828' ?>">
                Rp <?= number_format($selisih,0,',','.') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div style="font-size:11px;color:#888">TOTAL DATA</div>
            <div style="font-size:18px;font-weight:800;color:#555"><?= count($list) ?> transaksi</div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3"><div class="card-body p-3">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
        <input type="text" name="q" class="form-control" style="max-width:200px" placeholder="Cari keterangan..." value="<?= sanitize($search) ?>">
        <select name="jenis" class="form-select" style="max-width:140px">
            <option value="">Semua Jenis</option>
            <option value="masuk"  <?= $filterJenis==='masuk' ?'selected':''?>>Masuk</option>
            <option value="keluar" <?= $filterJenis==='keluar'?'selected':''?>>Keluar</option>
        </select>
        <select name="sumber" class="form-select" style="max-width:160px">
            <option value="">Semua Sumber</option>
            <?php foreach (['infaq','spp','pengeluaran','gaji','lainnya'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterSumber===$s?'selected':''?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="month" name="bulan" class="form-control" style="max-width:160px" value="<?= $filterBulan ?>">
        <button type="submit" class="btn-primary-green"><i class="fas fa-filter"></i>Filter</button>
        <a href="transaksi.php" class="btn-outline-green">Reset</a>
    </form>
</div></div>

<!-- Tabel -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Sumber</th><th>Jenis</th><th>Jumlah</th><th>Dicatat</th><?php if($role==='admin'): ?><th>Aksi</th><?php endif; ?></tr></thead>
            <tbody>
                <?php if (empty($list)): ?>
                <tr><td colspan="8"><div class="empty-state py-4"><i class="fas fa-receipt"></i><p>Belum ada transaksi</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($list as $t): ?>
                <tr>
                    <td><small><?= formatTanggal($t['tanggal'], 'd M Y') ?></small></td>
                    <td>
                        <div style="font-weight:600;font-size:13px"><?= sanitize($t['keterangan']) ?></div>
                        <?php if ($t['nama_santri']): ?>
                        <small class="text-muted"><i class="fas fa-user me-1"></i><?= sanitize($t['nama_santri']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><small><?= sanitize($t['nama_kat'] ?? '-') ?></small></td>
                    <td><span class="badge bg-secondary"><?= ucfirst($t['sumber']) ?></span></td>
                    <td>
                        <span class="badge bg-<?= $t['jenis']==='masuk'?'success':'danger' ?>">
                            <?= $t['jenis']==='masuk'?'↑ Masuk':'↓ Keluar' ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-weight:700;color:<?= $t['jenis']==='masuk'?'#2E7D32':'#c62828' ?>">
                            <?= $t['jenis']==='masuk'?'+':'-' ?>Rp <?= number_format($t['jumlah'],0,',','.') ?>
                        </span>
                    </td>
                    <td><small class="text-muted"><?= sanitize($t['pencatat']) ?></small></td>
                    <?php if ($role === 'admin'): ?>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="act" value="hapus">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Hapus transaksi ini?">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($role === 'admin'): ?>
<!-- Modal Tambah Transaksi -->
<div class="modal fade" id="modalTransaksi" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">Catat Transaksi Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="act" value="tambah">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Jenis *</label>
                            <select name="jenis" class="form-select" id="jenisSelect">
                                <option value="masuk" <?= $defaultJenis==='masuk'?'selected':''?>>Pemasukan</option>
                                <option value="keluar" <?= $defaultJenis==='keluar'?'selected':''?>>Pengeluaran</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sumber *</label>
                            <select name="sumber" class="form-select" id="sumberSelect">
                                <option value="infaq"       <?= $defaultSumber==='infaq'?'selected':''?>>Infaq / Sedekah</option>
                                <option value="spp"         <?= $defaultSumber==='spp'?'selected':''?>>SPP Bulanan</option>
                                <option value="pengeluaran" <?= $defaultSumber==='pengeluaran'?'selected':''?>>Pengeluaran</option>
                                <option value="gaji"        <?= $defaultSumber==='gaji'?'selected':''?>>Gaji</option>
                                <option value="lainnya"     <?= $defaultSumber==='lainnya'?'selected':''?>>Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select name="kategori_id" class="form-select">
                                <option value="">— Pilih Kategori —</option>
                                <?php foreach ($kategoriList as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= sanitize($k['nama']) ?> (<?= $k['jenis'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal *</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan *</label>
                            <input type="text" name="keterangan" class="form-control" required placeholder="cth: Infaq dari Bapak Ahmad, SPP Rafi bulan Januari...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah (Rp) *</label>
                            <input type="number" name="jumlah" class="form-control" required min="1000" placeholder="50000">
                        </div>
                        <!-- Santri (muncul jika SPP/Infaq) -->
                        <div class="col-md-6" id="santriField">
                            <label class="form-label">Santri <small class="text-muted">(jika terkait)</small></label>
                            <select name="santri_id" class="form-select">
                                <option value="">— Pilih Santri —</option>
                                <?php foreach ($santriList as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= sanitize($s['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="kelasField" style="display:none">
                            <label class="form-label">Kelas</label>
                            <select name="kelas_id" class="form-select">
                                <option value="">— Pilih Kelas —</option>
                                <?php foreach ($kelasList as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= sanitize($k['nama_kelas']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-green"><i class="fas fa-save"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
const sumberSel = document.getElementById('sumberSelect');
function toggleFields() {
    const s = sumberSel.value;
    document.getElementById('santriField').style.display = ['infaq','spp'].includes(s) ? '' : 'none';
    document.getElementById('kelasField').style.display  = s === 'spp' ? '' : 'none';
}
sumberSel?.addEventListener('change', toggleFields);
toggleFields();
</script>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>