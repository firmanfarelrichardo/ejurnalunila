<?php
// Memulai session jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mendapatkan nama file halaman yang sedang dibuka
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a href="index.php" class="logo">
                <img src="https://www.unila.ac.id/storage/2024/08/logo-header-2024-normal.png" alt="Logo Universitas Lampung">
            </a>

            <div class="nav-wrapper" id="nav-wrapper">
                <nav class="main-nav">
                    <ul>
                        <li class="<?php if ($current_page == 'index.php') { echo 'active'; } ?>"><a href="index.php">Home</a></li>
                        <li class="<?php if ($current_page == 'fakultas.php' || $current_page == 'jurnal_fak.php') { echo 'active'; } ?>"><a href="fakultas.php">Fakultas</a></li>
                        <li class="<?php if ($current_page == 'penerbit.php' || $current_page == 'jurnal_penerbit.php') { echo 'active'; } ?>"><a href="penerbit.php">Penerbit</a></li>
                        <li class="<?php if ($current_page == 'subjek.php' || $current_page == 'jurnal_subjek.php') { echo 'active'; } ?>"><a href="subjek.php">Subjek</a></li>
                        <li class="<?php if ($current_page == 'statistik.php') { echo 'active'; } ?>"><a href="statistik.php">Statistik</a></li>
                    </ul>
                </nav>
<<<<<<< HEAD
            </div>
            
=======
            <button id="mobile-menu-toggle" class="mobile-menu-button">
                <i class="fas fa-bars"></i>
            </button>
>>>>>>> 45bf2530d2a044317161da647d0673ef01ce26cf
        </div>
    </header>