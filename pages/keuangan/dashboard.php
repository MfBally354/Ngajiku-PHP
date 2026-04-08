<?php
// Mengambil file dari struktur folder utama kelompok
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
include '../../includes/header.php';

// Proses Input Data Keuangan
if (isset($_POST['simpan_transaksi'])) {
    $ket = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $jml = $_POST['jumlah'];
    $kat = $_POST['kategori'];
    
    $query = "INSERT INTO log_keuangan (kategori, keterangan, jumlah) VALUES ('$kat', '$ket', '$jml')";
    if(mysqli_query($conn, $query)) {
        echo "<script>alert('Berhasil disimpan!'); window.location='dashboard.php';</script>";
    }
}
?>

<div class="container-fluid p-4">
    <h1 class="h3 mb-4">Dashboard Keuangan</h1>
    
    <div class="row">
        <div class="col-md-5">
            <div class="card shadow border-left-success">
                <div class="card-header bg-success text-white py-3">
                    <h6 class="m-0 font-weight-bold">Input Masukkan/Barang</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label>Keterangan (Contoh: Beli Buku/SPP)</label>
                            <input type="text" name="keterangan" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Nominal (Harga)</label>
                            <input type="number" name="jumlah" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Kategori</label>
                            <select name="kategori" class="form-select">
                                <option value="Masuk">Pemasukan</option>
                                <option value="Keluar">Pengeluaran (Beli Barang/Gaji)</option>
                            </select>
                        </div>
                        <button name="simpan_transaksi" class="btn btn-success w-100">Simpan Data</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7 text-center d-flex align-items-center">
            <div class="p-5 bg-light rounded-3 w-100 border">
                <h4>Navigasi Cepat</h4>
                <hr>
                <a href="laporan.php" class="btn btn-primary m-2">Lihat Diagram</a>
                <a href="pengawasan.php" class="btn btn-dark m-2">Pengawasan Log</a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>