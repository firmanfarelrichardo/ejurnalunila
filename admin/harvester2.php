<?php
// admin/harvester.php
session_start();

// Cek sesi admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db = "oai";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { 
    die("Koneksi gagal: " . $conn->connect_error); 
}

// Bagian ini menangani request AJAX untuk menjalankan proses harvesting
if (isset($_POST['action']) && $_POST['action'] == 'run_harvest' && isset($_POST['jurnal_id'])) {
    header('Content-Type: application/json');
    $jurnal_id = (int)$_POST['jurnal_id'];

    $stmt = $conn->prepare("SELECT link_oai FROM jurnal_sumber WHERE id = ?");
    $stmt->bind_param("i", $jurnal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $jurnal = $result->fetch_assoc();
    $stmt->close();

    if (!$jurnal || empty($jurnal['link_oai'])) {
        echo json_encode(['success' => false, 'message' => 'Link OAI tidak ditemukan.']);
        exit();
    }
    
    // Logika harvester (disederhanakan untuk contoh, sesuaikan dengan implementasi lengkapmu)
    // Di sini Anda akan meletakkan logika file_get_contents, parsing XML, dan memasukkan ke database
    // Untuk simulasi, kita akan anggap prosesnya berhasil.
    sleep(2); // Simulasi proses yang memakan waktu
    $new_articles = rand(5, 50);
    $skipped_articles = rand(0, 10);

    // Dapatkan data terbaru setelah harvest
    $check_harvest = $conn->prepare("SELECT COUNT(*) as count, MAX(created_at) as last_harvest FROM artikel_oai WHERE journal_source_id = ?");
    $check_harvest->bind_param("i", $jurnal_id);
    $check_harvest->execute();
    $harvest_result = $check_harvest->get_result()->fetch_assoc();
    $check_harvest->close();

    echo json_encode([
        'success' => true,
        'message' => "Panen selesai! $new_articles artikel baru ditambahkan, $skipped_articles dilewati.",
        'article_count' => $harvest_result['count'],
        'last_harvest' => $harvest_result['last_harvest'] ? date('d M Y, H:i:s', strtotime($harvest_result['last_harvest'])) : 'Belum Pernah'
    ]);
    exit();
}

// Ambil daftar jurnal untuk ditampilkan di halaman
$jurnal_list = [];
$sql = "SELECT id, judul_jurnal, link_oai, status FROM jurnal_sumber WHERE link_oai IS NOT NULL AND link_oai != ''";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $check_harvest = $conn->prepare("SELECT COUNT(*) as count, MAX(created_at) as last_harvest FROM artikel_oai WHERE journal_source_id = ?");
        $check_harvest->bind_param("i", $row['id']);
        $check_harvest->execute();
        $harvest_result = $check_harvest->get_result()->fetch_assoc();
        $row['article_count'] = $harvest_result['count'];
        $row['last_harvest'] = $harvest_result['last_harvest'];
        $jurnal_list[] = $row;
        $check_harvest->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jalankan Harvester - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .harvester-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .harvester-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .harvester-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 25px;
            flex-grow: 1;
        }

        .card-title {
            font-size: 1.25em;
            font-weight: 600;
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .card-oai-link {
            font-size: 0.9em;
            color: #3498db;
            text-decoration: none;
            word-break: break-all;
            display: inline-block;
            margin-bottom: 20px;
        }
        .card-oai-link:hover {
            text-decoration: underline;
        }

        .card-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        .stat-item {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        .stat-item .label {
            display: block;
            color: #7f8c8d;
            font-size: 0.8em;
            margin-bottom: 5px;
        }
        .stat-item .value {
            font-weight: 600;
            color: #34495e;
        }
        
        .card-footer {
            background-color: #f8f9fa;
            padding: 20px 25px;
            border-top: 1px solid #e0e0e0;
            border-radius: 0 0 12px 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .btn-harvest {
            width: 100%;
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
        }
        .btn-harvest:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(46, 204, 113, 0.4);
        }
        .btn-harvest:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            box-shadow: none;
        }

        .status-area {
            margin-top: 15px;
            font-size: 0.9em;
            text-align: center;
            min-height: 20px;
        }
        .status-loading i {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .status-success { color: #27ae60; font-weight: 500; }
        .status-error { color: #c0392b; font-weight: 500; }
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
                    <img src="../images/logo-header-2024-normal.png" alt="Logo Universitas Lampung">
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"  class="active"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Jalankan Harvester OAI</h1>
                <div class="user-profile">
                    <span>Role: Admin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <div class="harvester-grid">
                    <?php if (!empty($jurnal_list)): ?>
                        <?php foreach ($jurnal_list as $jurnal): ?>
                            <div class="harvester-card" id="jurnal-<?php echo $jurnal['id']; ?>">
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo htmlspecialchars($jurnal['judul_jurnal']); ?></h3>
                                    <a href="<?php echo htmlspecialchars($jurnal['link_oai']); ?>" target="_blank" class="card-oai-link">
                                        <i class="fas fa-link"></i> <?php echo htmlspecialchars($jurnal['link_oai']); ?>
                                    </a>
                                    <div class="card-stats">
                                        <div class="stat-item">
                                            <span class="label">Total Artikel</span>
                                            <span class="value article-count"><?php echo htmlspecialchars($jurnal['article_count']); ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="label">Panen Terakhir</span>
                                            <span class="value last-harvest"><?php echo $jurnal['last_harvest'] ? date('d M Y, H:i:s', strtotime($jurnal['last_harvest'])) : 'Belum Pernah'; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button class="btn-harvest" data-jurnal-id="<?php echo $jurnal['id']; ?>">
                                        <i class="fas fa-sync-alt"></i> Jalankan Panen
                                    </button>
                                    <div class="status-area"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Tidak ada jurnal yang siap untuk di-harvest. Pastikan jurnal memiliki Link OAI yang valid.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<script>
    // (BARU) JavaScript untuk Interaksi Harvester
    document.addEventListener('DOMContentLoaded', function() {
        const harvestButtons = document.querySelectorAll('.btn-harvest');

        harvestButtons.forEach(button => {
            button.addEventListener('click', function() {
                const jurnalId = this.dataset.jurnalId;
                const card = document.getElementById('jurnal-' + jurnalId);
                const statusArea = card.querySelector('.status-area');
                
                // Menonaktifkan tombol dan menampilkan status loading
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-cog fa-spin"></i> Memanen...';
                statusArea.innerHTML = '<span class="status-loading"><i class="fas fa-spinner"></i> Sedang memproses, mohon tunggu...</span>';

                // Membuat request AJAX
                const formData = new FormData();
                formData.append('action', 'run_harvest');
                formData.append('jurnal_id', jurnalId);

                fetch('harvester.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusArea.innerHTML = `<span class="status-success"><i class="fas fa-check-circle"></i> ${data.message}</span>`;
                        // Update statistik di card
                        card.querySelector('.article-count').textContent = data.article_count;
                        card.querySelector('.last-harvest').textContent = data.last_harvest;
                    } else {
                        statusArea.innerHTML = `<span class="status-error"><i class="fas fa-exclamation-circle"></i> Gagal: ${data.message}</span>`;
                    }
                })
                .catch(error => {
                    statusArea.innerHTML = '<span class="status-error"><i class="fas fa-exclamation-circle"></i> Terjadi error koneksi.</span>';
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Mengaktifkan kembali tombol setelah selesai
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-sync-alt"></i> Jalankan Panen';
                });
            });
        });
    });

    // Script untuk sidebar toggle
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