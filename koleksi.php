<?php
/**
 * Halaman Koleksi Jurnal
 * Menampilkan daftar artikel berdasarkan kategori koleksi (terbaru, bahasa, acak).
 */
include 'header.php';

// --- PENGATUAN PAGINASI ---
$results_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// --- KONEKSI DATABASE ---
require_once './database/config.php';
$conn = connect_to_database();

// --- LOGIKA DINAMIS BERDASARKAN PARAMETER 'koleksi' ---
$koleksi = isset($_GET['koleksi']) ? $_GET['koleksi'] : 'terbaru';
$judul = '';
$deskripsi = '';
$where_clause = '';
$order_by_clause = '';

switch ($koleksi) {
    case 'acak':
        $judul = 'Artikel Pilihan Acak';
        $deskripsi = 'Menampilkan koleksi artikel yang dipilih secara acak dari seluruh database.';
        $order_by_clause = 'ORDER BY RAND()';
        break;
    
    case 'inggris':
        $judul = 'Artikel Berbahasa Inggris';
        $deskripsi = 'Koleksi semua artikel yang dipublikasikan dalam Bahasa Inggris.';
        $where_clause = "WHERE language LIKE '%eng%'"; // Mencari 'en' atau 'english'
        $order_by_clause = 'ORDER BY date ASC';
        break;
        
    case 'indonesia':
        $judul = 'Artikel Berbahasa Indonesia';
        $deskripsi = 'Koleksi semua artikel yang dipublikasikan dalam Bahasa Indonesia.';
        $where_clause = "WHERE language LIKE '%ind%'";
        $order_by_clause = 'ORDER BY date DESC';
        break;
        
    case 'terbaru':
    default:
        $judul = 'Artikel Terbaru';
        $deskripsi = 'Telusuri koleksi artikel yang paling baru dipublikasikan.';
        $order_by_clause = 'ORDER BY date DESC';
        break;
}

// --- Query untuk menghitung total hasil ---
$count_sql = "SELECT COUNT(*) FROM artikel_oai " . $where_clause;
$count_result = $conn->query($count_sql);
$total_results = $count_result ? $count_result->fetch_row()[0] : 0;
$total_pages = ceil($total_results / $results_per_page);
?>

<title><?php echo htmlspecialchars($judul, ENT_QUOTES, 'UTF-8'); ?> - Portal Jurnal</title>

<!-- Kontainer Utama Halaman -->
<main class="page-container">
    <div class="container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars($judul, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars($deskripsi, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <p>Total ditemukan <?php echo htmlspecialchars((string)$total_results, ENT_QUOTES, 'UTF-8'); ?> artikel.</p>
        <hr>

        <!-- Daftar Hasil Pencarian Artikel -->
        <div class="search-results-list">
            <?php
            // --- Query utama untuk mengambil data artikel ---
            $data_sql = "SELECT title, publisher, creator1, creator2, creator3, identifier1, description, date 
                         FROM artikel_oai 
                         $where_clause 
                         $order_by_clause
                         LIMIT ? OFFSET ?";
            
            $data_stmt = $conn->prepare($data_sql);
            if ($data_stmt) {
                $data_stmt->bind_param("ii", $results_per_page, $offset);
                $data_stmt->execute();
                $result = $data_stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="article-item">';
                        echo '  <h4><a href="' . htmlspecialchars($row['identifier1'], ENT_QUOTES, 'UTF-8') . '" target="_blank">' . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . '</a></h4>';
                        $creators = array_filter([$row['creator1'], $row['creator2'], $row['creator3']]);
                        if (!empty($creators)) {
                            echo '  <p class="article-creator">Oleh: ' . htmlspecialchars(implode(', ', $creators), ENT_QUOTES, 'UTF-8') . '</p>';
                        }
                        $description_snippet = substr(strip_tags($row['description'] ?? ''), 0, 300);
                        echo '  <p class="article-description">' . htmlspecialchars($description_snippet, ENT_QUOTES, 'UTF-8') . '...</p>';
                        $publication_date = date("d F Y", strtotime($row['date']));
                        echo '  <p class="article-source">Penerbit: ' . htmlspecialchars($row['publisher'], ENT_QUOTES, 'UTF-8') . ' &bull; Publikasi: ' . htmlspecialchars($publication_date, ENT_QUOTES, 'UTF-8') . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Tidak ada artikel dalam koleksi ini untuk ditampilkan.</p>';
                }
                $data_stmt->close();
            }
            ?>
        </div>

        <!-- Navigasi Paginasi -->
        <nav class="pagination modern">
            <ul>
                <?php
                // Logika paginasi
                if ($total_pages > 1) {
                    $safe_koleksi = htmlspecialchars(urlencode($koleksi), ENT_QUOTES, 'UTF-8');
                    // Previous button logic
                    if ($page > 1) {
                        $prev_page = htmlspecialchars((string)($page - 1), ENT_QUOTES, 'UTF-8');
                        echo '<li><a href="koleksi.php?koleksi=' . $safe_koleksi . '&page=' . $prev_page . '">&laquo; Previous</a></li>';
                    }
                    // Number logic
                    $window = 2;
                    for ($i = 1; $i <= $total_pages; $i++) {
                        if ($i == 1 || $i == $total_pages || ($i >= $page - $window && $i <= $page + $window)) {
                            $active_class = ($i == $page) ? 'active' : '';
                            $safe_i = htmlspecialchars((string)$i, ENT_QUOTES, 'UTF-8');
                            $safe_active_class = htmlspecialchars($active_class, ENT_QUOTES, 'UTF-8');
                            echo '<li><a href="koleksi.php?koleksi=' . $safe_koleksi . '&page=' . $safe_i . '" class="' . $safe_active_class . '">' . $safe_i . '</a></li>';
                        } elseif ($i == $page - $window - 1 || $i == $page + $window + 1) {
                            echo '<li><span class="ellipsis">...</span></li>';
                        }
                    }
                    // Next button logic
                    if ($page < $total_pages) {
                        $next_page = htmlspecialchars((string)($page + 1), ENT_QUOTES, 'UTF-8');
                        echo '<li><a href="koleksi.php?koleksi=' . $safe_koleksi . '&page=' . $next_page . '">Next &raquo;</a></li>';
                    }
                }
                ?>
            </ul>
        </nav>
    </div>
</main>

<?php
$conn->close();
include 'footer.php';
?>
</body>
</html>