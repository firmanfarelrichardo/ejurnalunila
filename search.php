<?php
include 'header.php';

// Pengaturan Pagination
$results_per_page = 10; // Jumlah hasil per halaman
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Ambil keyword dan pastikan tidak kosong
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Koneksi ke Database
require_once './database/config.php';
$conn = connect_to_database();
?>

<title>Hasil Pencarian untuk "<?php echo htmlspecialchars($keyword); ?>"</title>

<main class="page-container">
    <div class="container">
        <div class="page-header">
            <h1>Hasil Pencarian</h1>
        </div>

        <!-- FORM PENCARIAN ULANG -->
        <div class="re-search-container">
            <form action="search.php" method="GET">
                <input type="search" name="keyword" placeholder="Cari kata kunci lain..." value="<?php echo htmlspecialchars($keyword); ?>" required>
                <button type="submit">Cari Ulang</button>
            </form>
        </div>

        <?php if (!empty($keyword)): ?>
            <p>Menampilkan hasil untuk: "<strong><?php echo htmlspecialchars($keyword); ?></strong>"</p>
        <?php endif; ?>

        <div class="search-results-list">
            <?php
            if (!empty($keyword)) {
                $search_term = "%" . $keyword . "%";

                // Query untuk menghitung total hasil (untuk pagination)
                $count_stmt = $conn->prepare("SELECT COUNT(*) FROM artikel_oai WHERE journal_title_clean LIKE ? OR title LIKE ? OR description LIKE ? OR creator1 LIKE ? OR subject1 LIKE ?");
                $count_stmt->bind_param("sssss", $search_term, $search_term, $search_term, $search_term, $search_term);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result()->fetch_row();
                $total_results = $count_result[0];
                $total_pages = ceil($total_results / $results_per_page);
                $count_stmt->close();

                echo "<p>Ditemukan sekitar " . $total_results . " hasil.</p><hr>";

                // Query utama untuk mengambil data dengan limit dan offset
                $stmt = $conn->prepare(
                    "SELECT title, journal_title_clean, description, creator1, creator2, creator3, source1, identifier1 
                     FROM artikel_oai
                     WHERE journal_title_clean LIKE ? OR title LIKE ? OR description LIKE ? OR creator1 LIKE ? OR subject1 LIKE ?
                     LIMIT ? OFFSET ?"
                );
                $stmt->bind_param("sssssii", $search_term, $search_term, $search_term, $search_term, $search_term, $results_per_page, $offset);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($total_results > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="article-item">';
                        $link = !empty($row['identifier1']) ? $row['identifier1'] : '#';
                        echo '<h4><a href="' . htmlspecialchars($link) . '" target="_blank">' . htmlspecialchars($row['title']) . '</a></h4>';
                        
                        $creators = array_filter([$row['creator1'], $row['creator2'], $row['creator3']]);
                        echo '<p class="article-creator">Oleh: ' . htmlspecialchars(implode(', ', $creators)) . '</p>';

                        $description_snippet = substr(strip_tags($row['description']), 0, 300);
                        echo '<p class="article-description">' . htmlspecialchars($description_snippet) . '...</p>';
                        
                        echo '<p class="article-source">Sumber: ' . htmlspecialchars($row['source1']) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo "<p>Tidak ada artikel yang cocok dengan kata kunci Anda.</p>";
                }
                $stmt->close();

            } else {
                echo "<p>Silakan masukkan kata kunci untuk memulai pencarian.</p>";
            }
            ?>
        </div>

        <!-- Navigasi Pagination -->
        <nav class="pagination">
            <ul>
                <?php
                if (!empty($keyword) && $total_pages > 1) {
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active_class = ($i == $page) ? 'active' : '';
                        echo '<li><a href="search_result.php?keyword=' . urlencode($keyword) . '&page=' . $i . '" class="' . $active_class . '">' . $i . '</a></li>';
                    }
                }
                ?>
            </ul>
        </nav>
    </div>
</main>

<?php
$conn->close();
?>
</body>
</html>