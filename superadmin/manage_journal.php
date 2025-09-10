<?php
// Mulai atau lanjutkan sesi
session_start();

// Periksa apakah pengguna sudah login dan memiliki peran superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
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

// Handle aksi dari formulir (terutama untuk update status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $submissionId = $_POST['submission_id'];
    $newStatus = $_POST['status'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("UPDATE jurnal_submissions SET status = ?, notes = ? WHERE id = ?");
    $stmt->bind_param("ssi", $newStatus, $notes, $submissionId);
    if ($stmt->execute()) {
        $message = "<div class='success-message'>Status jurnal berhasil diperbarui!</div>";
    } else {
        $message = "<div class='error-message'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Ambil daftar jurnal submissions dari database
$submissions = [];
$sql = "SELECT js.*, u.nama AS pengelola_nama, u.nip AS pengelola_nip 
        FROM jurnal_submissions js 
        LEFT JOIN users u ON js.submitted_by_nip = u.nip
        ORDER BY js.submission_date DESC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jurnal - Superadmin</title>
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
                <li><a href="manage_journal.php" class="active"><i class="fas fa-book"></i> Kelola Jurnal</a></li>
                <li><a href="change_password.php"><i class="fas fa-key"></i> Ganti Password</a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <!-- End Sidebar -->

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Kelola Jurnal Submissions</h1>
                <div class="user-profile">
                    <span>Role: Superadmin</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <div class="content-area">
                <?php echo $message; ?>
                <div class="card">
                    <h3>Daftar Submissions Jurnal</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Judul Jurnal</th>
                                <th>Pengelola</th>
                                <th>Status</th>
                                <th>Tanggal Submit</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($submission['id']); ?></td>
                                    <td><?php echo htmlspecialchars($submission['journal_title']); ?></td>
                                    <td><?php echo htmlspecialchars($submission['pengelola_nama']); ?> (<?php echo htmlspecialchars($submission['pengelola_nip']); ?>)</td>
                                    <td>
                                        <form method="POST" class="status-form">
                                            <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($submission['id']); ?>">
                                            <select name="status">
                                                <option value="pending" <?php echo $submission['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo $submission['status'] == 'approved' ? 'selected' : ''; ?>>Disetujui</option>
                                                <option value="rejected" <?php echo $submission['status'] == 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                                                <option value="needs_edit" <?php echo $submission['status'] == 'needs_edit' ? 'selected' : ''; ?>>Butuh Edit</option>
                                            </select>
                                            <button type="submit" name="update_status">Update</button>
                                        </form>
                                    </td>
                                    <td><?php echo htmlspecialchars($submission['submission_date']); ?></td>
                                    <td>
                                        <a href="view_jurnal_details.php?id=<?php echo htmlspecialchars($submission['id']); ?>">Lihat Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- End Main Content -->
    </div>
</body>
</html>
<?php
$conn->close();
?>
