<?php
// Naik 2 level untuk mengakses config & includes di root 'ngajiku/'
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
include '../../includes/header.php';

// Logika Input Data (Barang, Harga, Infaq)
if (isset($_POST['simpan_transaksi'])) {
    $ket = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $jml = $_POST['jumlah'];
    $kat = $_POST['kategori']; 
    
    $query = "INSERT INTO log_keuangan (kategori, keterangan, jumlah) VALUES ('$kat', '$ket', '$jml')";
    if(mysqli_query($conn, $query)) {
        echo "<script>alert('Data Berhasil Dicatat!'); window.location='dashboard.php';</script>";
    }
}

// Ambil Ringkasan Data
$q_infaq = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM log_keuangan WHERE kategori='Infaq'")->fetch_assoc();
$q_masuk = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM log_keuangan WHERE kategori='Masuk'")->fetch_assoc();
$q_keluar = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM log_keuangan WHERE kategori='Keluar'")->fetch_assoc();
?>

<div class="container-fluid p-4">
    <h1 class="h3 mb-4 text-success">Manajemen Keuangan & Infaq</h1>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-left-warning shadow py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Infaq Terkumpul</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($q_infaq['total'] ?? 0, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-left-success shadow py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pemasukan Lainnya</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($q_masuk['total'] ?? 0, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-left-danger shadow py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Pengeluaran</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($q_keluar['total'] ?? 0, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold">Tambah Transaksi / Infaq</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Keterangan Barang/Jasa</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Infaq Hamba Allah / Beli Iqra" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nominal (Harga)</label>
                            <input type="number" name="jumlah" class="form-control" placeholder="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="kategori" class="form-select">
                                <option value="Infaq">Infaq / Sedekah</option>
                                <option value="Masuk">Pemasukan (SPP/Daftar)</option>
                                <option value="Keluar">Pengeluaran (Beli Barang/Gaji)</option>
                            </select>
                        </div>
                        <button type="submit" name="simpan_transaksi" class="btn btn-success w-100">Simpan Data</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7 text-center">
            <div class="p-5 bg-white shadow rounded border">
                <h4>Menu Pengelolaan</h4>
                <hr>
                <div class="d-grid gap-3">
                    <a href="laporan.php" class="btn btn-primary btn-lg">Lihat Diagram Perkembangan</a>
                    <a href="pengawasan.php" class="btn btn-dark btn-lg">Buka Log Pengawasan Keuangan</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>