<?php
// Mulai atau lanjutkan sesi
session_start();

// Cek apakah pengguna sudah login dan memiliki peran yang sesuai
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Pengaturan Database MySQL
$host = "localhost";
$user = "root";
$pass = "";
$db = "oai";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// --- DATA UNTUK KARTU STATISTIK UTAMA ---
$totalPengelola = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'pengelola'")->fetch_row()[0];
$totalJurnals = $conn->query("SELECT COUNT(*) FROM jurnal_sumber")->fetch_row()[0];
$pendingJurnals = $conn->query("SELECT COUNT(*) FROM jurnal_sumber WHERE status = 'pending'")->fetch_row()[0];
$pendingRequests = $conn->query("SELECT COUNT(*) FROM submission_requests WHERE status = 'pending'")->fetch_row()[0];


// --- DATA UNTUK GRAFIK ---

// 1. DATA AKTIVITAS HARVESTING (LINE CHART)
$harvest_activity_query = $conn->query(
    "SELECT 
        DATE(created_at) as activity_day, 
        COUNT(*) as new_article_count 
    FROM artikel_oai 
    WHERE created_at >= CURDATE() - INTERVAL 30 DAY 
    GROUP BY DATE(created_at) 
    ORDER BY activity_day ASC"
);
$harvest_activity_labels = [];
$harvest_activity_counts = [];
if ($harvest_activity_query) {
    while($row = $harvest_activity_query->fetch_assoc()) {
        $harvest_activity_labels[] = date("d M", strtotime($row['activity_day']));
        $harvest_activity_counts[] = $row['new_article_count'];
    }
}
$harvest_activity_chart_data = json_encode(['labels' => $harvest_activity_labels, 'data' => $harvest_activity_counts]);

// 2. JURNAL PER PENGELOLA (BAR CHART)
$pengelola_data_query = $conn->query("SELECT u.nama, COUNT(js.id) as total_jurnal FROM users u LEFT JOIN jurnal_sumber js ON u.id = js.pengelola_id WHERE u.role = 'pengelola' GROUP BY u.id, u.nama HAVING total_jurnal > 0 ORDER BY total_jurnal DESC");
$pengelola_labels = [];
$pengelola_counts = [];
while($row = $pengelola_data_query->fetch_assoc()) {
    $pengelola_labels[] = $row['nama'];
    $pengelola_counts[] = $row['total_jurnal'];
}
$pengelola_chart_data = json_encode(['labels' => $pengelola_labels, 'data' => $pengelola_counts]);

// 3. STATUS JURNAL (DOUGHNUT CHART)
$jurnal_status_data = ['selesai' => 0, 'ditolak' => 0, 'butuh_edit' => 0, 'pending' => 0];
$jurnal_status_query = $conn->query("SELECT status, COUNT(*) as count FROM jurnal_sumber GROUP BY status");
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

// 4. (DIPERBARUI) STATUS PERMINTAAN (DOUGHNUT CHART) - Tampilkan semua kategori
$request_status_data = [
    'approved' => 0,
    'rejected' => 0,
    'pending' => 0
];
$request_status_query = $conn->query("SELECT status, COUNT(*) as count FROM submission_requests GROUP BY status");
if ($request_status_query) {
    while($row = $request_status_query->fetch_assoc()) {
        if (array_key_exists($row['status'], $request_status_data)) {
            $request_status_data[$row['status']] = (int)$row['count'];
        }
    }
}
$request_status_labels = ['Approved', 'Rejected', 'Pending'];
$request_status_counts = [
    $request_status_data['approved'],
    $request_status_data['rejected'],
    $request_status_data['pending']
];
$request_status_colors = ['#2ecc71', '#e74c3c', '#f1c40f'];
$request_status_chart_data = json_encode(['labels' => $request_status_labels, 'data' => $request_status_counts, 'colors' => $request_status_colors]);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="admin_style.css">
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
                    <img src="../images/logo-header-2024-normal.png" alt="Logo Universitas Lampung">
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="header">
                <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></h1>
                <div class="user-profile">
                    <span>Role: Admin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-users-cog"></i>
                        <div class="stat-info">
                            <h4>Total Pengelola</h4>
                            <p><?php echo $totalPengelola; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-book-open"></i>
                        <div class="stat-info">
                            <h4>Total Jurnal</h4>
                            <p><?php echo $totalJurnals; ?></p>
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
                        <i class="fas fa-inbox"></i>
                        <div class="stat-info">
                            <h4>Permintaan Pending</h4>
                            <p><?php echo $pendingRequests; ?></p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="card">
                        <canvas id="harvestActivityChart"></canvas>
                    </div>
                    <div class="card">
                        <canvas id="pengelolaChart"></canvas>
                    </div>
                    <div class="card">
                        <canvas id="jurnalStatusChart"></canvas>
                    </div>
                    <div class="card">
                        <canvas id="requestStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    Chart.register(ChartDataLabels);

    const harvestActivityData = <?php echo $harvest_activity_chart_data; ?>;
    const pengelolaData = <?php echo $pengelola_chart_data; ?>;
    const jurnalStatusData = <?php echo $jurnal_status_chart_data; ?>;
    const requestStatusData = <?php echo $request_status_chart_data; ?>;

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

    // Inisialisasi Chart
    new Chart(document.getElementById('harvestActivityChart'), {
        type: 'line',
        data: {
            labels: harvestActivityData.labels,
            datasets: [{
                label: 'Jumlah Artikel Baru',
                data: harvestActivityData.data,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            ...defaultOptions('Aktivitas Harvesting (Artikel Baru per Hari)'),
            layout: {
                padding: {
                    right: 20
                }
            }
        }
    });

    new Chart(document.getElementById('pengelolaChart'), {
        type: 'bar',
        data: {
            labels: pengelolaData.labels,
            datasets: [{
                label: 'Jumlah Jurnal Diajukan',
                data: pengelolaData.data,
                backgroundColor: '#2ecc71'
            }]
        },
        options: { ...defaultOptions('Kontribusi Jurnal per Pengelola'), indexAxis: 'y' }
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
        options: doughnutOptions('Distribusi Status Jurnal')
    });

    new Chart(document.getElementById('requestStatusChart'), {
        type: 'doughnut',
        data: {
            labels: requestStatusData.labels,
            datasets: [{
                data: requestStatusData.data,
                backgroundColor: requestStatusData.colors,
                hoverOffset: 4
            }]
        },
        options: doughnutOptions('Distribusi Status Permintaan')
    });
});

// Script untuk sidebar toggle
document.getElementById('sidebar-toggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('collapsed');
        if (document.getElementById('sidebar').classList.contains('collapsed')) {
            localStorage.setItem('sidebarState', 'collapsed');
        } else {
            localStorage.setItem('sidebarState', 'expanded');
        }
    });

    if (localStorage.getItem('sidebarState') === 'collapsed') {
        document.getElementById('sidebar').classList.add('collapsed');
    }
</script>
</body>
</html>