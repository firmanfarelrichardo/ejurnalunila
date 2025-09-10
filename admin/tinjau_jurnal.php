<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki peran yang sesuai
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Jika tidak, arahkan ke halaman login
    header("Location: login.php");
    exit();
}

// Pengaturan Database MySQL
$host = "localhost";
$user = "root";
$pass = "";
$db = "oai";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) { 
    die("Koneksi gagal: " . $conn->connect_error); 
}

// BAGIAN 1: PROSES FORM JIKA ADA DATA YANG DI-POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $jurnal_id = (int)$_POST['jurnal_id'];
    $new_status = $_POST['status'];
    $catatan = trim($_POST['catatan_admin']);
    
    // Validasi status
    $allowed_statuses = ['pending', 'selesai', 'ditolak', 'butuh_edit'];
    if (in_array($new_status, $allowed_statuses)) {
        
        // Query untuk memperbarui status DAN catatan admin di tabel jurnal_sumber
        $sql_update = "UPDATE jurnal_sumber SET status = ?, catatan_admin = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        
        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "ssi", $new_status, $catatan, $jurnal_id);
            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['success_message'] = "Status jurnal berhasil diperbarui.";
            } else {
                $_SESSION['error_message'] = "Gagal memperbarui status: " . mysqli_stmt_error($stmt_update);
            }
            mysqli_stmt_close($stmt_update);
        } else {
             $_SESSION['error_message'] = "Gagal menyiapkan statement: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error_message'] = "Status tidak valid.";
    }
    
    header("Location: manage_journal.php");
    exit();
}

// BAGIAN 2: TAMPILKAN DATA JURNAL (GET REQUEST)
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: index.php");
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
    header("Location: index.php");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>

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

    .status-needs-edit {
        background-color: #9b59b6; /* Ungu */
        color: white;
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
    
    .detail-group {
        display: flex;
        flex-wrap: wrap;
        padding: 12px 0;
        border-bottom: 1px solid #f1f1f1;
        font-size: 14px;
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
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }
    
    .form-group select, .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    
    .form-actions {
        margin-top: 20px;
        display: flex;
        justify-content: flex-end;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.3s, transform 0.2s;
    }

    .btn-primary {
        background-color: #3498db;
        color: white;
    }
    
</style>

<body>
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <h2>Admin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> Kelola Pengelola</a></li>
                <li><a href="manage_journal.php" class="active"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
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
                        <div class="detail-group"><span class="detail-label">Judul :</span><span class="detail-value"><?php echo htmlspecialchars($jurnal['judul_jurnal']); ?></span></div>
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
                
                    <fieldset class="admin-fieldset">
                        <legend>📝 Tindakan Admin</legend>
                        <form action="tinjau_jurnal.php" method="POST">
                            <input type="hidden" name="jurnal_id" value="<?php echo $jurnal['id']; ?>">
                            
                            <div class="form-group">
                                <label for="status">Ubah Status Menjadi</label>
                                <select id="status" name="status">
                                    <option value="pending" <?php echo ($jurnal['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="selesai" <?php echo ($jurnal['status'] == 'selesai') ? 'selected' : ''; ?>>Approved</option>
                                    <option value="ditolak" <?php echo ($jurnal['status'] == 'ditolak') ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="butuh_edit" <?php echo ($jurnal['status'] == 'butuh_edit') ? 'selected' : ''; ?>>Needs Edit</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="catatan_admin">Catatan untuk Pengelola (Opsional)</label>
                                <textarea id="catatan_admin" name="catatan_admin" rows="4" placeholder="Contoh: Selamat, jurnal Anda telah disetujui."><?php echo htmlspecialchars($jurnal['catatan_admin']); ?></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Simpan Perubahan Status</button>
                            </div>
                        </form>
                    </fieldset>
                    
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>