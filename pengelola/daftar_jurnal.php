<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pengelola') {
    header("Location: login.php");
    exit();
}


// Konfigurasi database MySQL
require_once '../database/config.php';
$conn = connect_to_database();

$pengelola_id = $_SESSION['user_id'];

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
    <title>Daftar & Status Jurnal</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="sidebar-toggle-btn" id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                    <img src="../assets/unila_logo.png" alt="Logo Universitas Lampung">
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_pengelola.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="tambah_jurnal.php"><i class="fas fa-plus-circle"></i> <span>Daftar Jurnal Baru</span></a></li>
                <li><a href="daftar_jurnal.php" class="active"><i class="fas fa-list-alt"></i> <span>Daftar & Status Jurnal</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Daftar & Status Jurnal</h1>
                <div class="user-profile">
                    <span>Role: Pengelola</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

             <div class="content-area">
                <?php
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="notification success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="notification error">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                    unset($_SESSION['error_message']);
                }
                ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Jurnal yang Anda Kelola</h3>
                        <a href="tambah_jurnal.php" class="btn-primary"><i class="fas fa-plus"></i> Tambah Jurnal</a>
                    </div>

                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Judul Jurnal</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && mysqli_num_rows($result) > 0):
                                    $nomor = 1;
                                    while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $nomor++; ?></td>
                                        <td><?php echo htmlspecialchars($row['judul_jurnal']); ?></td>
                                        <td><?php echo date('d F Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <?php
                                            $status = $row['status'];
                                            // Menggunakan kelas status badge yang sudah ada di style.css
                                            echo '<span class="status-badge status-' . str_replace('_', '-', $status) . '">' . htmlspecialchars(ucwords(str_replace('_', ' ', $status))) . '</span>';
                                            ?>
                                        </td>
                                        <td class="action-buttons-group">
                                            <a href="detail_jurnal.php?id=<?php echo $row['id']; ?>" class="action-btn-view" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="ajukan_perubahan.php?id=<?php echo $row['id']; ?>" class="action-btn-edit" title="Ajukan Perubahan">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile;
                                else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">Anda belum mendaftarkan jurnal apapun.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
<?php
if ($stmt) {
    mysqli_stmt_close($stmt);
}
mysqli_close($conn);
?>