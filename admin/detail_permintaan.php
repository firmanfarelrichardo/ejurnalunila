<?php
// admin/detail_permintaan.php
session_start();
// Cek apakah pengguna sudah login dan memiliki peran yang sesuai
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Pengaturan Database
$host = "localhost";
$user = "root";
$pass = "";
$db = "oai";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$request = null;

if ($request_id > 0) {
    // Ambil detail permintaan dan data jurnal terkait
    $sql = "SELECT sr.*, j.*, u.nama AS pengelola_nama
            FROM submission_requests sr
            LEFT JOIN jurnal_sumber j ON sr.jurnal_id = j.id
            LEFT JOIN users u ON sr.pengelola_id = u.id
            WHERE sr.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Permintaan - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Gaya dari detail_jurnal.php */
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
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
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
        @media (max-width: 768px) {
            .detail-label, .detail-value {
                flex-basis: 100%;
            }
            .detail-label {
                margin-bottom: 5px;
                font-weight: 600;
            }
        }
        /* Gaya tambahan */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
        }
        .status-pending { background-color: #f39c12; }
        .status-approved { background-color: #2ecc71; }
        .status-rejected { background-color: #e74c3c; }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            text-decoration: none;
        }
        .btn-success { background-color: #2ecc71; color: white; }
        .btn-danger { background-color: #e74c3c; color: white; }
        .btn-success:hover { background-color: #27ae60; }
        .btn-danger:hover { background-color: #c0392b; }
        .btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo"><h2>Admin</h2></div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> Kelola Pengelola</a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php" class="active"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="header">
                <h1>Detail Permintaan</h1>
                <div class="user-profile">
                    <span>Role: Admin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <div class="content-panel">
                    <div class="panel-header">
                        <a href="tinjau_permintaan.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i><span>Kembali ke Daftar</span>
                        </a>
                        <h1>Detail Permintaan</h1>
                    </div>
                    
                    <?php if ($request): ?>
                        <h2 class="journal-title">Jurnal: <?php echo htmlspecialchars($request['judul_jurnal'] ?: '-'); ?></h2>

                        <fieldset>
                            <legend>Informasi Permintaan</legend>
                            <div class="detail-group">
                                <span class="detail-label">Jenis Permintaan:</span>
                                <span class="detail-value">
                                    <?php echo htmlspecialchars(ucfirst($request['request_type'])); ?>
                                </span>
                            </div>
                            <div class="detail-group">
                                <span class="detail-label">Alasan / Keterangan:</span>
                                <span class="detail-value" style="white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($request['alasan'])); ?></span>
                            </div>
                            <div class="detail-group">
                                <span class="detail-label">Diajukan Oleh:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($request['pengelola_nama']); ?></span>
                            </div>
                            <div class="detail-group">
                                <span class="detail-label">Tanggal Diajukan:</span>
                                <span class="detail-value"><?php echo date('d F Y, H:i', strtotime($request['created_at'])); ?></span>
                            </div>
                            <div class="detail-group">
                                <span class="detail-label">Status Saat Ini:</span>
                                <span class="detail-value">
                                    <span class="status-badge status-<?php echo strtolower($request['status']); ?>"><?php echo htmlspecialchars(ucfirst($request['status'])); ?></span>
                                </span>
                            </div>
                        </fieldset>

                        <?php if ($request['request_type'] == 'edit'): ?>
                            <fieldset>
                                <legend>Contact Detail</legend>
                                <div class="detail-group"><span class="detail-label">Nama Kontak:</span><span class="detail-value"><?php echo htmlspecialchars($request['nama_kontak'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Email Kontak:</span><span class="detail-value"><?php echo htmlspecialchars($request['email_kontak'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Institusi:</span><span class="detail-value"><?php echo htmlspecialchars($request['institusi'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Fakultas:</span><span class="detail-value"><?php echo htmlspecialchars($request['fakultas'] ?: '-'); ?></span></div>
                            </fieldset>

                            <fieldset>
                                <legend>Journal Information</legend>
                                <div class="detail-group"><span class="detail-label">Judul Asli:</span><span class="detail-value"><?php echo htmlspecialchars($request['judul_jurnal_asli'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Judul:</span><span class="detail-value"><?php echo htmlspecialchars($request['judul_jurnal'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">DOI:</span><span class="detail-value"><?php echo htmlspecialchars($request['doi'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Tipe:</span><span class="detail-value"><?php echo htmlspecialchars($request['journal_type'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">ISSN (Cetak):</span><span class="detail-value"><?php echo htmlspecialchars($request['p_issn'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">e-ISSN (Online):</span><span class="detail-value"><?php echo htmlspecialchars($request['e_issn'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Akreditasi SINTA:</span><span class="detail-value"><?php echo htmlspecialchars($request['akreditasi_sinta'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Indeks Scopus:</span><span class="detail-value"><?php echo htmlspecialchars($request['index_scopus'] ?: '-'); ?></span></div>
                            </fieldset>

                            <fieldset>
                                <legend>Publisher & Journal Contact</legend>
                                <div class="detail-group"><span class="detail-label">Penerbit:</span><span class="detail-value"><?php echo htmlspecialchars($request['penerbit'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Negara Penerbit:</span><span class="detail-value"><?php echo htmlspecialchars($request['country_of_publisher'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Website Jurnal:</span><span class="detail-value"><a href="<?php echo htmlspecialchars($request['website_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($request['website_url'] ?: '-'); ?></a></span></div>
                                <div class="detail-group"><span class="detail-label">Nama Kontak Jurnal:</span><span class="detail-value"><?php echo htmlspecialchars($request['journal_contact_name'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Email Resmi Jurnal:</span><span class="detail-value"><?php echo htmlspecialchars($request['journal_official_email'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Telepon Kontak Jurnal:</span><span class="detail-value"><?php echo htmlspecialchars($request['journal_contact_phone'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Tahun Mulai Online:</span><span class="detail-value"><?php echo htmlspecialchars($request['start_year'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Periode Terbit:</span><span class="detail-value"><?php echo htmlspecialchars(str_replace(',', ', ', $request['issue_period'] ?: '-')); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Tim Editor:</span><span class="detail-value"><?php echo nl2br(htmlspecialchars($request['editorial_team'] ?: '-')); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Alamat Editorial:</span><span class="detail-value"><?php echo nl2br(htmlspecialchars($request['editorial_address'] ?: '-')); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Aim dan Scope:</span><span class="detail-value"><?php echo nl2br(htmlspecialchars($request['aim_and_scope'] ?: '-')); ?></span></div>
                            </fieldset>
                            
                            <fieldset>
                                <legend>Additional Information & URLs</legend>
                                <div class="detail-group"><span class="detail-label">Memiliki Homepage?:</span><span class="detail-value"><?php echo $request['has_homepage'] ? 'Ya' : 'Tidak'; ?></span></div>
                                <div class="detail-group"><span class="detail-label">Menggunakan OJS?:</span><span class="detail-value"><?php echo $request['is_using_ojs'] ? 'Ya' : 'Tidak'; ?></span></div>
                                <div class="detail-group"><span class="detail-label">Link OJS:</span><span class="detail-value"><?php echo htmlspecialchars($request['ojs_link'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Link Open Access:</span><span class="detail-value"><?php echo htmlspecialchars($request['open_access_link'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">URL Editorial Board:</span><span class="detail-value"><?php echo htmlspecialchars($request['url_editorial_board'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">URL Kontak:</span><span class="detail-value"><?php echo htmlspecialchars($request['url_contact'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">URL Reviewer:</span><span class="detail-value"><?php echo htmlspecialchars($request['url_reviewer'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">URL Google Scholar:</span><span class="detail-value"><?php echo htmlspecialchars($request['url_google_scholar'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">URL Sinta:</span><span class="detail-value"><?php echo htmlspecialchars($request['link_sinta'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">URL Garuda:</span><span class="detail-value"><?php echo htmlspecialchars($request['link_garuda'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">URL Cover:</span><span class="detail-value"><?php echo htmlspecialchars($request['url_cover'] ?: '-'); ?></span></div>
                            </fieldset>

                            <fieldset>
                                <legend>Subject Area</legend>
                                <div class="detail-group"><span class="detail-label">Subjek Arjuna:</span><span class="detail-value"><?php echo htmlspecialchars($request['subject_arjuna'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Sub-subjek Arjuna:</span><span class="detail-value"><?php echo htmlspecialchars($request['sub_subject_arjuna'] ?: '-'); ?></span></div>
                                <div class="detail-group"><span class="detail-label">Subjek Garuda:</span><span class="detail-value"><?php echo htmlspecialchars(str_replace(',', ', ', $request['subject_garuda'] ?: '-')); ?></span></div>
                            </fieldset>
                        <?php endif; ?>

                        <?php if ($request['status'] == 'pending'): ?>
                            <fieldset>
                                <legend>Ambil Tindakan</legend>
                                <p>Silakan setujui atau tolak permintaan ini. Tindakan ini tidak dapat diurungkan.</p>
                                <form action="tinjau_permintaan.php" method="POST" class="form-actions" style="justify-content: flex-start;">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                    <input type="hidden" name="jurnal_id" value="<?php echo htmlspecialchars($request['jurnal_id']); ?>">
                                    <input type="hidden" name="request_type" value="<?php echo htmlspecialchars($request['request_type']); ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success">✅ Setujui Permintaan</button>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger">❌ Tolak Permintaan</button>
                                </form>
                            </fieldset>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="content-panel">
                            <p>Permintaan tidak ditemukan atau ID tidak valid.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>