<?php
include 'header.php';

// --- PENGATURAN & PENGAMBILAN PARAMETER ---
$results_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$penerbit = isset($_GET['penerbit']) ? trim(urldecode($_GET['penerbit'])) : '';
$offset = ($page - 1) * $results_per_page;

if (empty($penerbit)) {
    echo "<main class='page-container'><div class='container'><h1>Penerbit tidak ditemukan.</h1></div></main></body></html>";
    exit();
}

// --- KONEKSI DATABASE ---
$host = "localhost"; $user = "root"; $pass = ""; $db = "oai";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// --- QUERY UNTUK MENGHITUNG TOTAL HASIL (UNTUK PAGINASI) DARI `artikel_oai` ---
$count_sql = "SELECT COUNT(*) FROM artikel_oai WHERE publisher = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("s", $penerbit);
$count_stmt->execute();
$total_results = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_results / $results_per_page);
$count_stmt->close();
?>

<title>Artikel dari Penerbit "<?php echo htmlspecialchars($penerbit); ?>"</title>

<main class="page-container">
    <div class="container">
        <div class="page-header">
            <h1>Penerbit: <?php echo htmlspecialchars($penerbit); ?></h1>
            <p>Menampilkan semua artikel dari penerbit ini.</p>
        </div>

        <p>Ditemukan <?php echo $total_results; ?> artikel.</p><hr>

        <div class="search-results-list">
            <?php
            // --- QUERY UTAMA UNTUK MENGAMBIL DATA ARTIKEL DARI `artikel_oai` ---
            $data_sql = "SELECT title, description, creator1, creator2, creator3, source1, identifier1 
                         FROM artikel_oai WHERE publisher = ? LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($data_sql);
            $stmt->bind_param("sii", $penerbit, $results_per_page, $offset);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($total_results > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="article-item">';
                    echo '<h4><a href="' . htmlspecialchars($row['identifier1']) . '" target="_blank">' . htmlspecialchars($row['title']) . '</a></h4>';
                    $creators = array_filter([$row['creator1'], $row['creator2'], $row['creator3']]);
                    if (!empty($creators)) {
                        echo '<p class="article-creator">Oleh: ' . htmlspecialchars(implode(', ', $creators)) . '</p>';
                    }
                    $description_snippet = substr(strip_tags($row['description']), 0, 300);
                    echo '<p class="article-description">' . htmlspecialchars($description_snippet) . '...</p>';
                    echo '<p class="article-source">Sumber: ' . htmlspecialchars($row['source1']) . '</p>';
                    echo '</div>';
                }
            } else {
                echo "<p>Tidak ada artikel yang cocok dengan penerbit ini.</p>";
            }
            $stmt->close();
            ?>
        </div>

        <nav class="pagination">
            <ul>
                <?php
                if ($total_pages > 1) {
                    for ($i = 1; i <= $total_pages; $i++) {
                        $active_class = ($i == $page) ? 'active' : '';
                        echo '<li><a href="jurnal_penerbit.php?penerbit=' . urlencode($penerbit) . '&page=' . $i . '" class="' . $active_class . '">' . $i . '</a></li>';
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