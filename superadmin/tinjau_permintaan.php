<?php
// admin/tinjau_permintaan.php
session_start();

// Cek sesi admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

// Koneksi database
require_once '../database/config.php';
$conn = connect_to_database();

// (DIPERBARUI) Logika untuk merespons permintaan
// Logika penghapusan jurnal dihilangkan untuk menjaga riwayat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $new_status = $_POST['status'];
    
    $allowed_statuses = ['approved', 'rejected', 'pending'];
    if (in_array($new_status, $allowed_statuses)) {
        $stmt_update = $conn->prepare("UPDATE submission_requests SET status = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_status, $request_id);
        if ($stmt_update->execute()) {
            $_SESSION['success_message'] = "Status permintaan telah berhasil diperbarui.";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui status permintaan.";
        }
        $stmt_update->close();
    } else {
        $_SESSION['error_message'] = "Status tidak valid.";
    }
    
    // Kembali ke halaman dengan filter yang sedang aktif
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit();
}

// (BARU) Logika untuk filter status
$filter_status = $_GET['status'] ?? 'pending'; // Default filter adalah 'pending'
$allowed_filters = ['pending', 'approved', 'rejected', 'semua'];
if (!in_array($filter_status, $allowed_filters)) {
    $filter_status = 'pending'; // Kembali ke default jika filter tidak valid
}

// Menyesuaikan query SQL dengan filter yang dipilih
$sql_select = "SELECT sr.id, sr.jurnal_id, sr.request_type, sr.status, sr.created_at, j.judul_jurnal, u.nama AS nama_pengelola
               FROM submission_requests sr
               LEFT JOIN jurnal_sumber j ON sr.jurnal_id = j.id
               LEFT JOIN users u ON sr.pengelola_id = u.id";

if ($filter_status != 'semua') {
    $sql_select .= " WHERE sr.status = ?";
}
$sql_select .= " ORDER BY sr.created_at DESC";

$stmt = $conn->prepare($sql_select);

if ($filter_status != 'semua') {
    $stmt->bind_param("s", $filter_status);
}

$stmt->execute();
$result = $stmt->get_result();

$requests = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tinjau Permintaan - Admin</title>
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
                    <img src="../Images/logo-header-2024-normal.png" alt="Logo Universitas Lampung">
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_superadmin.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_admin.php" ><i class="fas fa-user-shield"></i> <span>Kelola Admin</span></a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"  class="active"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
                <li><a href="change_password.php"><i class="fas fa-lock"></i> <span>Ganti Password</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Tinjau Permintaan</h1>
                <div class="user-profile">
                    <span>Role: Superadmin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <?php
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                    unset($_SESSION['error_message']);
                }
                ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Daftar Permintaan Masuk</h3>
                        <div class="filter-container">
                            <form method="GET" id="filterForm">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Tampilkan Pending</option>
                                    <option value="approved" <?php echo ($filter_status == 'approved') ? 'selected' : ''; ?>>Tampilkan Approved</option>
                                    <option value="rejected" <?php echo ($filter_status == 'rejected') ? 'selected' : ''; ?>>Tampilkan Rejected</option>
                                    <option value="semua" <?php echo ($filter_status == 'semua') ? 'selected' : ''; ?>>Tampilkan Semua</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Judul Jurnal</th>
                                    <th>Jenis</th>
                                    <th>Pengelola</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($requests)): ?>
                                    <?php 
                                        $nomor = 1; 
                                        foreach ($requests as $request): 
                                    ?>
                                    <tr>
                                        <td><?php echo $nomor; ?></td>
                                        <td><?php echo htmlspecialchars($request['judul_jurnal'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($request['request_type'])); ?></td>
                                        <td><?php echo htmlspecialchars($request['nama_pengelola'] ?: '-'); ?></td>
                                        <td><?php echo date('d M Y, H:i:s', strtotime($request['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" class="status-update-form">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <input type="hidden" name="jurnal_id" value="<?php echo $request['jurnal_id']; ?>">
                                                <input type="hidden" name="request_type" value="<?php echo $request['request_type']; ?>">
                                                <select name="status" class="status-select <?php echo 'status-'.strtolower($request['status']); ?>" onchange="this.className='status-select status-' + this.value">
                                                    <option value="pending" <?php echo $request['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="approved" <?php echo $request['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="rejected" <?php echo $request['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                                <button type="submit" name="action" value="update_status" class="action-btn-save" title="Simpan Status">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="action-buttons-group">
                                            <a href="detail_permintaan.php?id=<?php echo htmlspecialchars($request['id']); ?>" class="action-btn-view" title="Lihat Informasi Permintaan">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="tinjau_jurnal.php?id=<?php echo htmlspecialchars($request['jurnal_id']); ?>" class="action-btn-edit" title="Edit Jurnal Terkait">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php 
                                        $nomor++; 
                                        endforeach; 
                                    ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">Tidak ada permintaan dengan status "<?php echo ucfirst($filter_status); ?>".</td>
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
<?php mysqli_close($conn); ?>