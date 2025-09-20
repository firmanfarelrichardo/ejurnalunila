<?php
// Mulai atau lanjutkan sesi
session_start();

// Periksa apakah pengguna sudah login dan memiliki peran superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

// Konfigurasi database MySQL
require_once '../database/config.php';
$conn = connect_to_database();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $userId = $_SESSION['user_id'];

    // Ambil password saat ini dari database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Verifikasi password saat ini dan password baru
    if ($user && password_verify($currentPassword, $user['password'])) {
        if ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);
            if ($stmt->execute()) {
                $message = "<div class='success-message'>Password berhasil diubah.</div>";
            } else {
                $message = "<div class='error-message'>Terjadi kesalahan saat memperbarui password.</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='error-message'>Password baru tidak cocok.</div>";
        }
    } else {
        $message = "<div class='error-message'>Password saat ini salah.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - Superadmin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>

        .form-ganti-password {
            box-sizing: border-box;
            background: #fff;
            padding: 30px 25px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            animation: fadeIn 0.5s ease-in-out;
        }
        .form-ganti-password h3 {
            margin-top: 0;
            color: #3498db;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .form-ganti-password label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        .form-ganti-password input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-ganti-password button {
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-ganti-password button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>Superadmin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_superadmin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_admin.php"><i class="fas fa-user-shield"></i> Kelola Admin</a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> Kelola Pengelola</a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> Kelola Jurnal</a></li>
                <li><a href="change_password.php" class="active"><i class="fas fa-key"></i> Ganti Password</a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <!-- End Sidebar -->

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Ganti Password</h1>
                <div class="user-profile">
                    <span>Role: Superadmin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <div class="form-ganti-password">
                    <?php echo $message; ?>
                    <form method="POST">
                        <label for="current_password">Password Saat Ini:</label>
                        <input type="password" name="current_password" required>

                        <label for="new_password">Password Baru:</label>
                        <input type="password" name="new_password" required>

                        <label for="confirm_password">Konfirmasi Password Baru:</label>
                        <input type="password" name="confirm_password" required>

                        <button type="submit">Ganti Password</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- End Main Content -->
    </div>
</body>
</html>
<?php
$conn->close();
?>
