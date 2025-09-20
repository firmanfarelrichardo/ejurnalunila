<?php
// Memulai session, penting jika nanti kita ingin menambahkan pesan notifikasi di sini.
session_start();

// Periksa apakah pengguna sudah login dan memiliki peran pengelola
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pengelola') {
    header("Location: login.php");
    exit();
}

// Konfigurasi database MySQL
require_once '../database/config.php';
$conn = connect_to_database();

// Ambil ID pengelola dari session
$pengelola_id = $_SESSION['user_id'];

// Menyiapkan query untuk mengambil data jurnal milik pengelola yang sedang login.
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
    <title>Daftar Jurnal Saya</title>
    
    <link rel="stylesheet" href="admin_style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<style>

    /* =============================================== */
/* === Gaya untuk Panel Konten dan Tabel Data === */
/* =============================================== */

/* Panel putih untuk membungkus konten seperti tabel */
.content-panel {
    background-color: #ffffff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
    flex-wrap: wrap;
    gap: 15px;
}

.panel-header h1 {
    margin: 0;
    font-size: 22px;
}

/* Tombol Aksi Utama */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.3s, transform 0.2s;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-primary {
    background-color: #3498db;
    color: white;
}

.btn-primary:hover {
    background-color: #2980b9;
}

/* Wrapper untuk tabel agar responsif */
.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: middle;
}

thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #34495e;
}

tbody tr:hover {
    background-color: #f1f1f1;
}

/* Badge Status */
.status {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 500;
    color: white;
    text-transform: uppercase;
    white-space: nowrap;
}

.status-pending { background-color: #f39c12; }
.status-approved { background-color: #2ecc71; }
.status-rejected { background-color: #e74c3c; }
.status-needs-edit { background-color: #3498db; }

/* Link Aksi dalam Tabel */
.action-links a {
    color: #3498db;
    text-decoration: none;
    margin-right: 15px;
    font-weight: 500;
    white-space: nowrap;
}

.action-links a:hover {
    text-decoration: underline;
    color: #2980b9;
}

/* Notifikasi */
.notification { 
    padding: 15px; 
    margin-bottom: 20px; 
    border-radius: 5px; 
    color: white; 
    font-size: 14px; 
    text-align: center; 
}
.success { background-color: #2ecc71; }
.error { background-color: #e74c3c; }

</style>


<body>

    <div class="dashboard-container">

        <div class="sidebar" id="sidebar">
            <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <h2>Pengelola</h2>
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
                <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
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
                
                <div class="content-panel">
                    <div class="panel-header">
                        <h1>Daftar Jurnal Saya</h1>
                        <a href="tambah_jurnal.php" class="btn btn-primary">Tambah Jurnal Baru</a>
                    </div>

                    <div class="table-wrapper">
                        <table>
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
                                            $status_class = 'status-pending'; // Default
                                            if ($status == 'approved') $status_class = 'status-approved';
                                            if ($status == 'rejected') $status_class = 'status-rejected';
                                            if ($status == 'needs_edit') $status_class = 'status-needs-edit';
                                            $status_text = ucwords(str_replace('_', ' ', $status));
                                            echo '<span class="status ' . $status_class . '">' . htmlspecialchars($status_text) . '</span>';
                                            ?>
                                        </td>
                                        <td class="action-links">
                                            <a href="detail_jurnal.php?id=<?php echo $row['id']; ?>">Detail</a>
                                            <a href="ajukan_perubahan.php?id=<?php echo $row['id']; ?>">Ajukan Perubahan</a>
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

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
<?php
// Menutup statement dan koneksi
if ($stmt) {
    mysqli_stmt_close($stmt);
}
mysqli_close($conn);
?>