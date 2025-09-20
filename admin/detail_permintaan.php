<?php
// admin/detail_permintaan.php
session_start();
// Cek sesi admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Koneksi database
require_once '../database/config.php';
$conn = connect_to_database();

// (DIPERBARUI) Logika untuk menangani aksi approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action']; // 'approve' atau 'reject'
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';

    $stmt_update = $conn->prepare("UPDATE submission_requests SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $new_status, $request_id);
    if ($stmt_update->execute()) {
        $_SESSION['success_message'] = "Permintaan telah berhasil direspons.";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui status permintaan.";
    }
    $stmt_update->close();

    // Redirect kembali ke halaman yang sama untuk me-refresh data
    header("Location: detail_permintaan.php?id=" . $request_id);
    exit();
}


$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$request = null;

if ($request_id > 0) {
    // Query yang disederhanakan
    $sql = "SELECT sr.id, sr.jurnal_id, sr.request_type, sr.status, sr.alasan, sr.created_at, 
                   j.judul_jurnal, u.nama AS pengelola_nama
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
    <style>
        /* (DIPERBARUI) CSS Tambahan untuk Halaman Detail Permintaan */
        .detail-card {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .detail-header h2 {
            margin: 0;
            font-size: 20px;
            color: #2c3e50;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #ecf0f1;
            color: #2c3e50;
            padding: 10px 18px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            border: 1px solid #bdc3c7;
            transition: all 0.3s;
        }
        .btn-back:hover {
            background-color: #dcdde1;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px 20px;
            align-items: center;
        }
        .detail-grid .label {
            font-weight: 600;
            color: #555;
        }
        .detail-grid .value {
            word-break: break-word;
        }
        .value.status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
            font-size: 12px;
        }
        .reason-box {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
            margin-top: 5px;
        }
        .actions-card {
            margin-top: 25px;
            border-top: 1px solid #e0e0e0;
            padding-top: 20px;
        }
        .actions-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
        }
        .btn-action {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            margin-right: 10px;
            color: white;
        }
        .btn-approve { background-color: #2ecc71; }
        .btn-approve:hover { background-color: #27ae60; transform: translateY(-2px); }
        .btn-reject { background-color: #e74c3c; }
        .btn-reject:hover { background-color: #c0392b; transform: translateY(-2px); }
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
                    <img src="../assets/unila_logo.png" alt="Logo Universitas Lampung">
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php" class="active"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
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
                <?php
                // Notifikasi untuk update status
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                    unset($_SESSION['error_message']);
                }
                ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Informasi Permintaan</h2>
                        <a href="tinjau_permintaan.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                    
                    <?php if ($request): ?>
                        <div class="detail-grid">
                            <div class="label">Jurnal Terkait:</div>
                            <div class="value"><?php echo htmlspecialchars($request['judul_jurnal'] ?: '-'); ?></div>

                            <div class="label">Jenis Permintaan:</div>
                            <div class="value"><?php echo htmlspecialchars(ucfirst($request['request_type'])); ?></div>

                            <div class="label">Diajukan Oleh:</div>
                            <div class="value"><?php echo htmlspecialchars($request['pengelola_nama']); ?></div>

                            <div class="label">Tanggal Diajukan:</div>
                            <div class="value"><?php echo date('d F Y, H:i:s', strtotime($request['created_at'])); ?></div>

                            <div class="label">Status Saat Ini:</div>
                            <div class="value">
                                <span class="status-badge status-<?php echo strtolower($request['status']); ?>"><?php echo htmlspecialchars(ucfirst($request['status'])); ?></span>
                            </div>
                            
                            <div class="label">Alasan / Keterangan:</div>
                            <div class="value reason-box"><?php echo nl2br(htmlspecialchars($request['alasan'])); ?></div>
                        </div>

                        <?php if ($request['status'] == 'pending'): ?>
                            <div class="actions-card">
                                <h3>Ambil Tindakan</h3>
                                <p>Silakan setujui atau tolak permintaan ini. Tindakan ini tidak dapat diurungkan.</p>
                                <form action="detail_permintaan.php?id=<?php echo $request_id; ?>" method="POST">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                    <button type="submit" name="action" value="approve" class="btn-action btn-approve"><i class="fas fa-check"></i> Setujui</button>
                                    <button type="submit" name="action" value="reject" class="btn-action btn-reject"><i class="fas fa-times"></i> Tolak</button>
                                </form>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <p>Permintaan tidak ditemukan atau ID tidak valid.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<script>
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
<?php
$conn->close();
?>