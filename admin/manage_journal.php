<?php
// Mulai atau lanjutkan sesi
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Konfigurasi database MySQL
require_once '../database/config.php';
$conn = connect_to_database();

// Inisialisasi pesan
$message = '';

// Handle aksi dari formulir untuk update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $jurnalId = $_POST['jurnal_id'];
    $newStatus = $_POST['status'];
    
    // Validasi status untuk keamanan
    $allowed_statuses = ['pending', 'selesai', 'ditolak', 'butuh_edit'];
    if (in_array($newStatus, $allowed_statuses)) {
        // Query untuk memperbarui status di tabel jurnal_sumber
        $stmt = $conn->prepare("UPDATE jurnal_sumber SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $jurnalId);
        if ($stmt->execute()) {
            $message = "<div class='success-message'>Status jurnal berhasil diperbarui!</div>";
        } else {
            $message = "<div class='error-message'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='error-message'>Status tidak valid.</div>";
    }
}

// Ambil daftar jurnal dari database jurnal_sumber
$jurnals = [];
$sql = "SELECT js.id, js.judul_jurnal, u.nama AS pengelola_nama, u.nip AS pengelola_nip, js.status, js.created_at
        FROM jurnal_sumber js 
        LEFT JOIN users u ON js.pengelola_id = u.id
        ORDER BY js.created_at DESC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $jurnals[] = $row;
    }
}

// Fungsi untuk mendapatkan kelas CSS berdasarkan status
function getStatusClass($status) {
    switch ($status) {
        case 'selesai':
            return 'status-approved';
        case 'ditolak':
            return 'status-rejected';
        case 'butuh_edit':
            return 'status-needs_edit';
        case 'pending':
        default:
            return 'status-pending';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jurnal - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
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
                    <img src="../images/logo-header-2024-normal.png" alt="Logo Universitas Lampung">
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php" ><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_journal.php" class="active"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="header">
                <h1>Kelola Jurnal Submissions</h1>
                <div class="user-profile">
                    <span>Role: Admin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <?php echo $message; ?>
                <div class="card">
                    <h3>Daftar Jurnal</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Judul Jurnal</th>
                                <th>NIP</th>
                                <th>Nama Pengelola</th>
                                <th>Tanggal Submit</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $nomor = 1; 
                                foreach ($jurnals as $jurnal): 
                            ?>
                                <tr>
                                    <td><?php echo $nomor; ?></td>
                                    <td><?php echo htmlspecialchars($jurnal['judul_jurnal']); ?></td>
                                    <td><?php echo htmlspecialchars($jurnal['pengelola_nip']); ?></td>
                                    <td><?php echo htmlspecialchars($jurnal['pengelola_nama']); ?></td>
                                    <td><?php echo htmlspecialchars($jurnal['created_at']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" class="status-form">
                                                <input type="hidden" name="jurnal_id" value="<?php echo htmlspecialchars($jurnal['id']); ?>">
                                                <select name="status" class="status-select <?php echo getStatusClass($jurnal['status']); ?>" onchange="this.className='status-select ' + this.options[this.selectedIndex].dataset.class">
                                                    <option value="pending" data-class="status-pending" <?php echo $jurnal['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="selesai" data-class="status-approved" <?php echo $jurnal['status'] == 'selesai' ? 'selected' : ''; ?>>Disetujui</option>
                                                    <option value="ditolak" data-class="status-rejected" <?php echo $jurnal['status'] == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                                    <option value="butuh_edit" data-class="status-needs_edit" <?php echo $jurnal['status'] == 'butuh_edit' ? 'selected' : ''; ?>>Butuh Edit</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-update"><i class="fas fa-save"></i></button>
                                            </form>
                                            <a href="tinjau_jurnal.php?id=<?php echo htmlspecialchars($jurnal['id']); ?>" class="btn btn-icon btn-edit" title="Lihat & Tinjau Detail">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                $nomor++; 
                                endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
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
<?php
$conn->close();
?>