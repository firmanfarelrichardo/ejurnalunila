<?php
include 'header.php';

// --- KONEKSI DATABASE ---
$host = "localhost"; $user = "root"; $pass = ""; $db = "oai";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("<main class='page-container'><div class='container'><h1>Koneksi Database Gagal</h1><p>" . $conn->connect_error . "</p></div></main>");
}

// Mendapatkan huruf yang dipilih dari URL, defaultnya 'A'
$selected_char = isset($_GET['char']) ? strtoupper(substr($_GET['char'], 0, 1)) : 'A';
if (!ctype_alpha($selected_char)) {
    $selected_char = 'A';
}

// --- PENGATURAN PAGINASI ---
$results_per_page = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// --- Query untuk menghitung total artikel untuk huruf yang dipilih ---
$count_sql = "SELECT COUNT(*) FROM artikel_oai WHERE title LIKE ?";
$count_stmt = $conn->prepare($count_sql);
$search_char = $selected_char . '%';
$count_stmt->bind_param("s", $search_char);
$count_stmt->execute();
$total_results = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_results / $results_per_page);
$count_stmt->close();
?>

<title>Telusuri Artikel Berdasarkan Abjad - Portal Jurnal</title>

<main class="page-container">
    <div class="container">
        <div class="page-header">
            <h1>Telusuri Artikel (A-Z)</h1>
            <p>Temukan artikel berdasarkan urutan abjad judulnya.</p>
        </div>

        <nav class="alphabet-nav">
            <ul>
                <?php
                foreach (range('A', 'Z') as $char) {
                    $active_class = ($char == $selected_char) ? 'active' : '';
                    echo '<li><a href="subjek.php?char=' . $char . '" class="' . $active_class . '">' . $char . '</a></li>';
                }
                ?>
            </ul>
        </nav>

        <div class="search-results-list" style="margin-top: 30px;">
            <p>Menampilkan <strong><?php echo $total_results; ?></strong> artikel yang diawali dengan huruf "<strong><?php echo htmlspecialchars($selected_char); ?></strong>"</p>
            <hr>
            <?php
            // --- Query untuk mengambil data artikel sesuai abjad dan paginasi ---
            $data_sql = "SELECT title, publisher, creator1, creator2, creator3, identifier1, description 
                         FROM artikel_oai 
                         WHERE title LIKE ? 
                         ORDER BY title ASC 
                         LIMIT ? OFFSET ?";
            
            $data_stmt = $conn->prepare($data_sql);
            $data_stmt->bind_param("sii", $search_char, $results_per_page, $offset);
            $data_stmt->execute();
            $result = $data_stmt->get_result();

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="article-item">';
                    echo '  <h4><a href="' . htmlspecialchars($row['identifier1']) . '" target="_blank">' . htmlspecialchars($row['title']) . '</a></h4>';
                    $creators = array_filter([$row['creator1'], $row['creator2'], $row['creator3']]);
                    if (!empty($creators)) {
                        echo '  <p class="article-creator">Oleh: ' . htmlspecialchars(implode(', ', $creators)) . '</p>';
                    }
                    $description_snippet = substr(strip_tags($row['description'] ?? ''), 0, 300);
                    echo '  <p class="article-description">' . htmlspecialchars($description_snippet) . '...</p>';
                    echo '  <p class="article-source">Penerbit: ' . htmlspecialchars($row['publisher']) . '</p>';
                    echo '</div>';
                }
            } else {
                echo '<p>Tidak ada artikel yang ditemukan dengan awalan huruf "' . htmlspecialchars($selected_char) . '".</p>';
            }
            $data_stmt->close();
            ?>
        </div>

        <nav class="pagination modern">
            <ul>
                <?php
                if ($total_pages > 1) {
                    // Tombol "Previous"
                    if ($page > 1) {
                        echo '<li><a href="subjek.php?char=' . $selected_char . '&page=' . ($page - 1) . '">&laquo; Previous</a></li>';
                    }

                    // Logika untuk menampilkan nomor halaman (misal: 1 ... 4 5 6 ... 10)
                    $window = 2; // Jumlah nomor di sekitar halaman aktif
                    for ($i = 1; $i <= $total_pages; $i++) {
                        if ($i == 1 || $i == $total_pages || ($i >= $page - $window && $i <= $page + $window)) {
                            $active_class = ($i == $page) ? 'active' : '';
                            echo '<li><a href="subjek.php?char=' . $selected_char . '&page=' . $i . '" class="' . $active_class . '">' . $i . '</a></li>';
                        } elseif ($i == $page - $window - 1 || $i == $page + $window + 1) {
                            echo '<li><span class="ellipsis">...</span></li>';
                        }
                    }

                    // Tombol "Next"
                    if ($page < $total_pages) {
                        echo '<li><a href="subjek.php?char=' . $selected_char . '&page=' . ($page + 1) . '">Next &raquo;</a></li>';
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