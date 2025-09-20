<?php
include 'header.php';

// --- PENGATURAN PAGINASI ---
$results_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// --- KONEKSI DATABASE ---
require_once './database/config.php';
$conn = connect_to_database();

// --- QUERY UNTUK MENGHITUNG TOTAL PENERBIT (UNTUK PAGINASI) DARI `artikel_oai` ---
$count_sql = "SELECT COUNT(DISTINCT publisher) FROM artikel_oai WHERE publisher IS NOT NULL AND publisher != ''";
$count_result = $conn->query($count_sql);
$total_results = $count_result ? $count_result->fetch_row()[0] : 0;
$total_pages = ceil($total_results / $results_per_page);
?>

<title>Daftar Penerbit - Portal Jurnal</title>

<main class="page-container">
    <div class="container">
        
        <div class="page-header">
            <h1>Penerbit</h1>
            <p>Telusuri artikel berdasarkan lembaga penerbit yang terdaftar.</p>
        </div>

        <div class="publisher-page-controls">
            <div class="sort-by">
                <label for="sort-select">Sort By:</label>
                <select id="sort-select">
                    <option value="number-of-journal">Number of Journal</option>
                </select>
            </div>
            <div class="total-publishers-info">
                <span class="number"><?php echo number_format($total_results); ?></span>
                <span class="label">PUBLISHERS</span>
            </div>
        </div>
        
        <div class="publisher-list-wrapper">
            <?php
            // --- QUERY UTAMA: Mengambil nama penerbit dan jumlah artikelnya DARI `artikel_oai` ---
            $data_sql = "SELECT publisher, COUNT(*) as article_count 
                         FROM artikel_oai 
                         WHERE publisher IS NOT NULL AND publisher != '' 
                         GROUP BY publisher 
                         ORDER BY publisher ASC 
                         LIMIT ? OFFSET ?";
            
            $data_stmt = $conn->prepare($data_sql);
            if ($data_stmt) {
                $data_stmt->bind_param("ii", $results_per_page, $offset);
                $data_stmt->execute();
                $result = $data_stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $publisher_name = htmlspecialchars($row['publisher']);
                        $article_count = htmlspecialchars($row['article_count']);
                        $logo_url = 'Images/logo unila.png'; 
                        
                        echo '<a href="jurnal_penerbit.php?penerbit=' . urlencode($row['publisher']) . '" class="publisher-entry-card">';
                        echo '<div class="card-left">';
                        echo '<img src="' . $logo_url . '" alt="' . $publisher_name . ' Logo" class="publisher-card-logo">';
                        echo '<div class="publisher-card-info">';
                        echo '<h4>' . $publisher_name . '</h4>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="card-right">';
                        echo '<span class="journal-count">' . $article_count . '</span>';
                        echo '<span class="journal-label">Articles</span>';
                        echo '</div>';
                        echo '</a>';
                    }
                } else {
                    echo '<div style="width: 100%; text-align: center;"><p>Tidak ada data penerbit untuk ditampilkan.</p></div>';
                }
                $data_stmt->close();
            }
            ?>
        </div>

        <!-- ===== PAGINASI MODERN DITERAPKAN DI SINI ===== -->
        <nav class="pagination modern">
            <ul>
                <?php
                if ($total_pages > 1) {
                    // Tombol "Previous"
                    if ($page > 1) {
                        echo '<li><a href="penerbit.php?page=' . ($page - 1) . '">&laquo; Previous</a></li>';
                    }

                    // Logika untuk menampilkan nomor halaman (misal: 1 ... 4 5 6 ... 10)
                    $window = 2; // Jumlah nomor di sekitar halaman aktif
                    for ($i = 1; $i <= $total_pages; $i++) {
                        if ($i == 1 || $i == $total_pages || ($i >= $page - $window && $i <= $page + $window)) {
                            $active_class = ($i == $page) ? 'active' : '';
                            echo '<li><a href="penerbit.php?page=' . $i . '" class="' . $active_class . '">' . $i . '</a></li>';
                        } elseif ($i == $page - $window - 1 || $i == $page + $window + 1) {
                            echo '<li><span class="ellipsis">...</span></li>';
                        }
                    }

                    // Tombol "Next"
                    if ($page < $total_pages) {
                        echo '<li><a href="penerbit.php?page=' . ($page + 1) . '">Next &raquo;</a></li>';
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