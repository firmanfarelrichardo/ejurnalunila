<?php
/**
 * Halaman Utama Publik E-Jurnal
 * Menampilkan beranda dengan fitur pencarian, statistik, dan kata kunci populer.
 */
include 'header.php';
?>

<!-- Konten Utama -->
<main>
    <!-- Bagian Hero Banner -->
    <section class="hero-banner">
        <div class="hero-content">
            <h1>Unila E-Journal System</h1>
            <p class="hero-subtitle">Temukan artikel dari berbagai Fakultas di Universitas Lampung.</p>
            
            <div class="hero-search-container">
                <!-- Form Pencarian -->
                <form action="search.php" method="GET" class="hero-search-form">
                    <div class="search-input-wrapper">
                        <input type="search" name="keyword" placeholder="Cari artikel, judul, penulis..." required>
                    </div>
                    <button type="submit" aria-label="Cari">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <?php
            require_once './database/config.php';
            $conn = connect_to_database();

            $total_articles = 0;
            $total_journals = 0;
            $total_publishers = 0;
            $total_subjects = 0;

            if (!$conn->connect_error) {
                $result = $conn->query("SELECT COUNT(*) as total FROM artikel_oai");
                $total_articles = $result->fetch_assoc()['total'];
                $result = $conn->query("SELECT COUNT(*) as total FROM jurnal_sumber");
                $total_journals = $result->fetch_assoc()['total'];
                $result = $conn->query("SELECT COUNT(DISTINCT publisher) as total FROM artikel_oai WHERE publisher IS NOT NULL AND publisher != ''");
                $total_publishers = $result->fetch_assoc()['total'];
                $sql_subjects = "SELECT COUNT(DISTINCT subject) as total FROM ( SELECT subject1 AS subject FROM artikel_oai WHERE subject1 IS NOT NULL AND subject1 != '' UNION SELECT subject2 AS subject FROM artikel_oai WHERE subject2 IS NOT NULL AND subject2 != '' UNION SELECT subject3 AS subject FROM artikel_oai WHERE subject3 IS NOT NULL AND subject3 != '' ) as all_subjects";
                $result = $conn->query($sql_subjects);
                $total_subjects = $result->fetch_assoc()['total'];
                $conn->close();
            }
            ?> 
            
            <!-- Statistik -->
            <div class="stats-bar">
                <div class="stats-item">
                    <i class="fas fa-file-alt"></i>
                    <div class="stats-info">
                        <span class="number"><?php echo htmlspecialchars((string)number_format($total_articles), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="label">Artikel</span>
                    </div>
                </div>
                <div class="stats-item">
                    <i class="fas fa-users"></i>
                    <div class="stats-info">
                        <span class="number"><?php echo htmlspecialchars((string)number_format($total_publishers), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="label">Penerbit</span>
                    </div>
                </div>
                <div class="stats-item">
                    <i class="fas fa-book-open"></i>
                    <div class="stats-info">
                        <span class="number"><?php echo htmlspecialchars((string)number_format($total_journals), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="label">Jurnal</span>
                    </div>
                </div>
                <div class="stats-item">
                    <i class="fas fa-tag"></i>
                    <div class="stats-info">
                        <span class="number"><?php echo htmlspecialchars((string)number_format($total_subjects), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="label">Subjek</span>
                    </div>
                </div>
            </div>

            <!-- Pilihan Subjek / Kata Kunci Populer -->
            <div class="subject-selection">
                <p>Telusuri berdasarkan kata kunci populer:</p>
                <div class="subjects-list">
                    <?php
                    // Daftar kata kunci utama yang sudah dipilih
                    $keywords = [
                        'Pendidikan', 'Sosial', 'Teknik', 'Manajemen',
                        'Ekonomi', 'Hukum', 'Kesehatan', 'Matematika',
                        'Pertanian', 'Komputer', 'Lingkungan', 'Bahasa',
                        'Biologi', 'Komunikasi', 'Seni', 'Keuangan'
                    ];

                    // Daftar kelas CSS untuk ukuran yang berbeda
                    $size_classes = ['tag-medium'];

                    // Acak urutan kata kunci agar tampilan selalu bervariasi
                    shuffle($keywords);

                    foreach ($keywords as $keyword) {
                        // Pilih kelas ukuran secara acak dari daftar
                        $random_class = $size_classes[array_rand($size_classes)];
                        
                        // Buat link yang mengarah ke pencarian
                        echo '<a href="search.php?keyword=' . htmlspecialchars(urlencode($keyword), ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($random_class, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') . '</a>';
                    }
                    ?>
                </div>
            </div>

        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
</body>
</html>