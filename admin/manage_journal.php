<?php
// Mulai atau lanjutkan sesi
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Konfigurasi database MySQL
require_once '../database/config.php';
$conn = connect_to_database();

// Inisialisasi pesan
$message = '';

// Handle aksi dari formulir untuk update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $jurnalId = $_POST['jurnal_id'];
    $newStatus = $_POST['status'];
    
    // Validasi status untuk keamanan
    $allowed_statuses = ['pending', 'selesai', 'ditolak', 'butuh_edit'];
    if (in_array($newStatus, $allowed_statuses)) {
        // Query untuk memperbarui status di tabel jurnal_sumber
        $stmt = $conn->prepare("UPDATE jurnal_sumber SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $jurnalId);
        if ($stmt->execute()) {
            $message = "<div class='success-message'>Status jurnal berhasil diperbarui!</div>";
        } else {
            $message = "<div class='error-message'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='error-message'>Status tidak valid.</div>";
    }
}

// (DIPERBARUI) Handle aksi untuk menghapus jurnal DAN artikel terkait menggunakan TRANSAKSI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_journal'])) {
    $jurnalId = $_POST['jurnal_id'];
    
    // Memulai transaksi untuk memastikan semua query berhasil atau tidak sama sekali
    $conn->begin_transaction();

    try {
        // Langkah 1: Hapus semua artikel yang terkait dengan jurnal ini dari tabel 'artikel_oai'
        $stmt_articles = $conn->prepare("DELETE FROM artikel_oai WHERE journal_source_id = ?");
        $stmt_articles->bind_param("i", $jurnalId);
        $stmt_articles->execute();
        $stmt_articles->close();

        // Langkah 2: Hapus jurnal itu sendiri dari tabel 'jurnal_sumber'
        $stmt_journal = $conn->prepare("DELETE FROM jurnal_sumber WHERE id = ?");
        $stmt_journal->bind_param("i", $jurnalId);
        $stmt_journal->execute();
        $stmt_journal->close();

        // Jika kedua penghapusan berhasil, commit (simpan permanen) transaksi
        $conn->commit();
        $message = "<div class='success-message'>Jurnal dan semua artikel terkait berhasil dihapus!</div>";

    } catch (mysqli_sql_exception $exception) {
        // Jika terjadi error di salah satu langkah, batalkan semua perubahan (rollback)
        $conn->rollback();
        $message = "<div class='error-message'>Error: Gagal menghapus data. Transaksi dibatalkan. " . $exception->getMessage() . "</div>";
    }
}


// Ambil daftar jurnal dari database jurnal_sumber
$jurnals = [];
$sql = "SELECT js.id, js.judul_jurnal, u.nama AS pengelola_nama, js.status, js.created_at
        FROM jurnal_sumber js 
        LEFT JOIN users u ON js.pengelola_id = u.id
        ORDER BY js.created_at DESC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $jurnals[] = $row;
    }
}

// Fungsi untuk mendapatkan kelas CSS berdasarkan status
function getStatusClass($status) {
    switch ($status) {
        case 'selesai':
            return 'status-approved';
        case 'ditolak':
            return 'status-rejected';
        case 'butuh_edit':
            return 'status-needs_edit';
        case 'pending':
        default:
            return 'status-pending';
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Style untuk tombol hapus */
        #btn-delete {
            background-color: #e74c3c; /* Warna merah untuk aksi berbahaya */
            color: white;
        }

         .edit-btn {
        display: inline-block;
        padding: 8px 12px;
        border-radius: 4px;
        background-color: #3498db; /* hijau elegan */
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        }

         .edit-btn:hover {
        background-color: #2980b9; /* warna lebih gelap saat hover */
        transform: translateY(-2px); /* efek naik */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); /* shadow elegan */
        }

        .edit-btn:active {
        transform: translateY(0); /* kembali normal saat ditekan */
        box-shadow: none;
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
                <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_pengelola.php"><i class="fas fa-user-cog"></i> <span>Kelola Pengelola</span></a></li>
                <li><a href="manage_journal.php"  class="active"><i class="fas fa-book"></i> <span>Kelola Jurnal</span></a></li>
                <li><a href="tinjau_permintaan.php"><i class="fas fa-envelope-open-text"></i> <span>Tinjau Permintaan</span></a></li>
                <li><a href="harvester.php"><i class="fas fa-seedling"></i> <span>Jalankan Harvester</span></a></li>
                <li><a href="cetak_editorial.php"><i class="fas fa-print"></i> <span>Cetak Editorial</span></a></li>
                <li><a href="change_password.php"><i class="fas fa-lock"></i> <span>Ganti Password</span></a></li>
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
                                <th>No.</th>
                                <th>Judul Jurnal</th>
                                <th>Nama Pengelola</th>
                                <th>Tanggal Submit</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $nomor = 1; 
                                foreach ($jurnals as $jurnal): 
                            ?>
                                <tr>
                                    <td><?php echo $nomor; ?></td>
                                    <td><?php echo htmlspecialchars($jurnal['judul_jurnal']); ?></td>
                                    <td><?php echo htmlspecialchars($jurnal['pengelola_nama']); ?></td>
                                    <td><?php echo htmlspecialchars($jurnal['created_at']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" class="status-form">
                                                <input type="hidden" name="jurnal_id" value="<?php echo htmlspecialchars($jurnal['id']); ?>">
                                                <select name="status" class="status-select <?php echo getStatusClass($jurnal['status']); ?>" onchange="this.className='status-select ' + this.options[this.selectedIndex].dataset.class">
                                                    <option value="pending" data-class="status-pending" <?php echo $jurnal['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="selesai" data-class="status-approved" <?php echo $jurnal['status'] == 'selesai' ? 'selected' : ''; ?>>Disetujui</option>
                                                    <option value="ditolak" data-class="status-rejected" <?php echo $jurnal['status'] == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                                    <option value="butuh_edit" data-class="status-needs_edit" <?php echo $jurnal['status'] == 'butuh_edit' ? 'selected' : ''; ?>>Butuh Edit</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-update"><i class="fas fa-save"></i></button>
                                            </form>
                                            <a href="tinjau_jurnal.php?id=<?php echo htmlspecialchars($jurnal['id']); ?>" class="edit-btn" title="Lihat & Tinjau Detail">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" id="btn-delete"
                                                title="Hapus Jurnal dan Isinya"
                                                onclick="confirmDelete(<?php echo $jurnal['id']; ?>, '<?php echo htmlspecialchars(addslashes($jurnal['judul_jurnal'])); ?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                $nomor++; 
                                endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
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

    // (BARU) Fungsi JavaScript untuk konfirmasi penghapusan 2-langkah
    function confirmDelete(journalId, journalTitle) {
        Swal.fire({
            title: 'Apakah Anda Yakin?',
            html: `
                <p>Tindakan ini bersifat <strong>permanen</strong> dan akan menghapus jurnal beserta <strong>seluruh artikel</strong> di dalamnya.</p>
                <p>Untuk mengonfirmasi, ketik judul jurnal di bawah ini:</p>
                <p><strong>${journalTitle}</strong></p>
                <input id="swal-input-confirm" class="swal2-input" placeholder="Ketik judul jurnal di sini">
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus Jurnal Ini',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            preConfirm: () => {
                // Validasi sebelum menutup dialog
                const confirmationText = document.getElementById('swal-input-confirm').value;
                if (confirmationText !== journalTitle) {
                    Swal.showValidationMessage('Judul jurnal yang Anda masukkan tidak cocok!');
                    return false; // Mencegah dialog tertutup
                }
                return true;
            }
        }).then((result) => {
            // Callback setelah dialog ditutup
            if (result.isConfirmed) {
                // Buat form dinamis dan submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const idInput = document.createElement('input');
                idInput.name = 'jurnal_id';
                idInput.value = journalId;
                form.appendChild(idInput);

                const actionInput = document.createElement('input');
                actionInput.name = 'delete_journal';
                actionInput.value = 'true'; // Nilai ini hanya untuk trigger di PHP
                form.appendChild(actionInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    </script>
</body>
</html>
<?php
$conn->close();
?>