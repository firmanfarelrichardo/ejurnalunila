<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    <link href="../bootstrap/css/bootstrap.min.css">
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="fakultas.php">Fakultas</a></li>
                        <li><a href="#">Tentang Kami</a></li>
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