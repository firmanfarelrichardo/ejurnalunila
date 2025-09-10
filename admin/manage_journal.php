<?php
// Mulai atau lanjutkan sesi
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

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

// Inisialisasi pesan
$message = '';

// Handle aksi dari formulir untuk update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $jurnalId = $_POST['jurnal_id'];
    $newStatus = $_POST['status'];
    
    // Perbaikan: Konversi nilai status dari form agar sesuai dengan database
    if ($newStatus == 'approved') {
        $newStatus = 'selesai';
    } elseif ($newStatus == 'rejected') {
        $newStatus = 'ditolak';
    } elseif ($newStatus == 'needs_edit') {
        $newStatus = 'butuh_edit';
    }

    // Query untuk memperbarui status di tabel jurnal_sumber
    $stmt = $conn->prepare("UPDATE jurnal_sumber SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $jurnalId);
    if ($stmt->execute()) {
        $message = "<div class='success-message'>Status jurnal berhasil diperbarui!</div>";
    } else {
        $message = "<div class='error-message'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Ambil daftar jurnal dari database jurnal_sumber
$jurnals = [];
$sql = "SELECT js.id, js.judul_jurnal, u.nama AS pengelola_nama, u.nip AS pengelola_nip, js.status, js.created_at
        FROM jurnal_sumber js 
        LEFT JOIN users u ON js.pengelola_id = u.id
        ORDER BY js.created_at DESC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $jurnals[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jurnal - Admin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .data-table th {
            background-color: #34495e;
            color: white;
        }
        .data-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .status-form {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .status-form select {
            padding: 5px;
            border-radius: 4px;
        }
        .status-form button {
            padding: 5px 10px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            background-color: #3498db;
            color: white;
        }
        .status-form button:hover {
            background-color: #2980b9;
        }
        .notes-text {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
        <div class="sidebar">
            <div class="logo">
                <h2>Admin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> Kelola Pengelola</a></li>
                <li><a href="manage_journal.php" class="active"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="header">
                <h1>Kelola Jurnal Submissions</h1>
                <div class="user-profile">
                    <span>Role: Admin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <?php echo $message; ?>
                <div class="card">
                    <h3>Daftar Jurnal</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Judul Jurnal</th>
                                <th>Nama</th>
                                <th>pengelola_nip</th>
                                <th>Status</th>
                                <th>Tanggal Submit</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jurnals as $jurnal): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($jurnal['id']); ?></td>
                                    <td><?php echo htmlspecialchars($jurnal['judul_jurnal']); ?></td>
                                    <td><?php echo htmlspecialchars($jurnal['pengelola_nama']); ?></td>
                                    <td><?php echo htmlspecialchars($jurnal['pengelola_nip']); ?></td>
                                    <td>
                                        <form method="POST" class="status-form">
                                            <input type="hidden" name="jurnal_id" value="<?php echo htmlspecialchars($jurnal['id']); ?>">
                                            <select name="status">
                                                <option value="pending" <?php echo $jurnal['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="selesai" <?php echo $jurnal['status'] == 'selesai' ? 'selected' : ''; ?>>Disetujui</option>
                                                <option value="ditolak" <?php echo $jurnal['status'] == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                                <option value="butuh_edit" <?php echo $jurnal['status'] == 'butuh_edit' ? 'selected' : ''; ?>>Butuh Edit</option>
                                            </select>
                                            <button type="submit" name="update_status">Update</button>
                                        </form>
                                    </td>
                                    <td><?php echo htmlspecialchars($jurnal['created_at']); ?></td>
                                    <td>
                                        <a href="tinjau_jurnal.php?id=<?php echo htmlspecialchars($jurnal['id']); ?>">Lihat & Tinjau</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
</body>
</html>
<?php
$conn->close();
?>