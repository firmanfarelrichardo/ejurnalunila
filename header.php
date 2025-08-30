<?php
// Memulai session jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mendapatkan nama file halaman yang sedang dibuka, contoh: "index.php"
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unila E-Journal System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="/Journal-OAI/frontend/dist/styles.css">

    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-left">
                <a href="index.php" class="logo">
                    <img src="https://www.unila.ac.id/storage/2024/08/logo-header-2024-normal.png" alt="Logo Universitas Lampung">
                </a>
                <nav class="main-nav">
                    <ul>
                        <li class="<?php if ($current_page == 'index.php') { echo 'active'; } ?>">
                            <a href="index.php">Home</a>
                        </li>

                        <li class="<?php if ($current_page == 'fakultas.php' || $current_page == 'jurnal_fak.php') { echo 'active'; } ?>">
                            <a href="fakultas.php">Fakultas</a>
                        </li>

                        <li class="<?php if ($current_page == 'penerbit.php') { echo 'active'; } ?>">
                            <a href="penerbit.php">Penerbit</a>
                        </li>
                        
                        <li class="<?php if ($current_page == 'subjek.php') { echo 'active'; } ?>">
                            <a href="subjek.php">Subjek</a>
                        </li>

                        <li class="<?php if ($current_page == 'statistik.php') { echo 'active'; } ?>">
                            <a href="statistik.php">Statistik</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <div class="header-right">
                <div class="user-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a href="api/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    <?php else: ?>
                        <a href="login.html"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>