<?php
// Mulai atau lanjutkan sesi
session_start();

// Periksa apakah pengguna sudah login dan memiliki peran superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

// Konfigurasi database MySQL
$host = "localhost";
$user = "root";
$pass = "";
$db = "oai";
$conn = new mysqli($host, $user, $pass, $db);

// Periksa koneksi
if ($conn->connect_error) { 
    die("Koneksi gagal: " . $conn->connect_error); 
}

// Logika untuk menghapus admin
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "<div class='success-message'>Admin berhasil dihapus!</div>";
    } else {
        $message = "<div class='error-message'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Ambil daftar admin
$admins = [];
$result = $conn->query("SELECT id, nip, nama, email FROM users WHERE role = 'admin'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Admin - Superadmin</title>
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
        padding: 8px 16px;
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
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <h2>Superadmin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_superadmin.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_admin.php" class="active"><i class="fas fa-user-shield"></i> <span>Kelola Admin</span></a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="change_password.php"><i class="fas fa-key"></i> <span>Ganti Password</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        <!-- End Sidebar -->

        <!-- Main Content -->
        <div class="main-content">
            <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header">
                <h1>Kelola Akun Admin</h1>
                <div class="user-profile">
                    <span>Role: Superadmin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <?php echo $message; ?>
                <div class="card">
                    <div class="header-with-btn">
                        <h3>Daftar Admin</h3>
                        <a href="add_admin.php" class="add-btn">
                            <i class="fas fa-plus"></i> Tambah Admin Baru
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
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['nip']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td class="action-buttons">
                                         <a href="edit_admin.php?id=<?php echo htmlspecialchars($admin['id']); ?>" class="edit-btn">Edit</a>
                                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus admin ini?');">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($admin['id']); ?>">
                                            <button type="submit" name="delete_admin">Hapus</button>
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
