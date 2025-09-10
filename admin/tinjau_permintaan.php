<?php
// admin/tinjau_permintaan.php
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

// Logika untuk menghapus request
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $jurnal_id = (int)$_POST['jurnal_id'];
    $request_type = $_POST['request_type'];
    $action = $_POST['action']; // 'approve' atau 'reject'

    $new_status = ($action == 'approve') ? 'approved' : 'rejected';

    // Langkah 1: Update status di tabel submission_requests
    $sql_update_request = "UPDATE submission_requests SET status = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update_request);
    mysqli_stmt_bind_param($stmt_update, "si", $new_status, $request_id);
    
    if (mysqli_stmt_execute($stmt_update)) {
        // Langkah 2: Jika permintaan HAPUS disetujui, hapus juga data jurnalnya.
        if ($action == 'approve' && $request_type == 'delete') {
            $sql_delete_jurnal = "DELETE FROM jurnal_sumber WHERE id = ?";
            $stmt_delete = mysqli_prepare($conn, $sql_delete_jurnal);
            mysqli_stmt_bind_param($stmt_delete, "i", $jurnal_id);
            if(mysqli_stmt_execute($stmt_delete)) {
                 $_SESSION['success_message'] = "Permintaan penghapusan disetujui dan jurnal telah dihapus.";
            } else {
                 $_SESSION['error_message'] = "Gagal menghapus jurnal, namun status permintaan sudah diupdate.";
            }
            mysqli_stmt_close($stmt_delete);
        } else {
            $_SESSION['success_message'] = "Permintaan telah berhasil direspons.";
        }
    } else {
        $_SESSION['error_message'] = "Gagal merespons permintaan.";
    }
    mysqli_stmt_close($stmt_update);
    
    header("Location: tinjau_permintaan.php");
    exit();
}

// Ambil daftar semua permintaan
$requests = [];
$sql_select = "SELECT sr.id, sr.request_type, sr.status, sr.created_at, j.judul_jurnal, u.nama AS nama_pengelola
               FROM submission_requests sr
               LEFT JOIN jurnal_sumber j ON sr.jurnal_id = j.id
               LEFT JOIN users u ON sr.pengelola_id = u.id
               ORDER BY sr.created_at DESC";
$result = $conn->query($sql_select);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Permintaan - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<style>
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
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 12px;
        font-weight: bold;
        color: white;
        text-transform: uppercase;
    }
    .status-pending { background-color: #f39c12; }
    .status-approved { background-color: #2ecc71; }
    .status-rejected { background-color: #e74c3c; }
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
</style>
<body>
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <h2>Admin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> Kelola Pengelola</a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php" class="active"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="header">
                <h1>Tinjau Permintaan Perubahan & Penghapusan</h1>
                <div class="user-profile">
                    <span>Role: Admin</span>
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
                        <h1>Daftar Permintaan Masuk</h1>
                    </div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Judul Jurnal</th>
                                    <th>Jenis Permintaan</th>
                                    <th>Pengelola</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($requests)): ?>
                                    <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['id']); ?></td>
                                        <td><?php echo htmlspecialchars($request['judul_jurnal'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($request['request_type'])); ?></td>
                                        <td><?php echo htmlspecialchars($request['nama_pengelola'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($request['created_at']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($request['status']); ?>"><?php echo htmlspecialchars(ucfirst($request['status'])); ?></span>
                                        </td>
                                        <td class="action-links">
                                            <a href="detail_permintaan.php?id=<?php echo htmlspecialchars($request['id']); ?>">Tinjau Detail</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">Tidak ada permintaan yang masuk.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>