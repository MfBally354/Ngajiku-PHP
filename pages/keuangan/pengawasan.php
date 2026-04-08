<?php
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
include '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <h1 class="h3 mb-4">Pengawasan Masukkan Keuangan</h1>

    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Log Audit Transaksi</h6>
            <button onclick="window.print()" class="btn btn-sm btn-secondary">Cetak PDF</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="bg-light">
                        <tr>
                            <th>Waktu</th>
                            <th>Keterangan</th>
                            <th>Kategori</th>
                            <th>Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $data = mysqli_query($conn, "SELECT * FROM log_keuangan ORDER BY tanggal DESC");
                        while($row = mysqli_fetch_assoc($data)):
                        ?>
                        <tr>
                            <td><?= $row['tanggal'] ?></td>
                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                            <td>
                                <span class="badge <?= $row['kategori'] == 'Masuk' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $row['kategori'] ?>
                                </span>
                            </td>
                            <td>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>