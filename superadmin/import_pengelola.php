<?php
/**
 * Halaman Impor Data Pengelola dari File CSV (Versi All-in-One).
 *
 * Alur Kerja:
 * Halaman ini menampilkan semua komponen secara bersamaan:
 * 1. Form Unggah: Selalu tersedia untuk mengunggah file CSV baru.
 * 2. Pratinjau Data: Tampil setelah file diproses, berisi tabel data yang bisa diedit.
 * 3. Tombol Simpan: Bagian dari form pratinjau untuk menyimpan data ke database.
 * Mengunggah file baru akan menggantikan data pratinjau yang ada.
 *
 * @author Gemini
 * @version 4.0 (All-in-One Layout)
 */

// --- 1. Konfigurasi dan Inisialisasi ---
session_start();
require_once '../database/config.php';

// --- Helper Functions (Fungsi Bantuan) ---

/**
 * Memeriksa sesi dan otorisasi pengguna.
 */
function authorize_superadmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
        header("Location: login.php");
        exit();
    }
}

/**
 * Mengurai file CSV dan mengembalikan datanya sebagai array.
 * @param string $filePath Path menuju file CSV.
 * @return array Data yang telah diparsing.
 */
function parse_csv_file(string $filePath): array {
    $data = [];
    if (!is_file($filePath) || !is_readable($filePath)) {
        return [];
    }
    
    if (($handle = fopen($filePath, 'r')) !== FALSE) {
        fgetcsv($handle); // Abaikan baris header
        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count($row) >= 3) {
                $data[] = ['nama' => trim($row[0]), 'email' => trim($row[1]), 'password' => trim($row[2])];
            }
        }
        fclose($handle);
    }
    return $data;
}

/**
 * Menyimpan data pengguna yang diimpor ke database.
 * @param mysqli $conn Koneksi database.
 * @param array $usersData Data pengguna dari form pratinjau.
 * @return array Hasil impor [jumlah sukses, detail error].
 */
function save_users_to_database(mysqli $conn, array $usersData): array {
    $success_count = 0;
    $error_details = [];
    $role = 'pengelola';

    $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");

    foreach ($usersData['names'] as $index => $nama) {
        $email = $usersData['emails'][$index];
        $password = $usersData['passwords'][$index];

        if (empty($nama) || empty($email) || empty($password)) continue;

        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows === 0) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("ssss", $nama, $email, $hashedPassword, $role);
            if ($stmt->execute()) {
                $success_count++;
            }
        } else {
            $error_details[] = "Email '<strong>" . htmlspecialchars($email) . "</strong>' sudah ada (Baris " . ($index + 1) . ").";
        }
    }
    
    $stmt->close();
    $check_stmt->close();
    
    return [$success_count, $error_details];
}

// --- 2. Controller Utama (Manajemen Aksi) ---
authorize_superadmin();
$conn = connect_to_database();
$message = '';
$preview_data = null;

// Routing Aksi Berdasarkan Tombol yang Dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aksi 1: Unggah & Proses File CSV
    if (isset($_POST['upload_and_preview'])) {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $preview_data = parse_csv_file($_FILES['csv_file']['tmp_name']);
            if (empty($preview_data)) {
                $message = "<div class='error-message'>File CSV kosong atau formatnya salah. Pastikan urutan kolom adalah nama, email, password.</div>";
            }
        } else {
            $message = "<div class='error-message'>Gagal mengunggah file. Silakan pilih file terlebih dahulu.</div>";
        }
    }
    // Aksi 2: Simpan Data ke Database
    elseif (isset($_POST['save_data'])) {
        $userData = ['names' => $_POST['nama'], 'emails' => $_POST['email'], 'passwords' => $_POST['password']];
        list($count, $errors) = save_users_to_database($conn, $userData);

        $message = "<div class='success-message'><strong>{$count} data pengelola berhasil ditambahkan!</strong></div>";
        if (!empty($errors)) {
            $message .= "<div class='error-message'>Beberapa data gagal ditambahkan karena duplikasi:<br>" . implode('<br>', $errors) . "</div>";
        }
        // Data pratinjau tidak perlu ditampilkan lagi setelah disimpan
        $preview_data = null; 
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impor Data Pengelola</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .card { margin-bottom: 25px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .data-table th, .data-table td { padding: 12px; vertical-align: middle; text-align: left; border: 1px solid #e0e0e0; }
        .data-table th { background-color: #34495e; font-weight: 600; }
        .data-table input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 10px 20px; border-radius: 5px; cursor: pointer; border: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background-color: #3498db; color: white; }
        .btn-success { background-color: #2ecc71; color: white; }
        .upload-container { border: 2px dashed #3498db; padding: 30px; text-align: center; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="sidebar-toggle-btn" id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <div class="logo"><img src="../Images/logo-header-2024-normal.png" alt="Logo"></div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_superadmin.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pengelola.php" class="active"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_admin.php"><i class="fas fa-user-shield"></i> <span>Kelola Admin</span></a></li>
                <li><a href="manage_journal.php"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
                <li><a href="change_password.php"><i class="fas fa-lock"></i> <span>Ganti Password</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Impor Data Pengelola</h1>
                <div class="user-profile"><span>Role: Superadmin</span><a href="../api/logout.php">Logout</a></div>
            </div>

            <div class="content-area">
                <?php if (!empty($message)) echo $message; ?>

                <div class="card">
                    <h3>Langkah 1: Unggah File CSV</h3>
                    <p>Pilih file CSV dari komputermu, lalu klik "Tampilkan Data". Mengunggah file baru akan menggantikan pratinjau di bawah.</p>
                    <div class="upload-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="file" name="csv_file" accept=".csv" required>
                            <button type="submit" name="upload_and_preview" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Tampilkan Data
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($preview_data)): ?>
                <div class="card">
                    <h3>Langkah 2: Tinjau dan Tambahkan Data</h3>
                    <p>Periksa data di bawah ini. Anda bisa mengeditnya langsung. Data dengan email yang sudah ada akan dilewati saat disimpan.</p>
                    <form method="POST">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nama Jurnal</th>
                                    <th>Email</th>
                                    <th>Password</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($preview_data as $index => $row): ?>
                                    <tr>
                                        <td><input type="text" name="nama[]" value="<?php echo htmlspecialchars($row['nama']); ?>" required></td>
                                        <td><input type="email" name="email[]" value="<?php echo htmlspecialchars($row['email']); ?>" required></td>
                                        <td><input type="text" name="password[]" value="<?php echo htmlspecialchars($row['password']); ?>" required></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" name="save_data" class="btn btn-success" style="float: right; margin-top: 20px;">
                            <i class="fas fa-plus-circle"></i> Tambahkan ke Database
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>