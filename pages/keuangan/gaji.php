<?php
require_once '../../includes/auth_check.php';
requireRole(['admin','ustad']);

$db   = getDB();
$uid  = $user['id'];
$role = $user['role'];
$pageTitle = 'Gaji Ustad';

// ===== PROSES (admin only) =====
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'generate') {
        // Generate tagihan gaji bulan ini untuk semua ustad
        $bulan   = date('Y-m-01', strtotime($_POST['bulan'].'-01'));
        $nominal = (int)$_POST['nominal'];
        $ustadList = $db->query("SELECT id FROM users WHERE role='ustad' AND status='aktif'")->fetchAll();
        foreach ($ustadList as $u) {
            $db->prepare("INSERT IGNORE INTO gaji_ustad (ustad_id,bulan,nominal) VALUES (?,?,?)")
               ->execute([$u['id'],$bulan,$nominal]);
        }
        flashMessage('success', 'Tagihan gaji berhasil di-generate untuk '.count($ustadList).' ustad.');
        redirect('gaji.php');
    }

    if ($act === 'bayar') {
        $id      = (int)$_POST['id'];
        $stmt    = $db->prepare("SELECT * FROM gaji_ustad WHERE id=?");
        $stmt->execute([$id]);
        $gaji = $stmt->fetch();

        if ($gaji && $gaji['status'] === 'pending') {
            // Catat ke transaksi
            $tanggal = date('Y-m-d');
            $bulanLabel = date('F Y', strtotime($gaji['bulan']));
            $ustadNama = $db->prepare("SELECT nama FROM users WHERE id=?");
            $ustadNama->execute([$gaji['ustad_id']]); $ustadNama = $ustadNama->fetchColumn();

            $tStmt = $db->prepare("INSERT INTO transaksi_keuangan (jenis,sumber,keterangan,jumlah,ustad_id,dicatat_oleh,tanggal) VALUES ('keluar','gaji',?,?,?,?,?)");
            $tStmt->execute(["Gaji $ustadNama — $bulanLabel", $gaji['nominal'], $gaji['ustad_id'], $uid, $tanggal]);
            $transaksiId = $db->lastInsertId();

            // Update status gaji
            $db->prepare("UPDATE gaji_ustad SET status='dibayar',dibayar_oleh=?,dibayar_pada=NOW(),transaksi_id=? WHERE id=?")
               ->execute([$uid,$transaksiId,$id]);
            flashMessage('success', "Gaji $ustadNama untuk $bulanLabel berhasil dibayar.");
        }
        redirect('gaji.php');
    }

    if ($act === 'set_nominal') {
        $id      = (int)$_POST['id'];
        $nominal = (int)$_POST['nominal'];
        $db->prepare("UPDATE gaji_ustad SET nominal=? WHERE id=? AND status='pending'")->execute([$nominal,$id]);
        flashMessage('success', 'Nominal gaji diperbarui.');
        redirect('gaji.php');
    }
}

$filterBulan = $_GET['bulan'] ?? date('Y-m');
$bulanDate   = $filterBulan.'-01';

$stmt = $db->prepare("SELECT g.*, u.nama as nama_ustad, u.email as email_ustad,
    admin.nama as nama_pembayar
    FROM gaji_ustad g
    JOIN users u ON g.ustad_id=u.id
    LEFT JOIN users admin ON g.dibayar_oleh=admin.id
    WHERE DATE_FORMAT(g.bulan,'%Y-%m')=?
    ORDER BY g.status DESC, u.nama");
$stmt->execute([$filterBulan]);
$gajiList = $stmt->fetchAll();

$totalNominal = array_sum(array_column($gajiList,'nominal'));
$sudahDibayar = array_sum(array_column(array_filter($gajiList, fn($g)=>$g['status']==='dibayar'), 'nominal'));
$pending      = array_sum(array_column(array_filter($gajiList, fn($g)=>$g['status']==='pending'), 'nominal'));

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="page-title mb-1">Gaji Ustad</h4>
        <p class="page-subtitle">Kelola dan bayar gaji ustad per bulan</p>
    </div>
    <?php if ($role === 'admin'): ?>
    <button class="btn-primary-green" data-bs-toggle="modal" data-bs-target="#modalGenerate">
        <i class="fas fa-calendar-plus"></i>Generate Gaji
    </button>
    <?php endif; ?>
</div>

<!-- Filter Bulan -->
<div class="card mb-3"><div class="card-body p-3">
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="form-label mb-0 fw-bold">Bulan:</label>
        <input type="month" name="bulan" class="form-control" style="max-width:180px" value="<?= $filterBulan ?>" onchange="this.form.submit()">
        <span class="text-muted" style="font-size:13px"><?= date('F Y', strtotime($bulanDate)) ?></span>
    </form>
</div></div>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center p-3" style="border-top:3px solid #555">
            <div style="font-size:12px;color:#888">TOTAL GAJI</div>
            <div style="font-size:22px;font-weight:800">Rp <?= number_format($totalNominal,0,',','.') ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3" style="border-top:3px solid #2E7D32">
            <div style="font-size:12px;color:#888">SUDAH DIBAYAR</div>
            <div style="font-size:22px;font-weight:800;color:#2E7D32">Rp <?= number_format($sudahDibayar,0,',','.') ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3" style="border-top:3px solid #F57F17">
            <div style="font-size:12px;color:#888">PENDING</div>
            <div style="font-size:22px;font-weight:800;color:#F57F17">Rp <?= number_format($pending,0,',','.') ?></div>
        </div>
    </div>
</div>

<!-- List Gaji -->
<?php if (empty($gajiList)): ?>
<div class="card"><div class="empty-state py-5">
    <i class="fas fa-user-tie"></i>
    <p>Belum ada data gaji untuk bulan ini.<br>
    <?= $role==='admin'?'Klik <strong>Generate Gaji</strong> untuk membuat tagihan gaji.':'Hubungi admin.' ?>
    </p>
</div></div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr>
                <th>Ustad</th><th>Bulan</th><th>Nominal</th><th>Status</th><th>Dibayar Pada</th>
                <?php if ($role==='admin'): ?><th>Aksi</th><?php endif; ?>
            </tr></thead>
            <tbody>
                <?php foreach ($gajiList as $g): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0"><?= avatarInitial($g['nama_ustad']) ?></div>
                            <div>
                                <div style="font-weight:600;font-size:13px"><?= sanitize($g['nama_ustad']) ?></div>
                                <small class="text-muted"><?= sanitize($g['email_ustad']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= date('F Y', strtotime($g['bulan'])) ?></td>
                    <td><strong>Rp <?= number_format($g['nominal'],0,',','.') ?></strong></td>
                    <td>
                        <span class="badge bg-<?= $g['status']==='dibayar'?'success':'warning' ?>">
                            <?= $g['status']==='dibayar' ? '✓ Dibayar' : '⏳ Pending' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($g['dibayar_pada']): ?>
                        <small><?= formatTanggal($g['dibayar_pada'], 'd M Y') ?><br>
                        <span class="text-muted">oleh <?= sanitize($g['nama_pembayar'] ?? '-') ?></span></small>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($role === 'admin'): ?>
                    <td>
                        <?php if ($g['status'] === 'pending'): ?>
                        <div class="d-flex gap-1">
                            <!-- Edit nominal -->
                            <button class="btn btn-sm btn-outline-secondary"
                                onclick="editNominal(<?= $g['id'] ?>, <?= $g['nominal'] ?>)">
                                <i class="fas fa-pencil"></i>
                            </button>
                            <!-- Bayar -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="act" value="bayar">
                                <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success"
                                    data-confirm="Bayar gaji <?= sanitize($g['nama_ustad']) ?> Rp <?= number_format($g['nominal'],0,',','.') ?>?">
                                    <i class="fas fa-money-bill-wave"></i> Bayar
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <span class="text-success"><i class="fas fa-check-circle"></i> Lunas</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
<!-- Modal Generate -->
<div class="modal fade" id="modalGenerate" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">Generate Gaji Bulan Ini</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="act" value="generate">
                <div class="modal-body">
                    <div class="alert alert-info" style="font-size:13px">
                        Akan membuat tagihan gaji untuk semua ustad aktif. Jika sudah ada, tidak akan duplikat.
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Bulan *</label>
                            <input type="month" name="bulan" class="form-control" value="<?= date('Y-m') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nominal Gaji (Rp) *</label>
                            <input type="number" name="nominal" class="form-control" required
                                placeholder="cth: 500000" min="10000">
                            <div class="form-text">Nominal ini akan sama untuk semua ustad. Bisa diedit per ustad setelah generate.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-green"><i class="fas fa-calendar-plus"></i>Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Nominal -->
<div class="modal fade" id="modalNominal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">Edit Nominal Gaji</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="act" value="set_nominal">
                <input type="hidden" name="id" id="nominalId">
                <div class="modal-body">
                    <label class="form-label">Nominal (Rp)</label>
                    <input type="number" name="nominal" id="nominalVal" class="form-control" min="10000" required>
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
function editNominal(id, val) {
    document.getElementById('nominalId').value = id;
    document.getElementById('nominalVal').value = val;
    new bootstrap.Modal(document.getElementById('modalNominal')).show();
}
</script>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>