<?php
// Mulai atau lanjutkan sesi
session_start();

// Cek apakah pengguna sudah login dan memiliki peran yang sesuai
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
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

// Ambil data jurnal dari database
$jurnal_list = [];
$sql = "SELECT id, judul_jurnal, link_oai, status, created_at FROM jurnal_sumber WHERE link_oai IS NOT NULL AND link_oai != ''";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Cek apakah jurnal sudah di-harvest (ada artikel di artikel_oai)
        $check_harvest = $conn->prepare("SELECT COUNT(*) as count, MAX(created_at) as last_harvest FROM artikel_oai WHERE journal_source_id = ?");
        $check_harvest->bind_param("i", $row['id']);
        $check_harvest->execute();
        $harvest_result = $check_harvest->get_result()->fetch_assoc();
        
        $row['is_harvested'] = $harvest_result['count'] > 0;
        $row['article_count'] = $harvest_result['count'];
        $row['last_harvest'] = $harvest_result['last_harvest'];
        
        $jurnal_list[] = $row;
        $check_harvest->close();
    }
}

// Handle AJAX request untuk menjalankan harvester
if (isset($_POST['action']) && $_POST['action'] == 'harvest' && isset($_POST['jurnal_id'])) {
    $jurnal_id = intval($_POST['jurnal_id']);
    
    // Ambil data jurnal yang akan di-harvest
    $stmt = $conn->prepare("SELECT id, judul_jurnal, link_oai FROM jurnal_sumber WHERE id = ?");
    $stmt->bind_param("i", $jurnal_id);
    $stmt->execute();
    $jurnal_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($jurnal_data) {
        // Set timeout untuk proses harvesting
        set_time_limit(300); // 5 menit
        
        $nama_jurnal = $jurnal_data['judul_jurnal'];
        $base_oai_url = $jurnal_data['link_oai'];
        
        $resumptionToken = null;
        $isFirstRequest = true;
        $total_new_articles = 0;
        $total_skipped_articles = 0;
        $total_deleted_records = 0;
        
        do {
            if ($isFirstRequest) {
                $oai_url = $base_oai_url . "?verb=ListRecords&metadataPrefix=oai_dc";
                $isFirstRequest = false;
            } else {
                $oai_url = $base_oai_url . "?verb=ListRecords&resumptionToken=" . urlencode($resumptionToken);
            }
            
            $xmlContent = @file_get_contents($oai_url);
            if (!$xmlContent) break;
            
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);
            if ($xml === false) {
                libxml_clear_errors();
                break;
            }
            
            $xml->registerXPathNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            
            $records = $xml->xpath('//oai:record');
            if (empty($records)) break;
            
            foreach ($records as $record) {
                if (!isset($record->metadata)) { 
                    $total_deleted_records++; 
                    continue; 
                }
                
                $dc = $record->metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/')->dc->children('http://purl.org/dc/elements/1.1/');
                $unique_identifier = isset($dc->identifier[0]) ? (string)$dc->identifier[0] : null;
                if (!$unique_identifier) continue;
                
                $checkStmt = $conn->prepare("SELECT id FROM artikel_oai WHERE unique_identifier = ?");
                $checkStmt->bind_param("s", $unique_identifier);
                $checkStmt->execute();
                $result_check = $checkStmt->get_result();
                
                if ($result_check->num_rows === 0) {
                    // Extract metadata
                    $title = (string)$dc->title ?: null;
                    $description = (string)$dc->description ?: null;
                    $publisher = (string)$dc->publisher ?: null;
                    $date = (string)$dc->date ?: null;
                    $language = (string)$dc->language ?: null;
                    $coverage = (string)$dc->coverage ?: null;
                    $rights = (string)$dc->rights ?: null;
                    
                    $creator1 = isset($dc->creator[0]) ? (string)$dc->creator[0] : null;
                    $creator2 = isset($dc->creator[1]) ? (string)$dc->creator[1] : null;
                    $creator3 = isset($dc->creator[2]) ? (string)$dc->creator[2] : null;
                    
                    $subject1 = isset($dc->subject[0]) ? (string)$dc->subject[0] : null;
                    $subject2 = isset($dc->subject[1]) ? (string)$dc->subject[1] : null;
                    $subject3 = isset($dc->subject[2]) ? (string)$dc->subject[2] : null;
                    
                    $contributor1 = isset($dc->contributor[0]) ? (string)$dc->contributor[0] : null;
                    $contributor2 = isset($dc->contributor[1]) ? (string)$dc->contributor[1] : null;
                    
                    $type1 = isset($dc->type[0]) ? (string)$dc->type[0] : null;
                    $type2 = isset($dc->type[1]) ? (string)$dc->type[1] : null;
                    
                    $format1 = isset($dc->format[0]) ? (string)$dc->format[0] : null;
                    $format2 = isset($dc->format[1]) ? (string)$dc->format[1] : null;
                    
                    $identifier1 = isset($dc->identifier[0]) ? (string)$dc->identifier[0] : null;
                    $identifier2 = isset($dc->identifier[1]) ? (string)$dc->identifier[1] : null;
                    $identifier3 = isset($dc->identifier[2]) ? (string)$dc->identifier[2] : null;
                    
                    $source1 = isset($dc->source[0]) ? (string)$dc->source[0] : null;
                    $source2 = isset($dc->source[1]) ? (string)$dc->source[1] : null;
                    
                    $relation1 = isset($dc->relation[0]) ? (string)$dc->relation[0] : null;
                    $relation2 = isset($dc->relation[1]) ? (string)$dc->relation[1] : null;
                    
                    // Insert ke database
                    $insertStmt = $conn->prepare(
                        "INSERT INTO artikel_oai (
                            journal_source_id, journal_title_clean, unique_identifier, title, description, publisher, date, language, coverage, rights,
                            creator1, creator2, creator3,
                            subject1, subject2, subject3,
                            contributor1, contributor2,
                            type1, type2,
                            format1, format2,
                            identifier1, identifier2, identifier3,
                            source1, source2,
                            relation1, relation2
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $insertStmt->bind_param("issssssssssssssssssssssssssss", 
                        $jurnal_id, $nama_jurnal, $unique_identifier, $title, $description, $publisher, $date, $language, $coverage, $rights,
                        $creator1, $creator2, $creator3,
                        $subject1, $subject2, $subject3,
                        $contributor1, $contributor2,
                        $type1, $type2,
                        $format1, $format2,
                        $identifier1, $identifier2, $identifier3,
                        $source1, $source2,
                        $relation1, $relation2
                    );
                    $insertStmt->execute();
                    $insertStmt->close();
                    
                    $total_new_articles++;
                } else {
                    $total_skipped_articles++;
                }
                $checkStmt->close();
            }
            
            $resumptionToken = (string)$xml->ListRecords->resumptionToken;
            
        } while (!empty($resumptionToken));
        
        // Return JSON response
        echo json_encode([
            'success' => true,
            'total_new' => $total_new_articles,
            'total_skipped' => $total_skipped_articles,
            'total_deleted' => $total_deleted_records
        ]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Jurnal tidak ditemukan']);
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harvester - Kelola Panen Jurnal</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .harvester-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .journal-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .journal-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .journal-card.harvested {
            border-left-color: #28a745;
        }
        
        .journal-card.not-harvested {
            border-left-color: #ffc107;
        }
        
        .journal-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .journal-info {
            margin-bottom: 15px;
        }
        
        .journal-info p {
            margin: 5px 0;
            font-size: 0.9em;
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .status-harvested {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-not-harvested {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .harvest-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background 0.3s ease;
            width: 100%;
        }
        
        .harvest-btn:hover {
            background: linear-gradient(45deg, #0056b3, #004085);
        }
        
        .harvest-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin: 10px 0;
        }
        
        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .result-message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }
        
        .result-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .result-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 2em;
        }
        
        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
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
                    <img src="../assets/unila_logo.png" alt="Logo Universitas Lampung">
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php" ><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php" class="active"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        <!-- End Sidebar -->

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="page-header">
                    <h1><i class="fas fa-seedling"></i> Harvester - Kelola Panen Jurnal</h1>
                    <p>Pilih jurnal yang ingin Anda panen metadata-nya dari Dublin Core OAI</p>
                </div>
            </div>

            <div class="content-area">
                <?php if (empty($jurnal_list)): ?>
                <div class="card" style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3em; color: #ffc107; margin-bottom: 20px;"></i>
                    <h3>Tidak Ada Jurnal Tersedia</h3>
                    <p>Belum ada jurnal dengan URL OAI yang valid di database.</p>
                    <a href="manage_journal.php" class="harvest-btn" style="max-width: 200px; margin: 20px auto;">Kelola Jurnal</a>
                </div>
                <?php else: ?>
                <div class="harvester-grid">
                    <?php foreach ($jurnal_list as $jurnal): ?>
                    <div class="journal-card <?php echo $jurnal['is_harvested'] ? 'harvested' : 'not-harvested'; ?>">
                        <div class="journal-title">
                            <?php echo htmlspecialchars($jurnal['judul_jurnal']); ?>
                        </div>
                        
                        <div class="status-badge <?php echo $jurnal['is_harvested'] ? 'status-harvested' : 'status-not-harvested'; ?>">
                            <i class="fas <?php echo $jurnal['is_harvested'] ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                            <?php echo $jurnal['is_harvested'] ? 'Sudah Di-harvest' : 'Belum Di-harvest'; ?>
                        </div>
                        
                        <div class="journal-info">
                            <p><strong>URL OAI:</strong> <small><?php echo htmlspecialchars($jurnal['link_oai']); ?></small></p>
                            <p><strong>Status:</strong> <?php echo ucfirst($jurnal['status']); ?></p>
                            <?php if ($jurnal['is_harvested']): ?>
                            <p><strong>Total Artikel:</strong> <?php echo number_format($jurnal['article_count']); ?></p>
                            <p><strong>Terakhir Di-harvest:</strong> <?php echo $jurnal['last_harvest'] ? date('d/m/Y H:i', strtotime($jurnal['last_harvest'])) : 'Tidak diketahui'; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <button class="harvest-btn" onclick="harvestJournal(<?php echo $jurnal['id']; ?>, this)">
                            <i class="fas fa-seedling"></i>
                            <?php echo $jurnal['is_harvested'] ? 'Panen Ulang' : 'Mulai Panen'; ?>
                        </button>
                        
                        <div class="loading">
                            <div class="spinner"></div>
                            Sedang memanen data...
                        </div>
                        
                        <div class="result-message"></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- End Main Content -->
    </div>

    <script>
    function harvestJournal(jurnalId, button) {
        const card = button.closest('.journal-card');
        const loading = card.querySelector('.loading');
        const resultMessage = card.querySelector('.result-message');
        
        // Reset state
        button.disabled = true;
        loading.style.display = 'block';
        resultMessage.style.display = 'none';
        
        // Send AJAX request
        const formData = new FormData();
        formData.append('action', 'harvest');
        formData.append('jurnal_id', jurnalId);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            resultMessage.style.display = 'block';
            
            if (data.success) {
                resultMessage.className = 'result-message result-success';
                resultMessage.innerHTML = `
                    <strong>Panen Berhasil!</strong><br>
                    Artikel baru: ${data.total_new}<br>
                    Artikel sudah ada: ${data.total_skipped}<br>
                    Record kosong: ${data.total_deleted}
                `;
                
                // Update status card
                card.classList.remove('not-harvested');
                card.classList.add('harvested');
                const statusBadge = card.querySelector('.status-badge');
                statusBadge.className = 'status-badge status-harvested';
                statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Sudah Di-harvest';
                
                button.innerHTML = '<i class="fas fa-seedling"></i> Panen Ulang';
                
                // Refresh halaman setelah 3 detik untuk update info
                setTimeout(() => {
                    location.reload();
                }, 3000);
            } else {
                resultMessage.className = 'result-message result-error';
                resultMessage.innerHTML = `<strong>Panen Gagal!</strong><br>${data.message || 'Terjadi kesalahan saat memanen data.'}`;
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            resultMessage.style.display = 'block';
            resultMessage.className = 'result-message result-error';
            resultMessage.innerHTML = '<strong>Error!</strong><br>Terjadi kesalahan koneksi.';
            console.error('Error:', error);
        })
        .finally(() => {
            button.disabled = false;
        });
    }

    
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