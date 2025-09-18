<?php
// admin/cetak_editorial.php
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
$editorials = [];
$sql = "SELECT judul_jurnal, editorial_team FROM jurnal_sumber WHERE editorial_team IS NOT NULL AND editorial_team != '' ORDER BY judul_jurnal ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $editorials[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Tim Editorial - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <h2>Admin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> Kelola Pengelola</a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php" class="active"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="header no-print">
                <h1>Laporan Tim Editorial Jurnal</h1>
                <div class="user-profile">
                    <span>Role: Admin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <div class="card">
                    <div class="card-header no-print">
                        <h3>Daftar Tim Editorial</h3>
                        <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Cetak Semua</button>
                    </div>
                    
                    <div id="printable-area">
                        <div class="print-header">
                            <h1>Laporan Tim Editorial</h1>
                            <p>Dicetak pada: <?php echo date('d F Y, H:i'); ?></p>
                        </div>
                        <?php if (!empty($editorials)): ?>
                            <?php foreach ($editorials as $index => $item): ?>
                                <div class="editorial-item" id="editorial-item-<?php echo $index; ?>">
                                    <div class="editorial-header no-print">
                                        <h2 class="journal-title"><?php echo htmlspecialchars($item['judul_jurnal']); ?></h2>
                                        <button onclick="printSpecificJournal('editorial-item-<?php echo $index; ?>')" class="btn-print-single">
                                            <i class="fas fa-print"></i> Cetak Jurnal Ini
                                        </button>
                                    </div>
                                    <h2 class="journal-title-print"><?php echo htmlspecialchars($item['judul_jurnal']); ?></h2>
                                    <div class="editorial-team-content">
                                        <pre><?php echo htmlspecialchars($item['editorial_team']); ?></pre>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Tidak ada data tim editorial yang ditemukan.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
function printSpecificJournal(elementId) {
    const body = document.body;
    const printItem = document.getElementById(elementId);

    // Tambahkan kelas ke body dan item yang akan dicetak
    body.classList.add('printing-active');
    printItem.classList.add('is-printing');

    // Panggil fungsi print browser
    window.print();

    // Hapus kelas setelah dialog print ditutup (dengan sedikit jeda)
    setTimeout(() => {
        body.classList.remove('printing-active');
        printItem.classList.remove('is-printing');
    }, 500);
}
</script>
</body>
</html>