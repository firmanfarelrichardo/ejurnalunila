<?php
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pengelola') {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "oai";
$conn = new mysqli($host, $user, $pass, $db);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Cek apakah ada ID jurnal di URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "Permintaan tidak valid.";
    header("Location: daftar_jurnal.php");
    exit();
}
$jurnal_id = (int)$_GET['id'];

// Ambil judul jurnal dari database
$sql_jurnal = "SELECT judul_jurnal FROM jurnal_sumber WHERE id = ?";
$stmt_jurnal = mysqli_prepare($conn, $sql_jurnal);
mysqli_stmt_bind_param($stmt_jurnal, "i", $jurnal_id);
mysqli_stmt_execute($stmt_jurnal);
$result_jurnal = mysqli_stmt_get_result($stmt_jurnal);
$jurnal = mysqli_fetch_assoc($result_jurnal);

// Jika jurnal tidak ditemukan, kembalikan
if (!$jurnal) {
    $_SESSION['error_message'] = "Jurnal tidak ditemukan.";
    header("Location: daftar_jurnal.php");
    exit();
}
$judul_jurnal = $jurnal['judul_jurnal'];
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Perubahan - <?php echo htmlspecialchars($judul_jurnal); ?></title>
    
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <div class="dashboard-container">

        <div class="sidebar" id="sidebar">
            <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <h2>Pengelola</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_pengelola.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="tambah_jurnal.php"><i class="fas fa-plus-circle"></i> <span>Daftar Jurnal Baru</span></a></li>
                <li class="active"><a href="daftar_jurnal.php" class="active"><i class="fas fa-list-alt"></i> <span>Daftar & Status Jurnal</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        
        <div class="main-content">
            
            <div class="header">
                <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
                <div class="user-profile">
                    <span>Role: Pengelola</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <div class="form-container">
                    <h1>Ajukan Perubahan/Penghapusan</h1>
                    <p>Untuk Jurnal: <strong><?php echo htmlspecialchars($judul_jurnal); ?></strong></p>

                    <form action="proses_perubahan.php" method="POST">
                        <input type="hidden" name="jurnal_id" value="<?php echo $jurnal_id; ?>">

                        <fieldset>
                            <legend>Detail Permintaan</legend>
                            <div class="form-group">
                                <label>Jenis Permintaan</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="request_type" value="edit" checked> Minta Edit Data</label>
                                    <label><input type="radio" name="request_type" value="delete"> Minta Hapus Jurnal</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="alasan">Alasan dan Keterangan</label>
                                <textarea 
                                    id="alasan" 
                                    name="alasan" 
                                    rows="6" 
                                    required 
                                    placeholder="Contoh untuk Minta Edit: Mohon ubah nama penerbit dari 'Penerbit A' menjadi 'Penerbit B'.&#10;Contoh untuk Minta Hapus: Jurnal ini sudah tidak aktif lagi."></textarea>
                                <small style="display: block; margin-top: 8px; color: #7f8c8d;">Jelaskan secara detail perubahan yang Anda inginkan atau alasan penghapusan.</small>
                            </div>
                        </fieldset>

                        <div class="form-actions">
                            <a href="daftar_jurnal.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Kirim Permintaan</button>
                        </div>
                    </form>
                </div>
            </div> </div> </div> <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>