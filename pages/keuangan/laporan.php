<?php
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
include '../../includes/header.php';

// Data untuk Diagram
$masuk = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM log_keuangan WHERE kategori='Masuk'")->fetch_assoc();
$keluar = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM log_keuangan WHERE kategori='Keluar'")->fetch_assoc();
?>

<div class="container-fluid p-4">
    <h1 class="h3 mb-4">Diagram Perkembangan Keuangan</h1>

    <div class="card shadow">
        <div class="card-body">
            <div style="height: 400px;">
                <canvas id="chartKeuangan"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('chartKeuangan').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Total Pemasukan', 'Total Pengeluaran'],
            datasets: [{
                label: 'Status Keuangan (Rp)',
                data: [<?= (int)$masuk['total'] ?>, <?= (int)$keluar['total'] ?>],
                backgroundColor: ['#1cc88a', '#e74a3b']
            }]
        },
        options: { maintainAspectRatio: false }
    });
</script>

<?php include '../../includes/footer.php'; ?>