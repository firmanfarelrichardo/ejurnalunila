<?php
session_start();

// Pengaturan Database
require_once '../database/config.php';
$conn = connect_to_database();

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT id, nama, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user_data = $result->fetch_assoc();
    if (password_verify($password, $user_data['password'])) {
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['user_name'] = $user_data['nama'];
        $_SESSION['user_role'] = $user_data['role'];

        if ($user_data['role'] === 'superadmin') {
            header("Location: ../superadmin/dashboard_superadmin.php");
        } else if ($user_data['role'] === 'admin') {
            header("Location: ../admin/dashboard_admin.php");
        } else if ($user_data['role'] === 'pengelola') {
            header("Location: ../pengelola/dashboard_pengelola.php");
        } else {
            header("Location: ../index.php");
        }
        exit(); 
    }
}
echo "<h1>Login Gagal</h1>";
echo "<p>Email atau password yang Anda masukkan salah.</p>";
echo "<a href='../admin/login.php'>Coba Lagi</a>";

$stmt->close();
$conn->close();
?>