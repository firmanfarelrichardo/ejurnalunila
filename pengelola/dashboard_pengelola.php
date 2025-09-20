<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pengelola') {
    header("Location: login.php");
    exit();
}

<<<<<<< HEAD
$pengelola_id = $_SESSION['user_id'];

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db = "oai";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
=======
// Konfigurasi database MySQL
require_once '../database/config.php';
$conn = connect_to_database();
>>>>>>> 9352bd23106f96148cb84f4a625531b8660b0bc5

// --- DATA UNTUK KARTU STATISTIK ---
$totalJurnals = $conn->query("SELECT COUNT(*) FROM jurnal_sumber WHERE pengelola_id = $pengelola_id")->fetch_row()[0];
$pendingJurnals = $conn->query("SELECT COUNT(*) FROM jurnal_sumber WHERE pengelola_id = $pengelola_id AND status = 'pending'")->fetch_row()[0];
$approvedJurnals = $conn->query("SELECT COUNT(*) FROM jurnal_sumber WHERE pengelola_id = $pengelola_id AND status = 'selesai'")->fetch_row()[0];
$rejectedJurnals = $conn->query("SELECT COUNT(*) FROM jurnal_sumber WHERE pengelola_id = $pengelola_id AND status = 'ditolak'")->fetch_row()[0];


// --- DATA UNTUK GRAFIK ---

// 1. DATA STATUS JURNAL (DOUGHNUT CHART)
$jurnal_status_data = ['selesai' => 0, 'ditolak' => 0, 'butuh_edit' => 0, 'pending' => 0];
$jurnal_status_query = $conn->query("SELECT status, COUNT(*) as count FROM jurnal_sumber WHERE pengelola_id = $pengelola_id GROUP BY status");
if ($jurnal_status_query) {
    while($row = $jurnal_status_query->fetch_assoc()) {
        if (array_key_exists($row['status'], $jurnal_status_data)) {
            $jurnal_status_data[$row['status']] = (int)$row['count'];
        }
    }
}
$jurnal_status_labels = ['Diterima', 'Ditolak', 'Butuh Edit', 'Pending'];
$jurnal_status_counts = array_values($jurnal_status_data);
$jurnal_status_colors = ['#2ecc71', '#e74c3c', '#3498db', '#f1c40f'];
$jurnal_status_chart_data = json_encode(['labels' => $jurnal_status_labels, 'data' => $jurnal_status_counts, 'colors' => $jurnal_status_colors]);


// 2. DATA AKREDITASI SINTA (BAR CHART)
$sinta_query = $conn->query("SELECT akreditasi_sinta, COUNT(*) as count FROM jurnal_sumber WHERE pengelola_id = $pengelola_id AND akreditasi_sinta IS NOT NULL AND akreditasi_sinta != '' GROUP BY akreditasi_sinta ORDER BY count DESC");
$sinta_labels = [];
$sinta_counts = [];
if ($sinta_query) {
    while($row = $sinta_query->fetch_assoc()) {
        $sinta_labels[] = $row['akreditasi_sinta'];
        $sinta_counts[] = $row['count'];
    }
}
$sinta_chart_data = json_encode(['labels' => $sinta_labels, 'data' => $sinta_counts]);

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengelola</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="sidebar-toggle-btn" id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                    <img src="../Images/logo-header-2024-normal.png" alt="Logo Universitas Lampung">
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_pengelola.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="tambah_jurnal.php" ><i class="fas fa-plus-circle"></i> <span>Daftar Jurnal Baru</span></a></li>
                <li><a href="daftar_jurnal.php"><i class="fas fa-list-alt"></i> <span>Daftar & Status Jurnal</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
                <div class="user-profile">
                    <span>Role: Pengelola</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-book-open"></i>
                        <div class="stat-info">
                            <h4>Total Jurnal</h4>
                            <p><?php echo $totalJurnals; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-check-circle"></i>
                        <div class="stat-info">
                            <h4>Jurnal Diterima</h4>
                            <p><?php echo $approvedJurnals; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-hourglass-half"></i>
                        <div class="stat-info">
                            <h4>Jurnal Pending</h4>
                            <p><?php echo $pendingJurnals; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-times-circle"></i>
                        <div class="stat-info">
                            <h4>Jurnal Ditolak</h4>
                            <p><?php echo $rejectedJurnals; ?></p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="card">
                        <canvas id="jurnalStatusChart"></canvas>
                    </div>
                    <div class="card">
                        <canvas id="sintaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Chart.register(ChartDataLabels);
    
    const jurnalStatusData = <?php echo $jurnal_status_chart_data; ?>;
    const sintaData = <?php echo $sinta_chart_data; ?>;

    const defaultOptions = (title) => ({
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: { display: true, text: title, font: { size: 16, weight: 'bold', family: 'Segoe UI' }, padding: { top: 10, bottom: 20 }, color: '#333' },
            legend: { position: 'bottom', labels: { font: { size: 12, family: 'Segoe UI' }, padding: 20 } }
        }
    });
    
    const doughnutOptions = (title) => ({
        ...defaultOptions(title),
        plugins: {
            ...defaultOptions(title).plugins,
            datalabels: {
                formatter: (value, ctx) => {
                    if (value === 0) return '';
                    let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                    if (sum === 0) return '0\n(0%)';
                    let percentage = (value * 100 / sum).toFixed(1) + "%";
                    return `${value}\n(${percentage})`;
                },
                color: '#fff',
                font: { weight: 'bold', size: 14 },
                textStrokeColor: 'black',
                textStrokeWidth: 1,
            }
        }
    });

    new Chart(document.getElementById('jurnalStatusChart'), {
        type: 'doughnut',
        data: {
            labels: jurnalStatusData.labels,
            datasets: [{
                data: jurnalStatusData.data,
                backgroundColor: jurnalStatusData.colors,
                hoverOffset: 4
            }]
        },
        options: doughnutOptions('Distribusi Status Jurnal Saya')
    });

    new Chart(document.getElementById('sintaChart'), {
        type: 'bar',
        data: {
            labels: sintaData.labels,
            datasets: [{
                label: 'Jumlah Jurnal',
                data: sintaData.data,
                backgroundColor: '#9b59b6'
            }]
        },
        options: defaultOptions('Distribusi Peringkat SINTA Jurnal Saya')
    });
});

document.getElementById('sidebar-toggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    if (document.getElementById('sidebar').classList.contains('collapsed')) {
        localStorage.setItem('sidebarStatePengelola', 'collapsed');
    } else {
        localStorage.setItem('sidebarStatePengelola', 'expanded');
    }
});
if (localStorage.getItem('sidebarStatePengelola') === 'collapsed') {
    document.getElementById('sidebar').classList.add('collapsed');
}
</script>
</body>
</html>