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
    <title>Unila E-Journal System</title>
    
    <link rel="stylesheet" href="/Journal-OAI/frontend/dist/styles.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a href="index.php" class="logo">
                <img src="Images/logo-header-2024-normal.png" alt="Logo Universitas Lampung">
            </a>

            <div class="nav-wrapper" id="nav-wrapper">
                <nav class="main-nav">
                    <ul>
                        <li class="<?php if ($current_page == 'index.php') { echo 'active'; } ?>"><a href="index.php">Home</a></li>
                        <li class="<?php if ($current_page == 'fakultas.php' || $current_page == 'jurnal_fak.php') { echo 'active'; } ?>"><a href="fakultas.php">Fakultas</a></li>
                        <li class="<?php if ($current_page == 'penerbit.php' || $current_page == 'jurnal_penerbit.php') { echo 'active'; } ?>"><a href="penerbit.php">Penerbit</a></li>
                        <li class="<?php if ($current_page == 'subjek.php' || $current_page == 'jurnal_subjek.php') { echo 'active'; } ?>"><a href="subjek.php">Subjek</a></li>
                        <li class="<?php if ($current_page == 'statistik.php') { echo 'active'; } ?>"><a href="statistik.php">Statistik</a></li>
                        <li class="<?php if ($current_page == 'tentang.php') { echo 'active'; } ?>"><a href="tentang.php">Tentang</a></li>
                    </ul>
                </nav>
                <div class="user-actions">
                    <button id="theme-toggle" class="action-button" title="Toggle Dark Mode" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem; padding: 0 15px;">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            <button id="mobile-menu-toggle" class="mobile-menu-button">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleButton = document.getElementById('theme-toggle');
            const icon = toggleButton.querySelector('i');
            const currentTheme = localStorage.getItem('theme') || 'light';

            if (currentTheme === 'dark') {
                document.body.setAttribute('data-theme', 'dark');
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }

            toggleButton.addEventListener('click', () => {
                let theme = document.body.getAttribute('data-theme');
                if (theme === 'dark') {
                    document.body.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                } else {
                    document.body.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                }
            });
        });
    </script>