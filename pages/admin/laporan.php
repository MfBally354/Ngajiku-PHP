<?php
// pages/admin/laporan.php
require_once '../../includes/auth_check.php';
requireRole('admin');

$db = getDB();
$pageTitle = 'Laporan & Statistik';

// Statistik Global
$stats = [
    'total_santri'  => $db->query("SELECT COUNT(*) FROM users WHERE role='santri'")->fetchColumn(),
    'total_ustad'   => $db->query("SELECT COUNT(*) FROM users WHERE role='ustad'")->fetchColumn(),
    'total_parent'  => $db->query("SELECT COUNT(*) FROM users WHERE role='parent'")->fetchColumn(),
    'total_kelas'   => $db->query("SELECT COUNT(*) FROM kelas WHERE status='aktif'")->fetchColumn(),
    'total_materi'  => $db->query("SELECT COUNT(*) FROM materi WHERE status='publik'")->fetchColumn(),
    'total_tugas'   => $db->query("SELECT COUNT(*) FROM tugas")->fetchColumn(),
    'total_nilai'   => $db->query("SELECT COUNT(*) FROM nilai")->fetchColumn(),
    'avg_nilai'     => round($db->query("SELECT AVG(nilai_angka) FROM nilai")->fetchColumn() ?? 0, 1),
];

// Rekap nilai per kelas
$nilaiPerKelas = $db->query("SELECT k.nama_kelas, u.nama as nama_ustad,
    COUNT(DISTINCT sk.santri_id) as jml_santri,
    COUNT(n.id) as jml_nilai,
    ROUND(AVG(n.nilai_angka),1) as rata_rata,
    MAX(n.nilai_angka) as nilai_max,
    MIN(n.nilai_angka) as nilai_min
    FROM kelas k
    LEFT JOIN users u ON k.ustad_id=u.id
    LEFT JOIN santri_kelas sk ON k.id=sk.kelas_id
    LEFT JOIN nilai n ON k.id=n.kelas_id
    WHERE k.status='aktif'
    GROUP BY k.id ORDER BY rata_rata DESC")->fetchAll();

// Rekap absensi bulan ini
$bulanIni = date('Y-m');
$absensiRekap = $db->prepare("SELECT u.nama, k.nama_kelas,
    SUM(a.status='hadir') as hadir,
    SUM(a.status='izin') as izin,
    SUM(a.status='sakit') as sakit,
    SUM(a.status='alpha') as alpha,
    COUNT(a.id) as total
    FROM absensi a
    JOIN users u ON a.santri_id=u.id
    JOIN kelas k ON a.kelas_id=k.id
    WHERE DATE_FORMAT(a.tanggal,'%Y-%m')=?
    GROUP BY a.santri_id, a.kelas_id ORDER BY u.nama");
$absensiRekap->execute([$bulanIni]);
$absensiRekap = $absensiRekap->fetchAll();

// Tugas completion rate
$tugasRekap = $db->query("SELECT t.judul, k.nama_kelas,
    COUNT(DISTINCT sk.santri_id) as total_santri,
    COUNT(DISTINCT pt.santri_id) as sudah_kumpul,
    ROUND(COUNT(DISTINCT pt.santri_id)/COUNT(DISTINCT sk.santri_id)*100,0) as pct
    FROM tugas t
    JOIN kelas k ON t.kelas_id=k.id
    JOIN santri_kelas sk ON k.id=sk.kelas_id
    LEFT JOIN pengumpulan_tugas pt ON t.id=pt.tugas_id
    WHERE t.status='aktif'
    GROUP BY t.id ORDER BY pct ASC LIMIT 10")->fetchAll();

// Top santri (rata-rata tertinggi)
$topSantri = $db->query("SELECT u.nama,
    ROUND(AVG(n.nilai_angka),1) as rata_rata,
    COUNT(n.id) as jml_nilai
    FROM users u
    JOIN nilai n ON u.id=n.santri_id
    WHERE u.role='santri'
    GROUP BY u.id HAVING jml_nilai >= 3
    ORDER BY rata_rata DESC LIMIT 5")->fetchAll();

require_once '../../includes/header.php';
?>

<h4 class="page-title mb-1">Laporan & Statistik</h4>
<p class="page-subtitle mb-4">Ringkasan data platform NgajiKu</p>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['green', 'fa-user-graduate', $stats['total_santri'],  'Total Santri'],
        ['teal',  'fa-chalkboard-user',$stats['total_ustad'],  'Ustad'],
        ['orange','fa-user-tie',       $stats['total_parent'], 'Orang Tua'],
        ['blue',  'fa-school',         $stats['total_kelas'],  'Kelas Aktif'],
        ['purple','fa-book-open',      $stats['total_materi'], 'Materi'],
        ['red',   'fa-tasks',          $stats['total_tugas'],  'Tugas Dibuat'],
        ['green', 'fa-star',           $stats['total_nilai'],  'Total Penilaian'],
        ['teal',  'fa-chart-line',     $stats['avg_nilai'],    'Rata-rata Nilai'],
    ];
    foreach ($cards as $c): ?>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon <?= $c[0] ?>"><i class="fas <?= $c[1] ?>"></i></div>
            <div><div class="stat-value"><?= $c[2] ?></div><div class="stat-label"><?= $c[3] ?></div></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Rekap Nilai per Kelas -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Rekap Nilai per Kelas</div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Kelas</th><th>Ustad</th><th>Santri</th><th>Rata-rata</th><th>Grade</th><th>Tertinggi</th><th>Terendah</th></tr></thead>
                    <tbody>
                        <?php if (empty($nilaiPerKelas)): ?>
                        <tr><td colspan="7"><div class="empty-state py-3"><p>Belum ada data nilai</p></div></td></tr>
                        <?php else: ?>
                        <?php foreach ($nilaiPerKelas as $n): ?>
                        <tr>
                            <td><strong><?= sanitize($n['nama_kelas']) ?></strong></td>
                            <td><?= sanitize($n['nama_ustad'] ?? '-') ?></td>
                            <td><?= $n['jml_santri'] ?></td>
                            <td>
                                <?php if ($n['rata_rata']): ?>
                                <strong class="text-<?= nilaiToWarna($n['rata_rata']) ?>"><?= $n['rata_rata'] ?></strong>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($n['rata_rata']): ?>
                                <span class="badge bg-<?= nilaiToWarna($n['rata_rata']) ?>"><?= nilaiToHuruf($n['rata_rata']) ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $n['nilai_max'] ?? '-' ?></td>
                            <td><?= $n['nilai_min'] ?? '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Santri -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-trophy me-2 text-warning"></i>Top Santri</div>
            <div class="card-body p-0">
                <?php if (empty($topSantri)): ?>
                <div class="empty-state py-3"><p>Data belum cukup</p></div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($topSantri as $i => $s): ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom d-flex align-items-center gap-2">
                        <div class="fw-bold" style="width:22px;color:<?= ['#F9A825','#90A4AE','#CD7F32'][$i] ?? '#888' ?>;font-size:16px">
                            <?= $i+1 ?>
                        </div>
                        <div class="avatar-circle" style="width:30px;height:30px;font-size:11px;flex-shrink:0"><?= avatarInitial($s['nama']) ?></div>
                        <div style="flex:1">
                            <div style="font-size:13px;font-weight:600"><?= sanitize($s['nama']) ?></div>
                            <small class="text-muted"><?= $s['jml_nilai'] ?> penilaian</small>
                        </div>
                        <span class="fw-bold text-<?= nilaiToWarna($s['rata_rata']) ?>"><?= $s['rata_rata'] ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Completion Rate Tugas -->
        <div class="card mt-3">
            <div class="card-header"><i class="fas fa-tasks me-2"></i>Tugas — Tingkat Kumpul</div>
            <div class="card-body p-0">
                <?php if (empty($tugasRekap)): ?>
                <div class="empty-state py-3"><p>Belum ada data</p></div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($tugasRekap as $t): ?>
                    <li class="list-group-item px-3 py-2 border-0 border-bottom">
                        <div style="font-size:13px;font-weight:600;margin-bottom:4px"><?= sanitize($t['judul']) ?></div>
                        <div style="font-size:11px;color:#888;margin-bottom:6px">
                            <?= sanitize($t['nama_kelas']) ?> · <?= $t['sudah_kumpul'] ?>/<?= $t['total_santri'] ?> santri
                        </div>
                        <div class="progress" style="height:6px;border-radius:3px">
                            <div class="progress-bar bg-<?= $t['pct']>=80?'success':($t['pct']>=50?'warning':'danger') ?>"
                                style="width:<?= $t['pct'] ?>%"></div>
                        </div>
                        <small class="text-muted"><?= $t['pct'] ?>%</small>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Rekap Absensi Bulan Ini -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-calendar-check me-2"></i>Rekap Absensi — <?= date('F Y') ?>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Santri</th><th>Kelas</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpha</th><th>Total</th><th>% Hadir</th></tr></thead>
                    <tbody>
                        <?php if (empty($absensiRekap)): ?>
                        <tr><td colspan="8"><div class="empty-state py-3"><p>Belum ada data absensi bulan ini</p></div></td></tr>
                        <?php else: ?>
                        <?php foreach ($absensiRekap as $a): ?>
                        <?php $pct = $a['total'] ? round($a['hadir']/$a['total']*100) : 0; ?>
                        <tr>
                            <td><strong><?= sanitize($a['nama']) ?></strong></td>
                            <td><?= sanitize($a['nama_kelas']) ?></td>
                            <td><span class="badge bg-success"><?= $a['hadir'] ?></span></td>
                            <td><span class="badge bg-info"><?= $a['izin'] ?></span></td>
                            <td><span class="badge bg-warning"><?= $a['sakit'] ?></span></td>
                            <td><span class="badge bg-danger"><?= $a['alpha'] ?></span></td>
                            <td><?= $a['total'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;border-radius:3px">
                                        <div class="progress-bar bg-<?= $pct>=80?'success':($pct>=60?'warning':'danger') ?>"
                                            style="width:<?= $pct ?>%"></div>
                                    </div>
                                    <small><?= $pct ?>%</small>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
