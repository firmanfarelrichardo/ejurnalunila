<?php
// Mulai atau lanjutkan sesi
session_start();

// Cek apakah pengguna sudah login dan memiliki peran yang sesuai
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Jika tidak, arahkan ke halaman login
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

// Ambil data statistik untuk dashboard
$totalPengelola = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'pengelola'")->fetch_row()[0];
$totalJurnals = $conn->query("SELECT COUNT(*) FROM jurnal_sumber")->fetch_row()[0];
$pendingJurnals = $conn->query("SELECT COUNT(*) FROM jurnal_sumber WHERE status = 'pending'")->fetch_row()[0];
$pendingRequests = $conn->query("SELECT COUNT(*) FROM submission_requests WHERE status = 'pending'")->fetch_row()[0];


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>Admin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> Kelola Pengelola</a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        <!-- End Sidebar -->

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
                <div class="user-profile">
                    <span>Role: Admin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <div class="card">
                    <h3>Statistik Pengguna</h3>
                    <p>Total Pengelola: **<?php echo $totalPengelola; ?>**</p>
                </div>
                <div class="card">
                    <h3>Status Jurnal</h3>
                    <p>Total Submissions: **<?php echo $totalJurnals; ?>**</p>
                    <p>Menunggu Persetujuan: **<?php echo $pendingJurnals; ?>**</p>
                    <p>Permintaan Tertunda: **<?php echo $pendingRequests; ?>**</p>
                </div>
            </div>
        </div>
        <!-- End Main Content -->
    </div>
</body>
</html>
