<?php
include 'header.php';

// Mendapatkan topik dari URL
$halaman = isset($_GET['halaman']) ? $_GET['halaman'] : 'default';

$judul = '';
$konten = '';

// Logika untuk menentukan judul dan konten
switch ($halaman) {
    case 'aksesibilitas':
        $judul = 'Pernyataan Aksesibilitas';
        $konten = '
            <div class="info-card">
                <i class="fas fa-universal-access"></i>
                <h3>Komitmen Kami</h3>
                <p>Portal Agregator Jurnal Universitas Lampung berkomitmen untuk memastikan aksesibilitas digital bagi semua pengguna, termasuk penyandang disabilitas. Kami secara berkelanjutan meningkatkan pengalaman pengguna dengan menerapkan standar aksesibilitas yang relevan.</p>
            </div>
            <div class="info-card">
                <i class="fas fa-check-circle"></i>
                <h3>Upaya Peningkatan</h3>
                <ul>
                    <li>Menggunakan HTML semantik untuk memastikan struktur konten yang jelas.</li>
                    <li>Menyediakan kontras warna yang memadai untuk keterbacaan.</li>
                    <li>Memastikan fungsionalitas situs dapat diakses melalui keyboard.</li>
                </ul>
            </div>';
        break;
    
    case 'kontak':
        $judul = 'Kontak & Bantuan';
        $konten = '
            <div class="info-card">
                <i class="fas fa-envelope"></i>
                <h3>Dukungan Teknis</h3>
                <p>Jika Anda mengalami kendala teknis atau memiliki pertanyaan terkait fungsionalitas portal, silakan hubungi tim dukungan kami melalui email di:</p>
                <p><strong>support-jurnal@unila.ac.id</strong></p>
            </div>
            <div class="info-card">
                <i class="fas fa-building"></i>
                <h3>Alamat Kantor</h3>
                <p>UPT Perpustakaan Universitas Lampung<br>Jl. Prof. Dr. Ir. Sumantri Brojonegoro No.1, Gedong Meneng, Kec. Rajabasa, Kota Bandar Lampung, Lampung 35141</p>
            </div>';
        break;

    case 'hukum':
        $judul = 'Pemberitahuan Hukum';
        $konten = '
            <div class="info-card">
                <i class="fas fa-gavel"></i>
                <h3>Batasan Tanggung Jawab</h3>
                <p>Informasi yang disajikan dalam portal ini adalah agregasi dari berbagai sumber jurnal di lingkungan Universitas Lampung. Meskipun kami berupaya untuk memastikan akurasi data, kami tidak bertanggung jawab atas kesalahan atau kelalaian dalam konten asli yang disediakan oleh penerbit.</p>
                <p>Penggunaan informasi dari portal ini adalah tanggung jawab penuh pengguna.</p>
            </div>';
        break;

    default:
        $judul = 'Halaman Tidak Ditemukan';
        $konten = '<p>Maaf, konten yang Anda cari tidak tersedia.</p>';
        break;
}
?>

<title><?php echo $judul; ?> - Portal Jurnal</title>

<main class="page-container">
    <div class="container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars($judul); ?></h1>
        </div>
        
        <div class="info-content-wrapper">
            <?php echo $konten; ?>
        </div>
    </div>
</main>

<?php
include 'footer.php';
?>
</body>
</html>