<?php
// pages/santri/pengumuman.php
session_start();
require_once '../../includes/auth_check.php';
requireRole('santri');

$db = getDB();
$pageTitle = 'Pengumuman';

$stmt = $db->query("SELECT p.*, u.nama as penulis FROM pengumuman p JOIN users u ON p.penulis_id=u.id WHERE FIND_IN_SET('santri', p.target_role) ORDER BY p.created_at DESC");
$list = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<h4 class="page-title mb-1">Pengumuman</h4>
<p class="page-subtitle mb-4">Informasi penting dari ustad dan admin</p>

<?php if (empty($list)): ?>
<div class="card"><div class="empty-state py-5"><i class="fas fa-bullhorn"></i><p>Tidak ada pengumuman saat ini.</p></div></div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($list as $p): ?>
    <div class="col-md-6">
        <div class="card h-100" style="border-left:4px solid <?= $p['prioritas']==='darurat'?'#c62828':($p['prioritas']==='penting'?'#f57f17':'#2E7D32') ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="badge bg-<?= $p['prioritas']==='darurat'?'danger':($p['prioritas']==='penting'?'warning':'success') ?>"><?= ucfirst($p['prioritas']) ?></span>
                    <small class="text-muted"><?= formatTanggal($p['created_at']) ?></small>
                </div>
                <h6 style="font-weight:700;margin-bottom:8px"><?= sanitize($p['judul']) ?></h6>
                <p style="font-size:13px;color:#555;line-height:1.7"><?= nl2br(sanitize($p['isi'])) ?></p>
                <small class="text-muted"><i class="fas fa-user me-1"></i><?= sanitize($p['penulis']) ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
