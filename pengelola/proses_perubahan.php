<?php
// pengelola/proses_perubahan.php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db = "oai";
$conn = new mysqli($host, $user, $pass, $db);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Hanya proses jika permintaan adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari formulir
    $jurnal_id = (int)$_POST['jurnal_id'];
    $request_type = $_POST['request_type'];
    $alasan = trim($_POST['alasan']);

    // Validasi sederhana
    if (empty($jurnal_id) || empty($request_type) || empty($alasan)) {
        $_SESSION['error_message'] = "Semua kolom harus diisi.";
        header("Location: ajukan_perubahan.php?id=" . $jurnal_id);
        exit();
    }

    // Ambil ID pengelola dari session
    $pengelola_id = $_SESSION['user_id'];

    // Siapkan query untuk memasukkan data ke tabel submission_requests
    $sql = "INSERT INTO submission_requests (jurnal_id, pengelola_id, request_type, alasan, status) VALUES (?, ?, ?, ?, 'pending')";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Bind parameter
        mysqli_stmt_bind_param($stmt, "iiss", $jurnal_id, $pengelola_id, $request_type, $alasan);
        
        // Eksekusi
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Permintaan Anda telah berhasil dikirim dan akan segera ditinjau oleh Admin.";
        } else {
            $_SESSION['error_message'] = "Gagal mengirim permintaan: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error_message'] = "Gagal menyiapkan statement: " . mysqli_error($conn);
    }

    mysqli_close($conn);
    // Arahkan kembali ke halaman dashboard
    header("Location: daftar_jurnal.php");
    exit();

} else {
    // Jika diakses langsung, arahkan ke dashboard
    header("Location: daftar_jurnal.php");
    exit();
}
?>