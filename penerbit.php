<?php
include 'header.php';

// --- PENGATURAN PAGINASI ---
$results_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// --- KONEKSI DATABASE ---
$host = "localhost"; $user = "root"; $pass = ""; $db = "oai";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("<main class='page-container'><div class='container'><h1>Koneksi Database Gagal</h1><p>" . $conn->connect_error . "</p></div></main>");
}

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
            <div class="publisher-search">
                <input type="text" placeholder="Publisher Name">
                <button type="submit"><i class="fas fa-search"></i></button>
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
                        $logo_url = 'https://unila.ac.id/storage/2024/09/logo-unila-min.png' . urlencode(strtoupper(substr($publisher_name, 0, 1)));
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

        <nav class="pagination">
            <ul>
                <?php
                if ($total_pages > 1) {
                    for ($i = 1; i <= $total_pages; $i++) {
                        $active_class = ($i == $page) ? 'active' : '';
                        echo '<li><a href="penerbit.php?page=' . $i . '" class="' . $active_class . '">' . $i . '</a></li>';
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