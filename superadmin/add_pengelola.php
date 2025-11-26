<?php
// Mulai atau lanjutkan sesi
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

require_once '../database/config.php';
$conn = connect_to_database();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pengelola'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'pengelola';

    $stmt = $conn->prepare("INSERT INTO users ( nama, email, password, role) VALUES ( ?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama, $email, $password, $role);
    if ($stmt->execute()) {
        $message = "<div class='success-message'>Pengelola berhasil ditambahkan! <a href='manage_pengelola.php'>Kembali ke daftar pengelola</a></div>";
    } else {
        $message = "<div class='error-message'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengelola Baru</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
       /* ===== Reset dasar ===== */

/* ===== Area utama ===== */
.content-area {
  justify-content: center;   /* bikin ke tengah horizontal */
  align-items: flex-start; 
  background: #f7fafd; /* biar lembut dan konsisten */
}

/* ===== Card form ===== */
.form-container.card {
  box-sizing: border-box;
  background: #fff;
  padding: 30px 25px;
  border-radius: 15px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  width: 100%;
  animation: fadeIn 0.5s ease-in-out;
}

/* Animasi masuk */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ===== Label ===== */
.form-container label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 6px;
  color: #333;
}

/* ===== Input ===== */
.form-container input {
  width: 100%;
  padding: 12px 0px 12px 12px;
  margin-bottom: 18px;
  border: 1px solid #ccc;
  border-radius: 8px;
  outline: none;
  transition: all 0.3s ease;
  font-size: 14px;
}

.form-container input:focus {
  border-color: #4facfe;
  box-shadow: 0 0 6px rgba(79, 172, 254, 0.5);
}

/* ===== Tombol ===== */
.form-container button {
  width: 100%;
  padding: 12px;
  background: linear-gradient(135deg, #4facfe, #00f2fe);
  border: none;
  border-radius: 8px;
  color: white;
  font-size: 15px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
}

.form-container button:hover {
  background: linear-gradient(135deg, #00c6ff, #0072ff);
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
}

    </style>
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
                <li><a href="manage_pengelola.php" class="active"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_admin.php" ><i class="fas fa-user-shield"></i> <span>Kelola Admin</span></a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
                <li><a href="change_password.php"><i class="fas fa-lock"></i> <span>Ganti Password</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        <!-- End Sidebar -->

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Tambah Pengelola Baru</h1>
                <div class="user-profile">
                    <span>Role: Superadmin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <div class="form-container card">
                    <?php echo $message; ?>
                    <form method="POST">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" name="nama" placeholder="Nama Lengkap" required>
                        <label for="email">Email</label>
                        <input type="email" name="email" placeholder="Email" required>
                        <label for="password">Password</label>
                        <input type="password" name="password" placeholder="Password" required>
                        <button type="submit" name="add_pengelola">Tambah Pengelola</button>
                    </form>
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
