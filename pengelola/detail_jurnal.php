<?php
session_start();

// Konfigurasi database MySQL
$host = "localhost";
$user = "root";
$pass = "";
$db = "oai";
$conn = new mysqli($host, $user, $pass, $db);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// 1. Validasi Input
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "ID Jurnal tidak valid.";
    header("Location: daftar_jurnal.php"); 
    exit();
}
$jurnal_id = (int)$_GET['id'];

// 2. Ambil data jurnal, termasuk catatan admin
$sql = "SELECT * FROM jurnal_sumber WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $jurnal_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $jurnal = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} else {
    die("Error preparing statement: " . mysqli_error($conn));
}

// 3. Handle jika jurnal tidak ditemukan
if (!$jurnal) {
    $_SESSION['error_message'] = "Jurnal tidak ditemukan.";
    header("Location: daftar_jurnal.php");
    exit();
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Jurnal - <?php echo htmlspecialchars($jurnal['judul_jurnal']); ?></title>
    
    <link rel="stylesheet" href="admin_style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<style>

    /* ========================================= */
/* === Gaya untuk Halaman Detail Jurnal === */
/* ========================================= */

.journal-title {
    margin-top: 0;
    margin-bottom: 30px;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 15px;
    color: #34495e;
    font-size: 20px;
}

.content-panel fieldset {
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    margin-bottom: 25px;
}

.content-panel legend {
    font-weight: 600;
    color: #34495e;
    padding: 0 10px;
    font-size: 16px;
}

.detail-group {
    display: flex;
    flex-wrap: wrap;
    padding: 12px 0;
    border-bottom: 1px solid #f1f1f1;
    font-size: 14px;
}
.detail-group:last-child {
    border-bottom: none;
}

.detail-label {
    flex-basis: 30%;
    font-weight: 500;
    color: #555;
    padding-right: 15px;
}

.detail-value {
    flex-basis: 70%;
    color: #333;
    word-break: break-word;
}

.detail-value a {
    color: #3498db;
    text-decoration: none;
}
.detail-value a:hover {
    text-decoration: underline;
}

/* Tombol Sekunder */
.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px; /* Jarak antara ikon dan teks */
    padding: 10px 20px;
    border: 1px solid #ccc;
    border-radius: 8px; /* Sudut membulat */
    background-color: #ecf0f1;
    color: #000;
    text-decoration: none;
    font-weight: 500;
    font-size: 16px;
    cursor: pointer;
}
.btn-secondary:hover {
    background-color: #95a5a6;
}

/* Penyesuaian Responsif untuk Halaman Detail */
@media (max-width: 768px) {
    .detail-label, .detail-value {
        flex-basis: 100%;
    }
    .detail-label {
        margin-bottom: 5px;
        font-weight: 600;
    }
}

</style>

<body>

    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="sidebar-toggle-btn" id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                    <img src="../Images/logo-header-2024-normal.png" alt="Logo Universitas Lampung">
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_pengelola.php" ><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="tambah_jurnal.php" ><i class="fas fa-plus-circle"></i> <span>Daftar Jurnal Baru</span></a></li>
                <li><a href="daftar_jurnal.php" class="active"><i class="fas fa-list-alt"></i> <span>Daftar & Status Jurnal</span></a></li>
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
                <div class="content-panel">
                    <div class="panel-header">
                        <a href="daftar_jurnal.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i><span>Kembali ke Daftar</span>
                        </a>
                        <h1>Detail Jurnal</h1>
                    </div>
                    
                    <h2 class="journal-title">Judul Jurnal: <?php echo htmlspecialchars($jurnal['judul_jurnal']); ?></h2>
                    
                    <!-- Fieldset baru untuk catatan admin -->
                    <fieldset>
                        <legend>Status dan Catatan dari Admin</legend>
                        <div class="detail-group">
                            <span class="detail-label">Status Saat Ini:</span>
                            <span class="detail-value">
                                <?php
                                $status = $jurnal['status'];
                                $status_class = 'status-' . str_replace('_', '-', $status);
                                $status_text = ucwords(str_replace('_', ' ', $status));
                                echo '<span class="status ' . $status_class . '">' . htmlspecialchars($status_text) . '</span>';
                                ?>
                            </span>
                        </div>
                        <div class="detail-group">
                            <span class="detail-label">Catatan Admin:</span>
                            <!-- Menggunakan nl2br untuk menampilkan baris baru dari database -->
                            <span class="detail-value"><?php echo nl2br(htmlspecialchars($jurnal['catatan_admin'] ?: '-')); ?></span>
                        </div>
                    </fieldset>
                    
                    <fieldset>
                        <legend>Contact Detail</legend>
                        <div class="detail-group"><span class="detail-label">Nama Kontak:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['nama_kontak']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Email Kontak:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['email_kontak']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Institusi:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['institusi']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Fakultas:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['fakultas']); ?></span></div>
                    </fieldset>

                    <fieldset>
                        <legend>Journal Information</legend>
                        <div class="detail-group"><span class="detail-label">Judul Asli:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['judul_jurnal_asli']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Judul :</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['judul_jurnal_asli']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">DOI:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['doi'] ?: '-'); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Tipe:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['journal_type']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">ISSN (Cetak):</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['p_issn'] ?: '-'); ?></span></div>
                        <div class="detail-group"><span class="detail-label">e-ISSN (Online):</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['e_issn'] ?: '-'); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Akreditasi SINTA:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['akreditasi_sinta'] ?: '-'); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Indeks Scopus:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['index_scopus'] ?: '-'); ?></span></div>
                        </fieldset>

                    <fieldset>
                        <legend>Publisher & Journal Contact</legend>
                        <div class="detail-group"><span class="detail-label">Penerbit:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['penerbit']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Negara Penerbit:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['country_of_publisher']); ?></span></div>
                        <div class="detail-group">
                            <span class="detail-label">Website Jurnal:</span>
                            <span class="detail-value"><a href="<?php echo htmlspecialchars($jurnal['website_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($jurnal['website_url']); ?></a></span>
                        </div>
                        <div class="detail-group"><span class="detail-label">Nama Kontak Jurnal:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['journal_contact_name']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Email Resmi Jurnal:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['journal_official_email']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Telepon Kontak Jurnal:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['journal_contact_phone'] ?: '-'); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Tahun Mulai Online:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['start_year']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Periode Terbit:</span><span class="detail-value"><?php echo htmlspecialchars(str_replace(',', ', ', $jurnal['issue_period'])); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Tim Editor:</span><span class="detail-value"><?php echo htmlspecialchars(str_replace(',', ', ', $jurnal['editorial_team'])); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Alamat Editorial:</span><span class="detail-value"><?php echo nl2br(htmlspecialchars($jurnal['editorial_address'])); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Aim dan Scope:</span><span class="detail-value"><?php echo nl2br(htmlspecialchars($jurnal['aim_and_scope'])); ?></span></div>
                    </fieldset>

                    <fieldset>
                        <legend>Additional Information & URLs</legend>
                        <div class="detail-group"><span class="detail-label">Memiliki Homepage?:</span><span class="detail-value"><?php echo $jurnal['has_homepage'] ? 'Ya' : 'Tidak'; ?></span></div>
                        <div class="detail-group"><span class="detail-label">Menggunakan OJS?:</span><span class="detail-value"><?php echo $jurnal['is_using_ojs'] ? 'Ya' : 'Tidak'; ?></span></div>
                        <div class="detail-group"><span class="detail-label">Link OJS:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['ojs_link'] ?: '-'); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Link Open Access:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['open_access_link'] ?: '-'); ?></span></div>
                        <div class="detail-group"><span class="detail-label">URL Editorial Board:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['url_editorial_board']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">URL Kontak:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['url_contact']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">URL Reviewer:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['url_reviewer'] ?: '-'); ?></span></div>
                        <div class="detail-group"><span class="detail-label">URL Google Scholar:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['url_google_scholar'] ?: '-'); ?></span></div>
                        <div class="detail-group"><span class="detail-label">URL Sinta:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['link_sinta'] ?: '-'); ?></span></div>
                        <div class="detail-group"><span class="detail-label">URL Garuda:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['link_garuda'] ?: '-'); ?></span></div>
                        <div class="detail-group"><span class="detail-label">URL Cover:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['url_cover'] ?: '-'); ?></span></div>
                    </fieldset>

                    <fieldset>
                        <legend>Subject Area</legend>
                        <div class="detail-group"><span class="detail-label">Subjek Arjuna:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['subject_arjuna']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Sub-subjek Arjuna:</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['sub_subject_arjuna']); ?></span></div>
                        <div class="detail-group"><span class="detail-label">Subjek Garuda:</span><span class="detail-value"><?php echo htmlspecialchars(str_replace(',', ', ', $jurnal['subject_garuda'])); ?></span></div>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>

    <script>
        
document.getElementById('sidebar-toggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    if (document.getElementById('sidebar').classList.contains('collapsed')) {
        localStorage.setItem('sidebarStatePengelola', 'collapsed');
    } else {
        localStorage.setItem('sidebarStatePengelola', 'expanded');
    }
});
if (localStorage.getItem('sidebarStatePengelola') === 'collapsed') {
    document.getElementById('sidebar').classList.add('collapsed');
}
    </script>
</body>
</html>