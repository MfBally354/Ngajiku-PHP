<?php
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
include '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between mb-4">
        <h1 class="h3">Pengawasan Arus Kas</h1>
        <button onclick="window.print()" class="btn btn-secondary btn-sm">Cetak Laporan</button>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-success">
                        <tr>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>Kategori</th>
                            <th>Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = mysqli_query($conn, "SELECT * FROM log_keuangan ORDER BY tanggal DESC");
                        while($row = mysqli_fetch_assoc($res)):
                        ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                            <td>
                                <?php if($row['kategori'] == 'Infaq'): ?>
                                    <span class="badge bg-warning text-dark">Infaq</span>
                                <?php elseif($row['kategori'] == 'Masuk'): ?>
                                    <span class="badge bg-success">Masuk</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Keluar</span>
                                <?php endif; ?>
                            </td>
                            <td><strong>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>