<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki peran yang sesuai
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Pengaturan Database MySQL
require_once '../database/config.php';
$conn = connect_to_database();

$message = '';

// BAGIAN 1: PROSES FORM JIKA ADA DATA YANG DI-POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form
    $jurnal_id = (int)$_POST['jurnal_id'];
    $new_status = $_POST['status'];
    $catatan = trim($_POST['catatan_admin']);
    
    // Perbarui semua data jurnal
    $nama_kontak = trim($_POST['nama_kontak']);
    $email_kontak = trim($_POST['email_kontak']);
    $institusi = trim($_POST['institusi']);
    $fakultas = $_POST['fakultas'];
    $judul_jurnal_asli = trim($_POST['judul_jurnal_asli']);
    $judul_jurnal = trim($_POST['judul_jurnal']);
    $doi = trim($_POST['doi']);
    $journal_type = $_POST['journal_type'];
    $p_issn = trim($_POST['p_issn']);
    $e_issn = trim($_POST['e_issn']);
    $akreditasi_sinta = $_POST['akreditasi_sinta'];
    $index_scopus = $_POST['index_scopus'];
    $penerbit = trim($_POST['penerbit']);
    $country_of_publisher = $_POST['country_of_publisher'];
    $website_url = trim($_POST['website_url']);
    $journal_contact_name = trim($_POST['journal_contact_name']);
    $journal_official_email = trim($_POST['journal_official_email']);
    $journal_contact_phone = trim($_POST['journal_contact_phone']);
    $start_year = $_POST['start_year'];
    $issue_period = isset($_POST['issue_period']) ? implode(',', $_POST['issue_period']) : '';
    $editorial_team = trim($_POST['editorial_team']);
    $editorial_address = trim($_POST['editorial_address']);
    $aim_and_scope = trim($_POST['aim_and_scope']);
    $has_homepage = $_POST['has_homepage'];
    $is_using_ojs = $_POST['is_using_ojs'];
    $ojs_link = trim($_POST['ojs_link']);
    $open_access_link = trim($_POST['open_access_link']);
    $url_editorial_board = trim($_POST['url_editorial_board']);
    $url_contact = trim($_POST['url_contact']);
    $url_reviewer = trim($_POST['url_reviewer']);
    $url_google_scholar = trim($_POST['url_google_scholar']);
    $link_sinta = trim($_POST['link_sinta']);
    $link_garuda = trim($_POST['link_garuda']);
    $url_cover = trim($_POST['url_cover']);
    $link_oai = trim($_POST['link_oai']);
    $subject_arjuna = $_POST['subject_arjuna'];
    $sub_subject_arjuna = $_POST['sub_subject_arjuna'];
    $subject_garuda = isset($_POST['subject_garuda']) ? implode(',', $_POST['subject_garuda']) : '';
    
    // Validasi status
    $allowed_statuses = ['pending', 'selesai', 'ditolak', 'butuh_edit'];
    if (!in_array($new_status, $allowed_statuses)) {
        $_SESSION['error_message'] = "Status tidak valid.";
        header("Location: manage_journal.php");
        exit();
    }
    
    // Query untuk memperbarui semua kolom di tabel jurnal_sumber
    $sql_update = "UPDATE jurnal_sumber SET
        status = ?,
        catatan_admin = ?,
        nama_kontak = ?,
        email_kontak = ?,
        institusi = ?,
        fakultas = ?,
        judul_jurnal_asli = ?,
        judul_jurnal = ?,
        doi = ?,
        journal_type = ?,
        p_issn = ?,
        e_issn = ?,
        akreditasi_sinta = ?,
        index_scopus = ?,
        penerbit = ?,
        country_of_publisher = ?,
        website_url = ?,
        journal_contact_name = ?,
        journal_official_email = ?,
        journal_contact_phone = ?,
        start_year = ?,
        issue_period = ?,
        editorial_team = ?,
        editorial_address = ?,
        aim_and_scope = ?,
        has_homepage = ?,
        is_using_ojs = ?,
        ojs_link = ?,
        open_access_link = ?,
        url_editorial_board = ?,
        url_contact = ?,
        url_reviewer = ?,
        url_google_scholar = ?,
        link_sinta = ?,
        link_garuda = ?,
        url_cover = ?,
        link_oai = ?,
        subject_arjuna = ?,
        sub_subject_arjuna = ?,
        subject_garuda = ?
    WHERE id = ?";

    $stmt_update = mysqli_prepare($conn, $sql_update);
    
    if ($stmt_update) {
        // Perbaikan: String tipe data disesuaikan menjadi 40 karakter
        mysqli_stmt_bind_param($stmt_update, "ssssssssssssssssssssissssiisssssssssssssi",
            $new_status, $catatan, $nama_kontak, $email_kontak, $institusi, $fakultas, $judul_jurnal_asli,
            $judul_jurnal, $doi, $journal_type, $p_issn, $e_issn, $akreditasi_sinta, $index_scopus,
            $penerbit, $country_of_publisher, $website_url, $journal_contact_name, $journal_official_email,
            $journal_contact_phone, $start_year, $issue_period, $editorial_team, $editorial_address,
            $aim_and_scope, $has_homepage, $is_using_ojs, $ojs_link, $open_access_link,
            $url_editorial_board, $url_contact, $url_reviewer, $url_google_scholar, $link_sinta,
            $link_garuda, $url_cover, $link_oai, $subject_arjuna, $sub_subject_arjuna, $subject_garuda, $jurnal_id
        );
        
        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['success_message'] = "Status dan detail jurnal berhasil diperbarui.";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui jurnal: " . mysqli_stmt_error($stmt_update);
        }
        mysqli_stmt_close($stmt_update);
    } else {
         $_SESSION['error_message'] = "Gagal menyiapkan statement: " . mysqli_error($conn);
    }
    
    header("Location: manage_journal.php");
    exit();
}

// BAGIAN 2: TAMPILKAN DATA JURNAL (GET REQUEST)
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "ID Jurnal tidak valid.";
    header("Location: manage_journal.php");
    exit();
}
$jurnal_id = (int)$_GET['id'];

// Mengambil semua data dari tabel jurnal_sumber
$sql_select = "SELECT * FROM jurnal_sumber WHERE id = ?";
$stmt_select = mysqli_prepare($conn, $sql_select);
mysqli_stmt_bind_param($stmt_select, "i", $jurnal_id);
mysqli_stmt_execute($stmt_select);
$result = mysqli_stmt_get_result($stmt_select);
$jurnal = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt_select);

if (!$jurnal) {
    $_SESSION['error_message'] = "Jurnal tidak ditemukan.";
    header("Location: manage_journal.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tinjau Jurnal - <?php echo htmlspecialchars($jurnal['judul_jurnal']); ?></title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="style.css">     
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .admin-fieldset {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 35px;
            background-color: #fdfdfd;
        }
        .admin-fieldset legend {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            padding: 0 15px;
        }
        .status-needs-edit { background-color: #9b59b6; color: white; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 12px; font-weight: bold; color: white; }
        .status-pending { background-color: #f39c12; }
        .status-approved { background-color: #2ecc71; }
        .status-rejected { background-color: #e74c3c; }
        .status-butuh_edit { background-color: #3498db; }
        .form-group-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .form-group-row .form-group {
            flex: 1;
            min-width: 200px;
        }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-actions { margin-top: 20px; display: flex; justify-content: flex-end; gap: 15px; }
        .btn { padding: 12px 25px; border: none; border-radius: 5px; font-size: 16px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: background-color 0.3s, transform 0.2s; }
        .btn-primary { background-color: #3498db; color: white; }
        .btn-primary:hover { background-color: #2980b9; }
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        .checkbox-item {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 8px; /* Memberi jarak antara checkbox dan teks */
            align-items: start; 
        }
    </style>
</head>

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
                <li><a href="dashboard_admin.php" ><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_journal.php" class="active"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
                <li><a href="change_password.php"><i class="fas fa-lock"></i> <span>Ganti Password</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="header">
                <h1>Detail Jurnal Submission</h1>
                <div class="user-profile">
                    <span>Role: Admin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>
            
            <div class="content-area">
                <div class="content-panel">
                    <div class="panel-header">
                        <h1>Tinjau Pengajuan Jurnal</h1>
                    </div>
                    
                    <h2 class="journal-title">Judul Jurnal: <?php echo htmlspecialchars($jurnal['judul_jurnal']); ?></h2>
                    
                    <form id="journalForm" action="tinjau_jurnal.php" method="POST">
                        <input type="hidden" name="jurnal_id" value="<?php echo htmlspecialchars($jurnal['id']); ?>">
                        
                        <fieldset>
                            <legend>Contact Detail</legend>
                            <div class="form-group-row">
                                <div class="form-group">
                                    <label for="nama_kontak">Nama Kontak*</label>
                                    <input type="text" id="nama_kontak" name="nama_kontak" value="<?php echo htmlspecialchars($jurnal['nama_kontak']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email_kontak">Email Kontak*</label>
                                    <input type="email" id="email_kontak" name="email_kontak" value="<?php echo htmlspecialchars($jurnal['email_kontak']); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="institusi">Institusi*</label>
                                <input type="text" id="institusi" name="institusi" value="<?php echo htmlspecialchars($jurnal['institusi']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="fakultas" required>Fakultas*</label>
                                <select id="fakultas" name="fakultas" >
                                    <option value="Fakultas Ekonomi dan Bisnis" <?php echo ($jurnal['fakultas'] == 'Fakultas Ekonomi dan Bisnis') ? 'selected' : ''; ?>>Fakultas Ekonomi dan Bisnis</option>
                                    <option value="Fakultas Hukum" <?php echo ($jurnal['fakultas'] == 'Fakultas Hukum') ? 'selected' : ''; ?>>Fakultas Hukum</option>
                                    <option value="Fakultas Ilmu Sosial dan Ilmu Politik" <?php echo ($jurnal['fakultas'] == 'Fakultas Ilmu Sosial dan Ilmu Politik') ? 'selected' : ''; ?>>Fakultas Ilmu Sosial dan Ilmu Politik</option>
                                    <option value="Fakultas Kedokteran" <?php echo ($jurnal['fakultas'] == 'Fakultas Kedokteran') ? 'selected' : ''; ?>>Fakultas Kedokteran</option>
                                    <option value="Fakultas Keguruan dan Ilmu Pendidikan" <?php echo ($jurnal['fakultas'] == 'Fakultas Keguruan dan Ilmu Pendidikan') ? 'selected' : ''; ?>>Fakultas Keguruan dan Ilmu Pendidikan</option>
                                    <option value="Fakultas Matematika dan Ilmu Pengetahuan Alam" <?php echo ($jurnal['fakultas'] == 'Fakultas Matematika dan Ilmu Pengetahuan Alam') ? 'selected' : ''; ?>>Fakultas Matematika dan Ilmu Pengetahuan Alam</option>
                                    <option value="Fakultas Pertanian" <?php echo ($jurnal['fakultas'] == 'Fakultas Pertanian') ? 'selected' : ''; ?>>Fakultas Pertanian</option>
                                    <option value="Fakultas Teknik" <?php echo ($jurnal['fakultas'] == 'Fakultas Teknik') ? 'selected' : ''; ?>>Fakultas Teknik</option>
                                </select>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>Journal Information</legend>
                            <div class="form-group">
                                <label for="judul_jurnal_asli">Judul Asli</label>
                                <input type="text" id="judul_jurnal_asli" name="judul_jurnal_asli" value="<?php echo htmlspecialchars($jurnal['judul_jurnal_asli']); ?>" >
                            </div>
                            <div class="form-group">
                                <label for="judul_jurnal">Judul</label>
                                <input type="text" id="judul_jurnal" name="judul_jurnal" value="<?php echo htmlspecialchars($jurnal['judul_jurnal']); ?>" >
                            </div>
                            <div class="form-group">
                                <label for="doi">DOI</label>
                                <input type="text" id="doi" name="doi" value="<?php echo htmlspecialchars($jurnal['doi']); ?>">
                            </div>
                            <div class="form-group-row">
                                <div class="form-group">
                                    <label for="journal_type">Tipe Jurnal</label>
                                    <select id="journal_type" name="journal_type">
                                        <option value="Journal" <?php echo ($jurnal['journal_type'] == 'Journal') ? 'selected' : ''; ?>>Journal</option>
                                        <option value="Conference" <?php echo ($jurnal['journal_type'] == 'Conference') ? 'selected' : ''; ?>>Conference</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="p_issn">ISSN (Cetak)</label>
                                    <input type="text" id="p_issn" name="p_issn" value="<?php echo htmlspecialchars($jurnal['p_issn']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="e_issn">e-ISSN (Online)</label>
                                    <input type="text" id="e_issn" name="e_issn" value="<?php echo htmlspecialchars($jurnal['e_issn']); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="akreditasi_sinta">Akreditasi SINTA</label>
                                <select id="akreditasi_sinta" name="akreditasi_sinta" >
                                    <option value="Belum Terakreditasi" <?php echo ($jurnal['akreditasi_sinta'] == 'Belum Terakreditasi') ? 'selected' : ''; ?>>Belum Terakreditasi</option>
                                    <option value="Sinta 1" <?php echo ($jurnal['akreditasi_sinta'] == 'Sinta 1') ? 'selected' : ''; ?>>Sinta 1</option>
                                    <option value="Sinta 2" <?php echo ($jurnal['akreditasi_sinta'] == 'Sinta 2') ? 'selected' : ''; ?>>Sinta 2</option>
                                    <option value="Sinta 3" <?php echo ($jurnal['akreditasi_sinta'] == 'Sinta 3') ? 'selected' : ''; ?>>Sinta 3</option>
                                    <option value="Sinta 4" <?php echo ($jurnal['akreditasi_sinta'] == 'Sinta 4') ? 'selected' : ''; ?>>Sinta 4</option>
                                    <option value="Sinta 5" <?php echo ($jurnal['akreditasi_sinta'] == 'Sinta 5') ? 'selected' : ''; ?>>Sinta 5</option>
                                    <option value="Sinta 6" <?php echo ($jurnal['akreditasi_sinta'] == 'Sinta 6') ? 'selected' : ''; ?>>Sinta 6</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="index_scopus">Indeks Scopus</label>
                                <select id="index_scopus" name="index_scopus">
                                    <option value="Belum Terindeks" <?php echo ($jurnal['index_scopus'] == 'Belum Terindeks') ? 'selected' : ''; ?>>Belum Terindeks</option>
                                    <option value="Q1" <?php echo ($jurnal['index_scopus'] == 'Q1') ? 'selected' : ''; ?>>Q1</option>
                                    <option value="Q2" <?php echo ($jurnal['index_scopus'] == 'Q2') ? 'selected' : ''; ?>>Q2</option>
                                    <option value="Q3" <?php echo ($jurnal['index_scopus'] == 'Q3') ? 'selected' : ''; ?>>Q3</option>
                                    <option value="Q4" <?php echo ($jurnal['index_scopus'] == 'Q4') ? 'selected' : ''; ?>>Q4</option>
                                </select>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>Publisher & Journal Contact</legend>
                            <div class="form-group">
                                <label for="penerbit">Penerbit</label>
                                <input type="text" id="penerbit" name="penerbit" value="<?php echo htmlspecialchars($jurnal['penerbit']); ?>" >
                            </div>
                            <div class="form-group">
                                <label for="country_of_publisher">Negara Penerbit</label>
                                <input type="text" id="country_of_publisher" name="country_of_publisher" value="<?php echo htmlspecialchars($jurnal['country_of_publisher']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="website_url">Website Jurnal</label>
                                <input type="url" id="website_url" name="website_url" value="<?php echo htmlspecialchars($jurnal['website_url']); ?>" >
                            </div>
                            <div class="form-group-row">
                                <div class="form-group">
                                    <label for="journal_contact_name">Nama Kontak Jurnal</label>
                                    <input type="text" id="journal_contact_name" name="journal_contact_name" value="<?php echo htmlspecialchars($jurnal['journal_contact_name']); ?>" >
                                </div>
                                <div class="form-group">
                                    <label for="journal_official_email">Email Resmi Jurnal</label>
                                    <input type="email" id="journal_official_email" name="journal_official_email" value="<?php echo htmlspecialchars($jurnal['journal_official_email']); ?>" >
                                </div>
                                <div class="form-group">
                                    <label for="journal_contact_phone">Telepon Kontak Jurnal</label>
                                    <input type="tel" id="journal_contact_phone" name="journal_contact_phone" value="<?php echo htmlspecialchars($jurnal['journal_contact_phone']); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="start_year">Tahun Mulai Online</label>
                                <input type="number" id="start_year" name="start_year" min="1900" max="2099" step="1" placeholder="YYYY" value="<?php echo htmlspecialchars($jurnal['start_year']); ?>" >
                            </div>
                            <div class="form-group">
                                <label for="issue_period">Periode Terbit</label>
                                <div class="checkbox-group">
                                    <?php
                                    $issue_periods_array = explode(',', $jurnal['issue_period']);
                                    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                    foreach ($months as $month): ?>
                                        <div>
                                            <input type="checkbox" id="month_<?php echo strtolower($month); ?>" name="issue_period[]" value="<?php echo $month; ?>" <?php echo in_array($month, $issue_periods_array) ? 'checked' : ''; ?>>
                                            <label for="month_<?php echo strtolower($month); ?>"><?php echo $month; ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="editorial_team">Tim Editor</label>
                                <textarea id="editorial_team" name="editorial_team" rows="4" ><?php echo htmlspecialchars($jurnal['editorial_team']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="editorial_address">Alamat Editorial</label>
                                <textarea id="editorial_address" name="editorial_address" rows="4" ><?php echo htmlspecialchars($jurnal['editorial_address']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="aim_and_scope">Aim dan Scope</label>
                                <textarea id="aim_and_scope" name="aim_and_scope" rows="6" ><?php echo htmlspecialchars($jurnal['aim_and_scope']); ?></textarea>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>Additional Information & URLs</legend>
                            <div class="form-group">
                                <label>Memiliki Homepage?</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="has_homepage" value="1" <?php echo $jurnal['has_homepage'] ? 'checked' : ''; ?>> Ya</label>
                                    <label><input type="radio" name="has_homepage" value="0" <?php echo !$jurnal['has_homepage'] ? 'checked' : ''; ?>> Tidak</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Menggunakan OJS?</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="is_using_ojs" value="1" <?php echo $jurnal['is_using_ojs'] ? 'checked' : ''; ?>> Ya</label>
                                    <label><input type="radio" name="is_using_ojs" value="0" <?php echo !$jurnal['is_using_ojs'] ? 'checked' : ''; ?>> Tidak</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ojs_link">Link OJS</label>
                                <input type="url" id="ojs_link" name="ojs_link" value="<?php echo htmlspecialchars($jurnal['ojs_link']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="open_access_link">Link Open Access</label>
                                <input type="url" id="open_access_link" name="open_access_link" value="<?php echo htmlspecialchars($jurnal['open_access_link']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="url_editorial_board">URL Editorial Board</label>
                                <input type="url" id="url_editorial_board" name="url_editorial_board" value="<?php echo htmlspecialchars($jurnal['url_editorial_board']); ?>" >
                            </div>
                            <div class="form-group">
                                <label for="url_contact">URL Kontak</label>
                                <input type="url" id="url_contact" name="url_contact" value="<?php echo htmlspecialchars($jurnal['url_contact']); ?>" >
                            </div>
                            <div class="form-group">
                                <label for="url_reviewer">URL Reviewer</label>
                                <input type="url" id="url_reviewer" name="url_reviewer" value="<?php echo htmlspecialchars($jurnal['url_reviewer']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="url_google_scholar">URL Google Scholar</label>
                                <input type="url" id="url_google_scholar" name="url_google_scholar" value="<?php echo htmlspecialchars($jurnal['url_google_scholar']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="link_sinta">URL Sinta</label>
                                <input type="url" id="link_sinta" name="link_sinta" value="<?php echo htmlspecialchars($jurnal['link_sinta']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="link_garuda">URL Garuda</label>
                                <input type="url" id="link_garuda" name="link_garuda" value="<?php echo htmlspecialchars($jurnal['link_garuda']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="url_cover">URL Cover</label>
                                <input type="url" id="url_cover" name="url_cover" value="<?php echo htmlspecialchars($jurnal['url_cover']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="link_oai">URL OAI</label>
                                <input type="url" id="link_oai" name="link_oai" value="<?php echo htmlspecialchars($jurnal['link_oai']); ?>">
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>Subject Area</legend>
                            <div class="form-group">
                                <label for="subject_arjuna">Subjek Arjuna</label>
                                <select id="subject_arjuna" name="subject_arjuna">
                                    <option value="" <?php echo ($jurnal['subject_arjuna'] == '') ? 'selected' : ''; ?>>Pilih Subject Area...</option>
                                    <option value="Biokimia, Genetika dan Biologi Molekuler" <?php echo ($jurnal['subject_arjuna'] == 'Biokimia, Genetika dan Biologi Molekuler') ? 'selected' : ''; ?>>Biokimia, Genetika dan Biologi Molekuler</option>
                                    <option value="Bisnis, Menejemen, dan Akutansi (semua kategori)" <?php echo ($jurnal['subject_arjuna'] == 'Bisnis, Menejemen, dan Akutansi (semua kategori)') ? 'selected' : ''; ?>>Bisnis, Menejemen, dan Akutansi (semua kategori)</option>
                                    <option value="Energi (semua kategori)" <?php echo ($jurnal['subject_arjuna'] == 'Energi (semua kategori)') ? 'selected' : ''; ?>>Energi (semua kategori)</option>
                                    <option value="Fisika dan Astronomi" <?php echo ($jurnal['subject_arjuna'] == 'Fisika dan Astronomi') ? 'selected' : ''; ?>>Fisika dan Astronomi</option>
                                    <option value="Ilmu Bumi dan Planet (semua kategori)" <?php echo ($jurnal['subject_arjuna'] == 'Ilmu Bumi dan Planet (semua kategori)') ? 'selected' : ''; ?>>Ilmu Bumi dan Planet (semua kategori)</option>
                                    <option value="Ilmu Ekonomi, Ekonometrika dan Keuangan (semua kategori)" <?php echo ($jurnal['subject_arjuna'] == 'Ilmu Ekonomi, Ekonometrika dan Keuangan (semua kategori)') ? 'selected' : ''; ?>>Ilmu Ekonomi, Ekonometrika dan Keuangan (semua kategori)</option>
                                    <option value="Ilmu Komputer (semua kategori)" <?php echo ($jurnal['subject_arjuna'] == 'Ilmu Komputer (semua kategori)') ? 'selected' : ''; ?>>Ilmu Komputer (semua kategori)</option>
                                    <option value="Ilmu Lingkungan (semua ketegori)" <?php echo ($jurnal['subject_arjuna'] == 'Ilmu Lingkungan (semua ketegori)') ? 'selected' : ''; ?>>Ilmu Lingkungan (semua ketegori)</option>
                                    <option value="Ilmu Material (semua kategori)" <?php echo ($jurnal['subject_arjuna'] == 'Ilmu Material (semua kategori)') ? 'selected' : ''; ?>>Ilmu Material (semua kategori)</option>
                                    <option value="Ilmu Pengambilan Keputusan (semua kategori)" <?php echo ($jurnal['subject_arjuna'] == 'Ilmu Pengambilan Keputusan (semua kategori)') ? 'selected' : ''; ?>>Ilmu Pengambilan Keputusan (semua kategori)</option>
                                    <option value="Ilmu Pertanian dan Biologi (Semua)" <?php echo ($jurnal['subject_arjuna'] == 'Ilmu Pertanian dan Biologi (Semua)') ? 'selected' : ''; ?>>Ilmu Pertanian dan Biologi (Semua)</option>
                                    <option value="Ilmu Sosial" <?php echo ($jurnal['subject_arjuna'] == 'Ilmu Sosial') ? 'selected' : ''; ?>>Ilmu Sosial</option>
                                    <option value="Ilmu Syaraf" <?php echo ($jurnal['subject_arjuna'] == 'Ilmu Syaraf') ? 'selected' : ''; ?>>Ilmu Syaraf</option>
                                    <option value="Imunologi dan Mikrobiologi (semua kategori)" <?php echo ($jurnal['subject_arjuna'] == 'Imunologi dan Mikrobiologi (semua kategori)') ? 'selected' : ''; ?>>Imunologi dan Mikrobiologi (semua kategori)</option>
                                    <option value="Kedokteran" <?php echo ($jurnal['subject_arjuna'] == 'Kedokteran') ? 'selected' : ''; ?>>Kedokteran</option>
                                    <option value="Kedokteran Gigi" <?php echo ($jurnal['subject_arjuna'] == 'Kedokteran Gigi') ? 'selected' : ''; ?>>Kedokteran Gigi</option>
                                    <option value="Kedokteran Hewan" <?php echo ($jurnal['subject_arjuna'] == 'Kedokteran Hewan') ? 'selected' : ''; ?>>Kedokteran Hewan</option>
                                    <option value="Keperawatan" <?php echo ($jurnal['subject_arjuna'] == 'Keperawatan') ? 'selected' : ''; ?>>Keperawatan</option>
                                    <option value="Kimia(semua kategori)" <?php echo ($jurnal['subject_arjuna'] == 'Kimia(semua kategori)') ? 'selected' : ''; ?>>Kimia(semua kategori)</option>
                                    <option value="Matematika" <?php echo ($jurnal['subject_arjuna'] == 'Matematika') ? 'selected' : ''; ?>>Matematika</option>
                                    <option value="Pharmacology, Toxicology and Pharmaceutics" <?php echo ($jurnal['subject_arjuna'] == 'Pharmacology, Toxicology and Pharmaceutics') ? 'selected' : ''; ?>>Pharmacology, Toxicology and Pharmaceutics</option>
                                    <option value="Profesi Kesehatan" <?php echo ($jurnal['subject_arjuna'] == 'Profesi Kesehatan') ? 'selected' : ''; ?>>Profesi Kesehatan</option>
                                    <option value="Psikologi" <?php echo ($jurnal['subject_arjuna'] == 'Psikologi') ? 'selected' : ''; ?>>Psikologi</option>
                                    <option value="Seni dan Humaniora" <?php echo ($jurnal['subject_arjuna'] == 'Seni dan Humaniora') ? 'selected' : ''; ?>>Seni dan Humaniora</option>
                                    <option value="Teknik (semua kategori)" <?php echo ($jurnal['subject_arjuna'] == 'Teknik (semua kategori)') ? 'selected' : ''; ?>>Teknik (semua kategori)</option>
                                    <option value="Teknik Kimia (semua kategori)" <?php echo ($jurnal['subject_arjuna'] == 'Teknik Kimia (semua kategori)') ? 'selected' : ''; ?>>Teknik Kimia (semua kategori)</option>
                                    <option value="Umum" <?php echo ($jurnal['subject_arjuna'] == 'Umum') ? 'selected' : ''; ?>>Umum</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="sub_subject_arjuna">Sub Subjek Arjuna</label>
                                <select id="sub_subject_arjuna" name="sub_subject_arjuna">
                                    <option value="">Pilih Sub Subject...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Subject Area Garuda (max. 5): *</label>
                                <div class="checkbox-grid" id="garuda-subjects">
                                    <?php 
                                        $subject_garuda_array = explode(',', $jurnal['subject_garuda']);
                                        $garuda_subjects = [
                                            'Religion' => 'Religion', 'Aerospace Engineering' => 'Engineering', 'Agriculture, Biological Sciences & Forestry' => 'Agriculture', 'Arts' => 'Art',
                                            'Humanities' => 'Humanities', 'Astronomy' => 'Science', 'Automotive Engineering' => 'Engineering', 'Biochemistry, Genetics & Molecular Biology' => 'Science',
                                            'Chemical Engineering, Chemistry & Bioengineering' => 'Engineering', 'Chemistry' => 'Science', 'Civil Engineering, Building, Construction & Architecture' => 'Engineering', 'Computer Science & IT' => 'Science',
                                            'Control & Systems Engineering' => 'Engineering', 'Decision Sciences, Operations Research & Management' => 'Science', 'Dentistry' => 'Health', 'Earth & Planetary Sciences' => 'Science',
                                            'Economics, Econometrics & Finance' => 'Economy', 'Education' => 'Education', 'Electrical & Electronics Engineering' => 'Engineering', 'Energy' => 'Science',
                                            'Engineering' => 'Engineering', 'Environmental Science' => 'Social', 'Health Professions' => 'Health', 'Immunology & microbiology' => 'Science',
                                            'Industrial & Manufacturing Engineering' => 'Engineering', 'Language, Linguistic, Communication & Media' => 'Education', 'Law, Crime, Criminology & Criminal Justice' => 'Social', 'Library & Information Science' => 'Science',
                                            'Materials Science & Nanotechnology' => 'Science', 'Mathematics' => 'Education', 'Mechanical Engineering' => 'Engineering', 'Medicine & Pharmacology' => 'Health',
                                            'Neuroscience' => 'Science', 'Nursing' => 'Health', 'Physics' => 'Science', 'Public Health' => 'Health', 'Social Sciences' => 'Social',
                                            'Transportation' => 'Engineering', 'Veterinary' => 'Health', 'Other' => 'Education'
                                        ]; 
                                        foreach($garuda_subjects as $subject => $category): 
                                            $id_safe_subject = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $subject));
                                    ?>
                                        <div class="checkbox-item">
                                            <input 
                                                type="checkbox" 
                                                id="garuda_<?php echo $id_safe_subject; ?>" 
                                                name="subject_garuda[]" 
                                                value="<?php echo htmlspecialchars($subject); ?>" 
                                                class="garuda-checkbox"
                                                <?php echo in_array($subject, $subject_garuda_array) ? 'checked' : ''; ?>>
                                            <label 
                                                for="garuda_<?php echo $id_safe_subject; ?>" 
                                                class="form-checkbox-label">
                                                <?php echo htmlspecialchars($subject); ?> (<?php echo htmlspecialchars($category); ?>)
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </fieldset>
                    
                        <fieldset class="admin-fieldset">
                            <legend>📝 Tindakan Admin</legend>
                            <div class="form-group">
                                <label for="status">Ubah Status Menjadi</label>
                                <select id="status" name="status">
                                    <option value="pending" <?php echo ($jurnal['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="selesai" <?php echo ($jurnal['status'] == 'selesai') ? 'selected' : ''; ?>>Disetujui</option>
                                    <option value="ditolak" <?php echo ($jurnal['status'] == 'ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                                    <option value="butuh_edit" <?php echo ($jurnal['status'] == 'butuh_edit') ? 'selected' : ''; ?>>Butuh Edit</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="catatan_admin">Catatan untuk Pengelola (Opsional)</label>
                                <textarea id="catatan_admin" name="catatan_admin" rows="4" placeholder="Contoh: Selamat, jurnal Anda telah disetujui."><?php echo htmlspecialchars($jurnal['catatan_admin']); ?></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>
<script src="script.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('collapsed');
            
            // Script ini bisa ditambahkan jika ingin konten utama menyesuaikan margin-left
            // Namun, flexbox di admin_style.css sudah menanganinya secara otomatis
        }

        // Script untuk dropdown Subject dan Sub Subject Arjuna
        const subjectSelect = document.getElementById('subject_arjuna');
        const subSubjectSelect = document.getElementById('sub_subject_arjuna');
        const currentSubSubject = "<?php echo isset($jurnal['sub_subject_arjuna']) ? addslashes($jurnal['sub_subject_arjuna']) : ''; ?>";
        
        const subSubjectMap = {
            'Biokimia, Genetika dan Biologi Molekuler': ['Biofisika', 'Biokimia', 'Biokimia Klinis', 'Biokimia, Genetika dan Biologi Molekuler (Lain-Lain)', 'Biologi Molekuler', 'Biologi Perkembangan', 'Biologi Sel', 'Biologi Struktur', 'Bioteknologi', 'Endokrinologi', 'Fisiologi', 'Genetika', 'Kedokteran Molekuler', 'Penelitian Kanker', 'Penuaan'],
            'Bisnis, Menejemen, dan Akutansi (semua kategori)': ['Akuntansi', 'Bisnis dan Menejemen Intetrnasional', 'Bisnis, Menejemen, dan Akutansi (Lain-Lain)', 'Menejemen Teknologi dan Inovasi', 'Pemasaran', 'Perilaku Organisasi dan Menejemen Sumber Daya Manusia', 'Relasi Industri', 'Sistem Informasi Menejemen', 'Strategi dan Menejemen', 'Tourism, Leisure and Hospitality Management'],
            'Energi (semua kategori)': ['Energi (Lain-Lain)', 'Energi Terbarukan, Keberlanjutan dan Lingkungan', 'Teknik Energi dan Teknologi Daya', 'Teknik dan Energi Nuklir', 'Teknologi Bahan Bakar'],
            'Fisika dan Astronomi': ['Akustik dan Ultrasonik', 'Astronomi dan Astrofisika', 'Condensed Matter Physics', 'Fisika Nonlinear dan Statistk', 'Fisika Nuklir dan Energi Tinggi', 'Fisika Nuklir dan Molekuler, dan Ooptik', 'Fisika dan Astronomi (Lain-Lain)', 'Instrumentasi', 'Permukaan dan Interface', 'Radiasi'],
            'Ilmu Bumi dan Planet (semua kategori)': ['Geofisika', 'Geokimia danPetrologi', 'Geologi', 'Geologi Ekonomi', 'Ilmu Atmosfir', 'Ilmu Bumi dan Planet (Lain-Lain)', 'Komputer pada Ilmu Bumi', 'Oseanografi', 'Paleontologi', 'Proses Permukaan Bumi', 'Ruang Angkasa dan Ilmu Planet', 'Styraytigrafi', 'Teknik Geologi dan Geologi Teknik'],
            'Ilmu Ekonomi, Ekonometrika dan Keuangan (semua kategori)': ['Ilmu Ekomomi dan Ekonometrika', 'Ilmu Ekonomi, Ekonometrika dan Keuangan (Lain-Lain)', 'Keuangan'],
            'Ilmu Komputer (semua kategori)': ['Aplikasi Ilmu Komputer', 'Grafik Komputer dan Desain Berbantu Komputer', 'Ilmu Komputer (Lain-Lain)', 'Interaksi Komputer-Manusia', 'Jaringan Komputer dan Komunikasi', 'Kecerdasan Buatan', 'Pemrosesan Sinyal', 'Perangkat Keras dan Arsitektur', 'Perangkat Lunak', 'Sistem Informasi', 'Teori Komputasi dan Matematika', 'Visi Komputer dan Pengenalan Pola'],
            'Ilmu Lingkungan (semua ketegori)': ['Ekologi', 'Ekologi Modeling', 'Global dan Perubahan Planet', 'Ilmu Lingkungan (Lain-Lain)', 'Kesehatan, Toksikologi dan Mutasi Gen', 'Kimia Lingkungan', 'Konservasi Alam dan Lahan', 'Manajemen Limbah dan Disposal', 'Manajemen, Monitoring, Kebijakan dan Hukum', 'Polusi', 'Sain dan Teknologi Air', 'Teknik Lingkungan'],
            'Ilmu Material (semua kategori)': ['Biomaterial', 'Elektronik, Optik dan Materi Magnetik', 'Ilmu Material (Lain-Lain)', 'Keramik dan Komposit', 'Kimia Material', 'Logam dan Paduan Logam', 'Permukaan, Pelapisan dan Film', 'Polimer dan Plastik'],
            'Ilmu Pengambilan Keputusan (semua kategori)': ['Ilmu Manajemen dan Riset Operasi', 'Ilmu Pengambilan Keputusan (Lain-Lain)', 'Sistem Informasi dan Manajemen', 'Statistik, Kemungkinan dan Ketidak Pastian'],
            'Ilmu Pertanian dan Biologi (Semua)': ['Agronomi dan Ilmu Tanaman', 'Ecology, Evolution, Behavior and Systematics', 'Holtikkultura', 'Ilmu Hewan dan Zoologi', 'Ilmu Makanan', 'Ilmu Perairan', 'Ilmu Pertanian dan Biologi (Lain-Lain)', 'Ilmu Serangga', 'Ilmu Tanah', 'Ilmu Tumbuhan', 'Kehutanan'],
            'Ilmu Sosial': ['Administrasi Publik', 'Antropologi', 'Arkeologi', 'Demografi', 'Geografi, Perencanaan dan Pengembangan', 'GFaktor Manusia dan Ergonomi', 'Hukum', 'Ilmu Politik dan Hubungan Internasional', 'Ilmu Sosial (Lain-Lain)', 'Kajian Budaya', 'Kesehatan (Ilmu Sosial)', 'Komunikasi', 'Life-span and Life-course Studies', 'Linguistik dan Bahasa', 'Pendidikan', 'Perkembangan', 'Perpustakan dan Informasi', 'Riset Keselamatan', 'Sosiologi dan Ilmu Politik', 'Studi Urban', 'Stugi Gender', 'Transportasi'],
            'Ilmu Syaraf': ['Ilmu Syaraf (Lain-Lain)', 'Ilmu Syaraf Kognitif', 'Ilmu Syaraf Perilaku', 'Ilmu Syaraf Perkembangan', 'Ilmu Syaraf Seluler dan Molekuler', 'Neurologi', 'Psikiatri Biologi', 'Sistem Endokrin dan Automatis', 'Sistem Sensori'],
            'Imunologi dan Mikrobiologi (semua kategori)': ['Imunologi', 'Imunologi dan Mikrobiologi (Lain-Lain)', 'Mikrobiologi', 'Mikrobiologi dan Bioteknologi Terapan', 'Parasitologi', 'Virologi'],
            'Kedokteran': ['Anatomi', 'Anesthesiology and Pain Medicine', 'Biokimia, Kedokteran', 'Cardiology and Cardiovascular Medicine', 'Critical Care and Intensive Care Medicine', 'Dermatologi', 'Embriologi', 'Endokrinologi, Diabetes dan Metabolisme', 'Epidemiologi', 'Family (Medis)', 'Fisiologi (Medis)', 'Genetika (Klinis)', 'Geriatrics and Gerontology', 'Hematologi', 'Hepatologi', 'Histologi', 'Ilmu Pencernaan', 'Imunologi dan Alergi', 'Informatika Kesehatan', 'Kebijakan Kesehatan', 'Kedokteran (Lain-Lain)', 'Kedokteran Emergensi', 'Kedokteran Internal', 'Kedokteran Keluarga', 'Kedokteran Reproduksi dan Alternatif', 'Kedokteran Paru dan Pernafasan', 'Kedokteran Radiologi Nuklir dan Imaging', 'Kedokteran Reproduksi', 'Kesehatan Masyarakat, Kesehatan Lingkungan dan Pekerjaan', 'Mikrobiologi (Medis)', 'NTT Nephrology', 'Neurologi Klinis', 'Obstetrics and gynaecology', 'Onkologi', 'Opthalmology', 'Ortopedi dan Kedokteran Kesehatan', 'Otorhinolaryngology', 'Patologi dan Kedokteran Kesehatan', 'Pediatrics, Perinatology, and Child Health', 'Pembedahan', 'Penyakit Menular', 'Petunjuk Obat-obatan', 'Psikiatri dan Kesehatan Mental', 'Rehabilitasi', 'Review dan Referensi, Kedokteran', 'Rheumatology', 'Transplantasi', 'Urologi'],
            'Kedokteran Gigi': ['Bedah Mulut', 'Kebersihan Gigi', 'Kedokteran Gigi (Lain-Lain)', 'Ortodonti', 'Perawatan Gigi', 'Periodontik'],
            'Kedokteran Hewan': ['Equine', 'Food Animals', 'Hewan Kecil', 'Kedokteran Hewan (Lain-Lain)', 'Zoologi'],
            'Keperawatan': ['Advanced and Specialised Nursing', 'Asemen dan Diagnosis', 'Bedah Keperawatan', 'Care Planning', 'Community and Home Care', 'Critical Care', 'Farmakologi (Keperawatan)', 'Fundamental dan Ketrampilan', 'Gerontologi', 'Isu, Etik dan Aspek', 'Kedaruratan', 'Kepemimpinan dan Menejemen', 'Keperawatan (Lain-Lain)', 'Kesehatan Mental Psikitri', 'LPN dan LVN', 'Maternity and Midwifery', 'Nurse Assisting', 'Nutrition and Dietetics', 'Oncology (Keperawatan)', 'Patofisiologi', 'Pediatrik', 'Review dan Persiapan', 'Teori dan Teori'],
            'Kimia(semua kategori)': ['Inorganic Chemistry', 'Kimia (Lain-Lain)', 'Kimia Analitik', 'Kimia Fisik', 'Kimia Organik', 'Kimia Teori dan Fisik', 'Spektroskopi'],
            'Matematika': ['Aljabar dan Teori Bilangan', 'Analisis', 'Analisis Numerikal', 'Fisika Matematik', 'Geometri dan Topologi', 'Ilmu Komputer Teoritis', 'Kontrol dan Optimasi', 'Logika', 'Matematika (Lain-Lain)', 'Matematika Diskrit dan Kombinatori', 'Matematika Komputer', 'Matematika Terapan', 'Pemodelan dan Simulasi', 'Statistik dan Probabilitas'],
            'Pharmacology, Toxicology and Pharmaceutics': ['Farmakologi', 'Ilmu Farmasi', 'Penemuan Obat', 'Pharmacology, Toxicology and Pharmaceutics (Lain-Lain)', 'Toksikologi'],
            'Profesi Kesehatan': ['Bimbingan Kesehatan dan Transkripsi', 'Chiropractics', 'Farmasi', 'Menejemen Informasi Kesehatan', 'Occupational Therapy', 'Optometri', 'Pelayanan Medis Emergensi', 'Perawatan Pernafasan', 'Podiatri', 'Profesi Kesehatan (Lain-Lain)', 'Speech and Hearing', 'Teknologi Laboratorium Medis', 'Teknologi Ultrasound dan Radiologi', 'Terapi Fisik, Olah Raga dan Rehabilitasi', 'Terapi Manual dan Pelengkap', 'Terminologi Medis'],
            'Psikologi': ['Neuropsychology and Physiological Psychology', 'Psikologi (Lain-Lain)', 'Psikologi Klinis', 'Psikologi Kognitif dan Experimental', 'Psikologi Pendidikan dan perkembangan', 'Psikologi Sosial', 'Psikologi Terapan'],
            'Seni dan Humaniora': ['Arkeologi', 'Bahasa dan Linguistik', 'Filsafat', 'Klasik', 'Konservasi', 'Museology', 'Musik', 'Sastra dan Teori Sastra', 'Sejarah', 'Sejarah dan Filsafat Ilmu', 'Seni Visual dan Seni Pertunjukan', 'Seni dan Humaniora (Lain-Lain)', 'Studi Agama'],
            'Teknik (semua kategori)': ['Arsitektur', 'Bangunan dan Konstruksi', 'Keselamatan, Resiko, Reabilitas dan Kualitas', 'Komputasi Mekanik', 'Mekanik Material', 'Teknik (Lain-Lain)', 'Teknik Biomedis', 'Teknik Kelautan', 'Teknik Kontrol dan Sistem', 'Teknik Listrik dan Elektro', 'Teknik Mesin', 'Teknik Otomottif', 'Teknik Ruang Angkasa', 'Teknik Sipil dan Struktur', 'Teknologi Media', 'Ternik Industri dan Manufaktur'],
            'Teknik Kimia (semua kategori)': ['Bioengineering', 'Filtrasi dan Seperasi', 'Fluid Flow and Transfer Processes', 'Katalisis', 'Kesehatan dan Keamanan Kimia', 'Kimia Proses dan Teknologi', 'Koloid dan Kimia Permukaan', 'Teknik Kimia (Lain-Lain)'],
            'Umum': ['Umum']
        };

        function updateSubSubjects() {
            const selectedSubject = subjectSelect.value;
            const subSubjects = subSubjectMap[selectedSubject] || [];
            
            subSubjectSelect.innerHTML = '<option value="">Pilih Sub Subject...</option>';
            
            subSubjects.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub;
                option.textContent = sub;
                if (sub === currentSubSubject) {
                    option.selected = true;
                }
                subSubjectSelect.appendChild(option);
            });
        }

        subjectSelect.addEventListener('change', updateSubSubjects);
        updateSubSubjects();

        // Menunggu hingga seluruh konten halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        
        // Mengambil semua elemen checkbox untuk Subject Area Garuda
        const garudaCheckboxes = document.querySelectorAll('.garuda-checkbox');
        
        // Array untuk melacak urutan checkbox yang dicentang
        let selectedGaruda = [];
        const maxSelection = 5;

        // Menambahkan event listener ke setiap checkbox
        garudaCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                
                // Jika checkbox dicentang
                if (this.checked) {
                    // Tambahkan ID checkbox ke dalam array pelacak
                    selectedGaruda.push(this.id);
                    
                    // Jika jumlah yang dicentang melebihi batas maksimal
                    if (selectedGaruda.length > maxSelection) {
                        // Ambil ID checkbox pertama yang dicentang
                        const firstSelectedId = selectedGaruda.shift(); // Ambil dan hapus elemen pertama
                        
                        // Dapatkan elemen checkbox tersebut dan hapus centangnya
                        document.getElementById(firstSelectedId).checked = false;
                    }
                } else {
                    // Jika centang dihilangkan, hapus ID checkbox dari array pelacak
                    selectedGaruda = selectedGaruda.filter(id => id !== this.id);
                }
            });
        });
    });
    </script>
</body>
</html>
<?php
$conn->close();
?>