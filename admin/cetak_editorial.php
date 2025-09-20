<?php
// admin/cetak_editorial.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once '../database/config.php';
$conn = connect_to_database();

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
            <div class="sidebar-header">
                <button class="sidebar-toggle-btn" id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                     <img src="../images/logo-header-2024-normal.png" alt="Logo Universitas Lampung">
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php" ><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
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
                                    <div class="editorial-header">
                                        <h2 class="journal-title"><?php echo htmlspecialchars($item['judul_jurnal']); ?></h2>
                                        <div class="action-wrapper no-print">
                                            <button class="toggle-editorial">
                                                <i class="fas fa-chevron-down"></i>
                                                <span>Lihat Selengkapnya</span>
                                            </button>
                                            <button onclick="printSpecificJournal('editorial-item-<?php echo $index; ?>')" class="btn-print-single">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <h2 class="journal-title-print"><?php echo htmlspecialchars($item['judul_jurnal']); ?></h2>
                                    <div class="collapsible-content">
                                        <div class="editorial-team-content">
                                            <pre><?php echo htmlspecialchars($item['editorial_team']); ?></pre>
                                        </div>
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
// Fungsi untuk cetak jurnal spesifik
function printSpecificJournal(elementId) {
    const body = document.body;
    const printItem = document.getElementById(elementId);
    body.classList.add('printing-active');
    printItem.classList.add('is-printing');
    window.print();
    setTimeout(() => {
        body.classList.remove('printing-active');
        printItem.classList.remove('is-printing');
    }, 500);
}

// Fungsi untuk toggle dropdown editorial
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.toggle-editorial');
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const content = this.closest('.editorial-item').querySelector('.collapsible-content');
            const icon = this.querySelector('i');
            const text = this.querySelector('span');

            if (content.classList.contains('active')) {
                content.classList.remove('active');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
                text.textContent = 'Lihat Selengkapnya';
            } else {
                content.classList.add('active');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
                text.textContent = 'Tutup';
            }
        });
    });
});


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