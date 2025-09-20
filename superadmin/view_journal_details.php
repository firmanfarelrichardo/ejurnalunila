<?php
// Mulai atau lanjutkan sesi
session_start();

// Periksa apakah pengguna sudah login dan memiliki peran superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

// Konfigurasi database MySQL
require_once '../database/config.php';
$conn = connect_to_database();

$submissionDetails = null;
$message = "";

if (isset($_GET['id'])) {
    $submissionId = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM jurnal_submissions WHERE id = ?");
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $submissionDetails = $result->fetch_assoc();
    $stmt->close();
}

// Logika update jurnal_sumber (proses kelengkapan)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_jurnal_sumber'])) {
    $submissionId = $_POST['submission_id'];
    $pengelola_id = $_POST['pengelola_id'];
    $nama_kontak = $_POST['nama_kontak'];
    $email_kontak = $_POST['email_kontak'];
    $institusi = $_POST['institusi'];
    $judul_jurnal_asli = $_POST['judul_jurnal_asli'];
    $judul_jurnal = $_POST['judul_jurnal'];
    $doi = $_POST['doi'];
    $journal_type = $_POST['journal_type'];
    $p_issn = $_POST['p_issn'];
    $e_issn = $_POST['e_issn'];
    $penerbit = $_POST['penerbit'];
    $country_of_publisher = $_POST['country_of_publisher'];
    $website_url = $_POST['website_url'];
    $journal_contact_phone = $_POST['journal_contact_phone'];
    $start_year = $_POST['start_year'];
    $issue_period = $_POST['issue_period'];
    $editorial_address = $_POST['editorial_address'];
    $aim_and_scope = $_POST['aim_and_scope'];
    $has_homepage = isset($_POST['has_homepage']) ? 1 : 0;
    $is_using_ojs = isset($_POST['is_using_ojs']) ? 1 : 0;
    $ojs_link = $_POST['ojs_link'];
    $open_access_link = $_POST['open_access_link'];
    $url_editorial_board = $_POST['url_editorial_board'];
    $url_contact = $_POST['url_contact'];
    $url_reviewer = $_POST['url_reviewer'];
    $url_google_scholar = $_POST['url_google_scholar'];
    $url_cover = $_POST['url_cover'];
    $link_sinta = $_POST['link_sinta'];
    $link_garuda = $_POST['link_garuda'];
    $link_oai = $_POST['link_oai']; // Kolom tambahan
    $subject_arjuna = $_POST['subject_arjuna'];
    $sub_subject_arjuna = $_POST['sub_subject_arjuna'];
    $subject_garuda = $_POST['subject_garuda'];
    $akreditasi_sinta = $_POST['akreditasi_sinta'];
    $index_scopus = $_POST['index_scopus'];
    $fakultas = $_POST['fakultas'];
    $editorial_team = $_POST['editorial_team'];
    $status = $_POST['status'];


    $stmt = $conn->prepare("INSERT INTO jurnal_sumber (pengelola_id, nama_kontak, email_kontak, institusi, judul_jurnal_asli, judul_jurnal, doi, journal_type, p_issn, e_issn, penerbit, country_of_publisher, website_url, journal_contact_phone, start_year, issue_period, editorial_address, aim_and_scope, has_homepage, is_using_ojs, ojs_link, open_access_link, url_editorial_board, url_contact, url_reviewer, url_google_scholar, url_cover, link_sinta, link_garuda, link_oai, subject_arjuna, sub_subject_arjuna, subject_garuda, akreditasi_sinta, index_scopus, fakultas, editorial_team, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssssssssissssssssssssssssssssi", 
        $pengelola_id, $nama_kontak, $email_kontak, $institusi, $judul_jurnal_asli, 
        $judul_jurnal, $doi, $journal_type, $p_issn, $e_issn, $penerbit, 
        $country_of_publisher, $website_url, $journal_contact_phone, $start_year, 
        $issue_period, $editorial_address, $aim_and_scope, $has_homepage, 
        $is_using_ojs, $ojs_link, $open_access_link, $url_editorial_board, 
        $url_contact, $url_reviewer, $url_google_scholar, $url_cover, 
        $link_sinta, $link_garuda, $link_oai, $subject_arjuna, $sub_subject_arjuna, 
        $subject_garuda, $akreditasi_sinta, $index_scopus, $fakultas, $editorial_team, $status
    );
    
    if ($stmt->execute()) {
        // Hapus dari jurnal_submissions setelah selesai
        $stmt_delete = $conn->prepare("DELETE FROM jurnal_submissions WHERE id = ?");
        $stmt_delete->bind_param("i", $submissionId);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        $message = "<div class='success-message'>Jurnal berhasil diselesaikan dan ditambahkan ke database sumber!</div>";
    } else {
        $message = "<div class='error-message'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Ambil daftar pengelola untuk dropdown
$pengelolaList = [];
$result = $conn->query("SELECT id, nama, nip FROM users WHERE role = 'pengelola'");
while ($row = $result->fetch_assoc()) {
    $pengelolaList[] = $row;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Jurnal - Superadmin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .detail-card {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .detail-card h3 {
            margin-top: 0;
            color: #3498db;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .detail-card p {
            margin: 10px 0;
        }
        .form-kelengkapan label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        .form-kelengkapan input, .form-kelengkapan select, .form-kelengkapan textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-kelengkapan button {
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-kelengkapan button:hover {
            background-color: #27ae60;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-weight: bold;
            color: white;
        }
        .status-pending { background-color: #f39c12; }
        .status-approved { background-color: #2ecc71; }
        .status-rejected { background-color: #e74c3c; }
        .status-needs_edit { background-color: #3498db; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>Superadmin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_superadmin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_admin.php"><i class="fas fa-user-shield"></i> Kelola Admin</a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> Kelola Pengelola</a></li>
                <li><a href="manage_jurnal_status.php" class="active"><i class="fas fa-book"></i> Kelola Jurnal</a></li>
                <li><a href="change_password.php"><i class="fas fa-key"></i> Ganti Password</a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <!-- End Sidebar -->

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Detail Jurnal Submission</h1>
                <div class="user-profile">
                    <span>Role: Superadmin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <?php if ($submissionDetails): ?>
                    <div class="detail-card">
                        <h3>Informasi Submission</h3>
                        <p><strong>Judul Jurnal:</strong> <?php echo htmlspecialchars($submissionDetails['journal_title']); ?></p>
                        <p><strong>Nama Kontak:</strong> <?php echo htmlspecialchars($submissionDetails['contact_name']); ?></p>
                        <p><strong>Email Kontak:</strong> <?php echo htmlspecialchars($submissionDetails['contact_email']); ?></p>
                        <p><strong>Fakultas:</strong> <?php echo htmlspecialchars($submissionDetails['fakultas']); ?></p>
                        <p><strong>URL Website:</strong> <a href="<?php echo htmlspecialchars($submissionDetails['journal_website_url']); ?>"><?php echo htmlspecialchars($submissionDetails['journal_website_url']); ?></a></p>
                        <p><strong>Status:</strong> <span class="status-badge status-<?php echo str_replace(' ', '_', $submissionDetails['status']); ?>"><?php echo htmlspecialchars($submissionDetails['status']); ?></span></p>
                    </div>

                    <div class="form-kelengkapan detail-card">
                        <h3>Input Data Lengkap Jurnal Sumber</h3>
                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin data ini sudah lengkap dan akan ditambahkan ke Jurnal Sumber?');">
                            <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($submissionDetails['id']); ?>">
                            
                            <label for="pengelola_id">Pilih Pengelola:</label>
                            <select name="pengelola_id" required>
                                <?php foreach ($pengelolaList as $pengelola): ?>
                                    <option value="<?php echo htmlspecialchars($pengelola['id']); ?>"><?php echo htmlspecialchars($pengelola['nama']) . ' (' . htmlspecialchars($pengelola['nip']) . ')'; ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label for="nama_kontak">Nama Kontak:</label>
                            <input type="text" name="nama_kontak" value="<?php echo htmlspecialchars($submissionDetails['contact_name']); ?>" required>

                            <label for="email_kontak">Email Kontak:</label>
                            <input type="email" name="email_kontak" value="<?php echo htmlspecialchars($submissionDetails['contact_email']); ?>" required>
                            
                            <!-- Sisanya dari kolom-kolom tabel jurnal_sumber -->
                            <!-- Untuk mempersingkat, asumsikan input di bawah ini sudah diisi dari submission awal dan hanya perlu divalidasi/disesuaikan -->
                            <label for="institusi">Institusi:</label>
                            <input type="text" name="institusi" required>

                            <label for="judul_jurnal_asli">Judul Jurnal Asli:</label>
                            <input type="text" name="judul_jurnal_asli" value="<?php echo htmlspecialchars($submissionDetails['journal_title']); ?>" required>

                            <label for="judul_jurnal">Judul Jurnal (diindeks):</label>
                            <input type="text" name="judul_jurnal" required>

                            <label for="doi">DOI:</label>
                            <input type="text" name="doi" required>

                            <label for="journal_type">Tipe Jurnal:</label>
                            <select name="journal_type" required>
                                <option value="Journal">Journal</option>
                                <option value="Conference">Conference</option>
                            </select>

                            <label for="p_issn">P-ISSN:</label>
                            <input type="text" name="p_issn" required>

                            <label for="e_issn">E-ISSN:</label>
                            <input type="text" name="e_issn" required>

                            <label for="penerbit">Penerbit:</label>
                            <input type="text" name="penerbit" required>

                            <label for="country_of_publisher">Negara Penerbit:</label>
                            <input type="text" name="country_of_publisher" value="Indonesia (ID)" required>

                            <label for="website_url">Website URL:</label>
                            <input type="text" name="website_url" value="<?php echo htmlspecialchars($submissionDetails['journal_website_url']); ?>" required>

                            <label for="journal_contact_phone">Nomor Telepon Kontak Jurnal:</label>
                            <input type="text" name="journal_contact_phone" required>

                            <label for="start_year">Tahun Mulai:</label>
                            <input type="text" name="start_year" required>

                            <label for="issue_period">Periode Terbit:</label>
                            <input type="text" name="issue_period" required>

                            <label for="editorial_address">Alamat Editorial:</label>
                            <textarea name="editorial_address" rows="4" required></textarea>

                            <label for="aim_and_scope">Tujuan dan Lingkup:</label>
                            <textarea name="aim_and_scope" rows="4" required></textarea>
                            
                            <label for="has_homepage">Ada Halaman Utama?:</label>
                            <input type="checkbox" name="has_homepage" value="1" checked>

                            <label for="is_using_ojs">Menggunakan OJS?:</label>
                            <input type="checkbox" name="is_using_ojs" value="1">

                            <label for="ojs_link">OJS Link:</label>
                            <input type="text" name="ojs_link">

                            <label for="open_access_link">Open Access Link:</label>
                            <input type="text" name="open_access_link" required>

                            <label for="url_editorial_board">URL Dewan Redaksi:</label>
                            <input type="text" name="url_editorial_board" required>

                            <label for="url_contact">URL Kontak:</label>
                            <input type="text" name="url_contact" required>

                            <label for="url_reviewer">URL Reviewer:</label>
                            <input type="text" name="url_reviewer" required>

                            <label for="url_google_scholar">URL Google Scholar:</label>
                            <input type="text" name="url_google_scholar" required>

                            <label for="url_cover">URL Cover:</label>
                            <input type="text" name="url_cover" required>

                            <label for="link_sinta">Link Sinta:</label>
                            <input type="text" name="link_sinta" required>

                            <label for="link_garuda">Link Garuda:</label>
                            <input type="text" name="link_garuda" required>

                            <label for="link_oai">Link OAI (Input Manual):</label>
                            <input type="text" name="link_oai" required>

                            <label for="subject_arjuna">Subjek Arjuna:</label>
                            <input type="text" name="subject_arjuna" required>

                            <label for="sub_subject_arjuna">Subjek Sekunder Arjuna:</label>
                            <input type="text" name="sub_subject_arjuna" required>

                            <label for="subject_garuda">Subjek Garuda:</label>
                            <textarea name="subject_garuda" rows="4" required></textarea>

                            <label for="akreditasi_sinta">Akreditasi Sinta:</label>
                            <select name="akreditasi_sinta" required>
                                <option value="Belum Terakreditasi">Belum Terakreditasi</option>
                                <option value="Sinta 1">Sinta 1</option>
                                <option value="Sinta 2">Sinta 2</option>
                                <option value="Sinta 3">Sinta 3</option>
                                <option value="Sinta 4">Sinta 4</option>
                                <option value="Sinta 5">Sinta 5</option>
                                <option value="Sinta 6">Sinta 6</option>
                            </select>

                            <label for="index_scopus">Indeks Scopus:</label>
                            <select name="index_scopus" required>
                                <option value="Belum Terindeks">Belum Terindeks</option>
                                <option value="Q1">Q1</option>
                                <option value="Q2">Q2</option>
                                <option value="Q3">Q3</option>
                                <option value="Q4">Q4</option>
                            </select>

                            <label for="fakultas">Fakultas:</label>
                            <select name="fakultas" required>
                                <option value="Fakultas Ekonomi dan Bisnis">Fakultas Ekonomi dan Bisnis</option>
                                <option value="Fakultas Hukum">Fakultas Hukum</option>
                                <option value="Fakultas Ilmu Sosial dan Ilmu Politik">Fakultas Ilmu Sosial dan Ilmu Politik</option>
                                <option value="Fakultas Kedokteran">Fakultas Kedokteran</option>
                                <option value="Fakultas Keguruan dan Ilmu Pendidikan">Fakultas Keguruan dan Ilmu Pendidikan</option>
                                <option value="Fakultas Matematika dan Ilmu Pengetahuan Alam">Fakultas Matematika dan Ilmu Pengetahuan Alam</option>
                                <option value="Fakultas Pertanian">Fakultas Pertanian</option>
                                <option value="Fakultas Teknik">Fakultas Teknik</option>
                            </select>
                            
                            <label for="editorial_team">Editorial Team:</label>
                            <textarea name="editorial_team" rows="4" required></textarea>
                            
                            <input type="hidden" name="status" value="selesai">
                            <button type="submit" name="update_jurnal_sumber">Selesaikan & Simpan</button>
                        </form>
                    </div>
                <?php else: ?>
                    <p>Submission tidak ditemukan.</p>
                <?php endif; ?>
            </div>
        </div>
        <!-- End Main Content -->
    </div>
</body>
</html>
<?php
$conn->close();
?>
