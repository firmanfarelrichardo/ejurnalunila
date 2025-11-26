<?php
// Mulai atau lanjutkan sesi
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

// Konfigurasi database MySQL
require_once '../database/config.php';
$conn = connect_to_database();

// Logika untuk menghapus pengelola
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_pengelola'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'pengelola'");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "<div class='success-message'>Pengelola berhasil dihapus!</div>";
    } else {
        $message = "<div class='error-message'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Logika untuk penyortiran (filtering)
$sort_option = $_GET['sort'] ?? 'created_at_desc'; // Default: terbaru dulu

$allowed_sorts = [
    'created_at_desc' => 'created_at DESC',
    'created_at_asc'  => 'created_at ASC',
    'nama_asc'        => 'nama ASC',
    'nama_desc'       => 'nama DESC',
];

if (!array_key_exists($sort_option, $allowed_sorts)) {
    $sort_option = 'created_at_desc';
}

$order_by_clause = $allowed_sorts[$sort_option];

// (DIPERBARUI) Ambil daftar pengelola, tambahkan kolom 'created_at'
$pengelolas = [];
$sql = "SELECT id, nama, email, created_at FROM users WHERE role = 'pengelola' ORDER BY {$order_by_clause}";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pengelolas[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengelola - Superadmin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            vertical-align: middle; /* (BARU) Agar tombol sejajar rapi */
        }
        .data-table th {
            background-color: #34495e;
            color: white;
        }
        .data-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .action-buttons form {
            display: inline-block;
            margin: 0;
        }
        .action-buttons .btn, .edit-btn, .action-buttons button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
        }
        .action-buttons button { background-color: #e74c3c; }
        .action-buttons button:hover { background-color: #c0392b; transform: translateY(-2px); }
        .edit-btn { background-color: #3498db; }
        .edit-btn:hover { background-color: #2980b9; transform: translateY(-2px); }
        
        .card .header-with-btn {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-controls {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .filter-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-container label {
            font-weight: 500;
            font-size: 14px;
        }
        .filter-container select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .header-action-buttons { display: flex; gap: 10px; }
        .header-action-buttons .btn {
            padding: 10px 15px; text-decoration: none; border-radius: 5px; color: white;
            display: inline-flex; align-items: center; gap: 8px; font-weight: 500;
        }
        .btn-add { background-color: #2ecc71; }
        .btn-add:hover { background-color: #27ae60; }
        .btn-import { background-color: #3498db; }
        .btn-import:hover { background-color: #2980b9; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="sidebar-toggle-btn" id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <div class="logo"><img src="../Images/logo-header-2024-normal.png" alt="Logo"></div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_superadmin.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pengelola.php" class="active"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_admin.php"><i class="fas fa-user-shield"></i> <span>Kelola Admin</span></a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
                <li><a href="change_password.php"><i class="fas fa-lock"></i> <span>Ganti Password</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Kelola Akun Pengelola</h1>
                <div class="user-profile"><span>Role: Superadmin</span><a href="../api/logout.php">Logout</a></div>
            </div>

            <div class="content-area">
                <?php echo $message; ?>
                <div class="card">
                    <div class="header-with-btn">
                        <h3>Daftar Pengelola</h3>
                        <div class="header-controls">
                            <div class="filter-container">
                                <form method="GET" id="filterForm">
                                    <label for="sort">Urutkan:</label>
                                    <select name="sort" id="sort" onchange="this.form.submit()">
                                        <option value="created_at_desc" <?php echo ($sort_option == 'created_at_desc') ? 'selected' : ''; ?>>Terbaru</option>
                                        <option value="created_at_asc" <?php echo ($sort_option == 'created_at_asc') ? 'selected' : ''; ?>>Terlama</option>
                                        <option value="nama_asc" <?php echo ($sort_option == 'nama_asc') ? 'selected' : ''; ?>>Nama (A-Z)</option>
                                        <option value="nama_desc" <?php echo ($sort_option == 'nama_desc') ? 'selected' : ''; ?>>Nama (Z-A)</option>
                                    </select>
                                </form>
                            </div>
                            <div class="header-action-buttons">
                                <a href="import_pengelola.php" class="btn btn-import"><i class="fas fa-file-csv"></i> Impor</a>
                                <a href="add_pengelola.php" class="btn btn-add"><i class="fas fa-plus"></i> Tambah</a>
                            </div>
                        </div>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Waktu Ditambahkan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pengelolas)): ?>
                                <?php foreach ($pengelolas as $pengelola): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($pengelola['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($pengelola['email']); ?></td>
                                        <td><?php echo date('d M Y, H:i', strtotime($pengelola['created_at'])); ?></td>
                                        <td class="action-buttons">
                                            <a href="edit_pengelola.php?id=<?php echo htmlspecialchars($pengelola['id']); ?>" class="edit-btn">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengelola ini?');">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($pengelola['id']); ?>">
                                                <button type="submit" name="delete_pengelola">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">Tidak ada data pengelola yang ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
<?php
$conn->close();
?>