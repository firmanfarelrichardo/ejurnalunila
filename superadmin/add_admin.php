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

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $nip = $_POST['nip'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'admin';

    $stmt = $conn->prepare("INSERT INTO users (nip, nama, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nip, $nama, $email, $password, $role);
    if ($stmt->execute()) {
        $message = "<div class='success-message'>Admin berhasil ditambahkan! <a href='manage_admin.php'>Kembali ke daftar admin</a></div>";
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
    <title>Tambah Admin Baru</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<style>
       /* ===== Reset dasar ===== */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

/* ===== Area utama ===== */
.content-area {
display: flex;
  justify-content: center;   /* bikin ke tengah horizontal */
  align-items: flex-start; 
  background: #f7fafd; /* biar lembut dan konsisten */
}

/* ===== Card form ===== */
.form-container.card {
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
  padding: 12px 14px;
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
                <h1>Tambah Admin Baru</h1>
                <div class="user-profile">
                    <span>Role: Superadmin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <div class="card form-container">
                    <?php echo $message; ?>
                    <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                        <div class="form-group">
                            <label for="nip">NIP</label>
                            <input type="text" id="nip" name="nip" placeholder="Masukkan NIP" required>
                        </div>
                        <div class="form-group">
                            <label for="nama">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama" placeholder="Masukkan Nama Lengkap" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="Masukkan Email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Masukkan Password" required>
                        </div>
                        <button type="submit" name="add_admin" class="add-btn-form">Tambah Admin</button>
                    </form>
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
