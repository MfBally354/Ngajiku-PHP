<?php
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
include '../../includes/header.php';

// Query Data
$infaq = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM log_keuangan WHERE kategori='Infaq'")->fetch_assoc();
$masuk = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM log_keuangan WHERE kategori='Masuk'")->fetch_assoc();
$keluar = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM log_keuangan WHERE kategori='Keluar'")->fetch_assoc();
?>

<div class="container-fluid p-4">
    <h1 class="h3 mb-4">Laporan Visual Keuangan</h1>
    <div class="card shadow">
        <div class="card-body">
            <div style="height: 400px;">
                <canvas id="myChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('myChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Infaq', 'Pemasukan Lain', 'Pengeluaran'],
            datasets: [{
                label: 'Jumlah dalam Rupiah',
                data: [<?= (int)$infaq['total'] ?>, <?= (int)$masuk['total'] ?>, <?= (int)$keluar['total'] ?>],
                backgroundColor: ['#f6c23e', '#1cc88a', '#e74a3b']
            }]
        },
        options: { maintainAspectRatio: false }
    });
</script>

<?php include '../../includes/footer.php'; ?>