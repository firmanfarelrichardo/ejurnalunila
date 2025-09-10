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
$adminData = null;

// Ambil data admin berdasarkan ID dari URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT id, nip, nama, email FROM users WHERE id = ? AND role = 'admin'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $adminData = $result->fetch_assoc();
    $stmt->close();

    if (!$adminData) {
        $message = "<div class='error-message'>Data admin tidak ditemukan.</div>";
    }
}

// Logika untuk mengupdate data admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
    $id = $_POST['id'];
    $nip = $_POST['nip'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Update data tanpa password jika password kosong
    if (empty($password)) {
        $stmt = $conn->prepare("UPDATE users SET nip = ?, nama = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nip, $nama, $email, $id);
    } else {
        // Update data dengan password baru yang di-hash
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET nip = ?, nama = ?, email = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nip, $nama, $email, $hashedPassword, $id);
    }
    
    if ($stmt->execute()) {
        $message = "<div class='success-message'>Admin berhasil diperbarui! <a href='manage_admin.php'>Kembali ke daftar admin</a></div>";
        // Perbarui data admin yang ditampilkan di formulir
        $adminData['nip'] = $nip;
        $adminData['nama'] = $nama;
        $adminData['email'] = $email;
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
    <title>Edit Admin - Superadmin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
<style>
   
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

.form-container label {
  display: block;
            margin-top: 15px;
            font-weight: bold;
}

/* ===== Input ===== */
.form-container input {
width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
}

.form-container input:focus {
  border-color: #4facfe;
  box-shadow: 0 0 6px rgba(79, 172, 254, 0.5);
}

/* ===== Tombol ===== */
.form-container button {
 margin-top: 20px;
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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
        <div class="main-content">
            <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header">
                <h1>Edit Akun Admin</h1>
                <div class="user-profile">
                    <span>Role: Superadmin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <div class="card form-container">
                    <?php echo $message; ?>
                    <?php if ($adminData): ?>
                        <form method="POST" class="form-edit-user">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($adminData['id']); ?>">
                            <div class="form-group">
                                <label for="nip">NIP</label>
                                <input type="text" id="nip" name="nip" value="<?php echo htmlspecialchars($adminData['nip']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="nama">Nama Lengkap</label>
                                <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($adminData['nama']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($adminData['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password (kosongkan jika tidak ingin diubah)</label>
                                <input type="password" id="password" name="password" placeholder="Masukkan password baru">
                            </div>
                            <button type="submit" name="update_admin" class="update-btn-form">Update Admin</button>
                        </form>
                    <?php else: ?>
                        <p>Data admin tidak ditemukan. Silakan kembali ke <a href="manage_admin.php">daftar admin</a>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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