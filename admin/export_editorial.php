<?php
// admin/export_editorial.php
session_start();

// Cek sesi admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db = "oai";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { 
    die("Koneksi gagal: " . $conn->connect_error); 
}

// Ambil data jurnal yang memiliki tim editorial
$sql = "SELECT judul_jurnal, editorial_team FROM jurnal_sumber WHERE editorial_team IS NOT NULL AND editorial_team != '' ORDER BY judul_jurnal ASC";
$result = $conn->query($sql);

// Nama file CSV yang akan diunduh
$filename = "laporan_tim_editorial_" . date('Y-m-d') . ".csv";

// Atur header HTTP untuk mengunduh file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Buka output stream PHP untuk menulis file CSV
$output = fopen('php://output', 'w');

// Tulis baris header ke file CSV
fputcsv($output, array('Judul Jurnal', 'Tim Editorial'));

// Tulis setiap baris data ke file CSV
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

fclose($output);
$conn->close();
exit();
?>