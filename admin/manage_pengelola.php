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

// Ambil daftar pengelola
$pengelolas = [];
$result = $conn->query("SELECT id, nip, nama, email FROM users WHERE role = 'pengelola'");
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
    <title>Kelola Pengelola - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

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
        }
        .data-table th {
            background-color: #34495e;
            color: white;
        }
        .data-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

         .action-buttons form {
            display: inline-block;
        }
        .action-buttons button {
        background-color: #e74c3c;
        display: inline-block;
        padding: 8px 12px;
        border-radius: 4px;
        color: #fff;
        text-decoration: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        }
        .action-buttons button:hover {
            background-color: #c0392b;
            transform: translateY(-2px); /* efek naik */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); /* shadow elegan */
        }
        .add-btn {
            background-color: #2ecc71;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .add-btn:hover {
            background-color: #27ae60;
        }
        .card .header-with-btn {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .edit-btn {
        display: inline-block;
        padding: 8px 12px;
        border-radius: 4px;
        background-color: #4CAF50; /* hijau elegan */
        color: #fff;
        text-decoration: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        }

        .edit-btn:hover {
        background-color: #45a049; /* warna lebih gelap saat hover */
        transform: translateY(-2px); /* efek naik */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); /* shadow elegan */
        }

        .edit-btn:active {
        transform: translateY(0); /* kembali normal saat ditekan */
        box-shadow: none;
        }
</style>

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
                <li><a href="manage_pengelola.php" class="active"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        <!-- End Sidebar -->

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Kelola Akun Pengelola</h1>
                <div class="user-profile">
                    <span>Role: Admin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <?php echo $message; ?>
                <div class="card">
                    <div class="header-with-btn">
                        <h3>Daftar Pengelola</h3>
                        <a href="add_pengelola.php" class="add-btn">
                            <i class="fas fa-plus"></i> Tambah Pengelola Baru
                        </a>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pengelolas as $pengelola): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pengelola['nip']); ?></td>
                                    <td><?php echo htmlspecialchars($pengelola['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($pengelola['email']); ?></td>
                                    <td class="action-buttons">
                                        <a href="edit_pengelola.php?id=<?php echo htmlspecialchars($pengelola['id']); ?>" class="edit-btn">Edit</a>
                                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengelola ini?');">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($pengelola['id']); ?>">
                                            <button type="submit" name="delete_pengelola">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- End Main Content -->
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
