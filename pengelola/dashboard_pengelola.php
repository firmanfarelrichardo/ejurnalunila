<?php
// Mulai atau lanjutkan sesi
session_start();

// Periksa apakah pengguna sudah login dan memiliki peran pengelola
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pengelola') {
    header("Location: login.php");
    exit();
}

// Konfigurasi database MySQL
require_once '../database/config.php';
$conn = connect_to_database();

// Ambil data statistik untuk dashboard pengelola
$pengelola_id = $_SESSION['user_id'];

// Mengambil data statistik untuk dashboard pengelola dari tabel jurnal_sumber
// 1. Total submissions yang dibuat oleh pengelola
$stmt = $conn->prepare("SELECT COUNT(*) FROM jurnal_sumber WHERE pengelola_id = ?");
$stmt->bind_param("i", $pengelola_id);
$stmt->execute();
$stmt->bind_result($totalSubmissions);
$stmt->fetch();
$stmt->close();

// 2. Total submissions yang masih pending
$stmt = $conn->prepare("SELECT COUNT(*) FROM jurnal_sumber WHERE pengelola_id = ? AND status = 'pending'");
$stmt->bind_param("i", $pengelola_id);
$stmt->execute();
$stmt->bind_result($pendingSubmissions);
$stmt->fetch();
$stmt->close();

// 3. Total submissions yang sudah disetujui
$stmt = $conn->prepare("SELECT COUNT(*) FROM jurnal_sumber WHERE pengelola_id = ? AND status = 'selesai'");
$stmt->bind_param("i", $pengelola_id);
$stmt->execute();
$stmt->bind_result($approvedSubmissions);
$stmt->fetch();
$stmt->close();

// Query ini tidak diperlukan karena sudah ada di daftar_jurnal.php, tapi tidak masalah jika ada.
$sql = "SELECT id, judul_jurnal, created_at, status FROM jurnal_sumber WHERE pengelola_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $pengelola_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    echo "Error: Gagal menyiapkan statement SQL.";
    $result = false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengelola</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php
        // --- Blok PHP untuk Menampilkan Notifikasi ---
        if (isset($_SESSION['success_message'])) {
            echo '<div class="notification success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="notification error">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>
        <div class="sidebar" id="sidebar">
            <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <h2>Pengelola</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_pengelola.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="tambah_jurnal.php"><i class="fas fa-plus-circle"></i> <span>Daftar Jurnal Baru</span></a></li>
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
                <div class="dashboard-stats">
                    <div class="card">
                        <h3>Total Submissions</h3>
                        <p><?php echo $totalSubmissions; ?></p>
                    </div>
                    <div class="card">
                        <h3>Menunggu Persetujuan</h3>
                        <p><?php echo $pendingSubmissions; ?></p>
                    </div>
                    <div class="card">
                        <h3>Jurnal Disetujui</h3>
                        <p><?php echo $approvedSubmissions; ?></p>
                    </div>
                </div>
            </div>
        </div>
        </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>