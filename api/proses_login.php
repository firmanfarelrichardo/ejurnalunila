<?php
// Wajib ada di awal untuk memulai atau melanjutkan session
session_start();

// Pengaturan Database
require_once '../database/config.php';
$conn = connect_to_database();

$email = $_POST['email'];
$password = $_POST['password'];

// PERBAIKAN: Tambahkan 'id' ke dalam query SELECT
$stmt = $conn->prepare("SELECT id, nip, nama, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user_data = $result->fetch_assoc();

    // Verifikasi password yang di-hash
    if (password_verify($password, $user_data['password'])) {
        // Password cocok, simpan data ke session
        // PERBAIKAN: Simpan 'id' asli pengguna, bukan 'nip'
        $_SESSION['user_id'] = $user_data['id']; 
        $_SESSION['user_nip'] = $user_data['nip']; // nip bisa disimpan di variabel session lain jika masih dibutuhkan
        $_SESSION['user_name'] = $user_data['nama'];
        $_SESSION['user_role'] = $user_data['role'];

        // Arahkan ke dashboard sesuai role
        if ($user_data['role'] === 'superadmin') {
            header("Location: ../superadmin/dashboard_superadmin.php");
        } else if ($user_data['role'] === 'admin') {
            header("Location: ../admin/dashboard_admin.php");
        } else if ($user_data['role'] === 'pengelola') {
            header("Location: ../pengelola/dashboard_pengelola.php");
        } else {
            // Arahkan ke halaman utama jika role tidak terdefinisi
            header("Location: ../index.php");
        }
        exit(); // Hentikan eksekusi skrip setelah redirect
    }
}

// Jika login gagal (email tidak ditemukan atau password salah)
echo "<h1>Login Gagal</h1>";
echo "<p>Email atau password yang Anda masukkan salah.</p>";
echo "<a href='../login.html'>Coba Lagi</a>";

$stmt->close();
$conn->close();
?>