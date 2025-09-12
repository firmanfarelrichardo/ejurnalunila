<?php 
include 'header.php';

// --- BAGIAN PHP UNTUK MENGAMBIL DATA ---
$results_per_page = 10;
$fakultas = isset($_GET['fakultas']) ? trim($_GET['fakultas']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

if (empty($fakultas)) {
    echo "<main><div class='container my-5'><h1>Fakultas tidak valid.</h1></div></main>";
    include 'footer.php';
    exit();
}

$host = "localhost"; $user = "root"; $pass = ""; $db = "oai";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

$base_sql = "FROM jurnal_sumber";
$where_clauses = ["fakultas = ?"]; // Gunakan '=' untuk pencocokan persis
$param_types = "s";
$param_values = [$fakultas];

if (!empty($search_query)) {
    $where_clauses[] = "(judul_jurnal_asli LIKE ? OR p_issn LIKE ? OR e_issn LIKE ?)";
    $param_types .= "sss";
    $search_term = "%" . $search_query . "%";
    array_push($param_values, $search_term, $search_term, $search_term);
}
$where_sql = " WHERE " . implode(" AND ", $where_clauses);

$count_sql = "SELECT COUNT(*) " . $base_sql . $where_sql;
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($param_types, ...$param_values);
$count_stmt->execute();
$total_results = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_results / $results_per_page);
$count_stmt->close();

$data_sql = "SELECT id, judul_jurnal_asli, penerbit, p_issn, e_issn, url_cover " . $base_sql . $where_sql . " ORDER BY judul_jurnal_asli ASC LIMIT ? OFFSET ?";
$param_types .= "ii";
array_push($param_values, $results_per_page, $offset);

$data_stmt = $conn->prepare($data_sql);
$data_stmt->bind_param($param_types, ...$param_values);
$data_stmt->execute();
$result = $data_stmt->get_result();
?>

<!-- Include CSS khusus untuk halaman ini -->
<link rel="stylesheet" href="css/jurnal_fak.css">

<main class="flex-shrink-0">
    <div class="container py-4">

        <!-- Header Halaman -->
        <div class="page-header">
            <h1 class="h2 mb-0"><?php echo htmlspecialchars($fakultas); ?></h1>
            <p class="text-muted mb-0">Universitas Lampung</p>
        </div>

        <!-- Container Pencarian dengan styling sesuai CSS yang diberikan -->
        <div class="re-search-container">
            <form action="jurnal_fak.php" method="GET">
                <input type="hidden" name="fakultas" value="<?php echo htmlspecialchars($fakultas); ?>">
                <input type="search" name="q" placeholder="Cari Jurnal, ISSN, EISSN..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit"><i class="fas fa-search"></i> Cari</button>
            </form>
        </div>

        <!-- Jumlah Hasil -->
        <div class="results-count">
            <strong><?php echo $total_results; ?></strong> jurnal ditemukan
        </div>

        <!-- Daftar Jurnal dengan styling sesuai CSS yang diberikan -->
        <div class="search-results-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <div class="article-item">
                    <h4>
                        <a href="detail_jurnal.php?id=<?php echo $row['id']; ?>">
                            <?php echo htmlspecialchars($row['judul_jurnal_asli']); ?>
                        </a>
                    </h4>
                    
                    <div class="article-creator">
                        Oleh: <?php echo htmlspecialchars($row['penerbit'] ?? '-'); ?>
                    </div>
                    
                    <div class="article-description">
                        <?php 
                        // Deskripsi jurnal - bisa dari abstrak atau informasi lainnya
                        $description = "Jurnal ini diterbitkan oleh " . htmlspecialchars($row['penerbit'] ?? 'Penerbit tidak diketahui') . ".";
                        
                        if (!empty($row['p_issn']) || !empty($row['e_issn'])) {
                            $description .= " Tersedia dengan";
                            if (!empty($row['p_issn'])) {
                                $description .= " ISSN: " . htmlspecialchars($row['p_issn']);
                            }
                            if (!empty($row['e_issn'])) {
                                $description .= (!empty($row['p_issn']) ? " dan" : "") . " E-ISSN: " . htmlspecialchars($row['e_issn']);
                            }
                            $description .= ".";
                        }
                        
                        echo $description;
                        ?>
                    </div>
                    
                    <div class="article-source">
                        Penerbit: <?php echo htmlspecialchars($row['penerbit'] ?? 'Tidak diketahui'); ?>
                        <?php if (!empty($row['url_cover'])): ?>
                            | <a href="<?php echo htmlspecialchars($row['url_cover']); ?>" target="_blank">Lihat Cover</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-warning">Tidak ada jurnal yang ditemukan dengan kriteria Anda.</div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="custom-pagination">
            <?php 
            // Previous button
            if ($page > 1): ?>
                <a href="?fakultas=<?php echo urlencode($fakultas); ?>&q=<?php echo urlencode($search_query); ?>&page=<?php echo ($page - 1); ?>" class="page-btn">‹ Prev</a>
            <?php else: ?>
                <span class="page-btn disabled">‹ Prev</span>
            <?php endif; ?>

            <?php
            // Calculate page range to show
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            // Show first page if we're not starting from page 1
            if ($start_page > 1) {
                echo '<a href="?fakultas=' . urlencode($fakultas) . '&q=' . urlencode($search_query) . '&page=1" class="page-btn">1</a>';
                if ($start_page > 2) {
                    echo '<span class="page-btn disabled">...</span>';
                }
            }
            
            // Show page numbers
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?fakultas=<?php echo urlencode($fakultas); ?>&q=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>" 
                   class="page-btn <?php if ($i == $page) echo 'active'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor;
            
            // Show last page if we're not ending at the last page
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="page-btn disabled">...</span>';
                }
                echo '<a href="?fakultas=' . urlencode($fakultas) . '&q=' . urlencode($search_query) . '&page=' . $total_pages . '" class="page-btn">' . $total_pages . '</a>';
            }
            ?>

            <?php 
            // Next button
            if ($page < $total_pages): ?>
                <a href="?fakultas=<?php echo urlencode($fakultas); ?>&q=<?php echo urlencode($search_query); ?>&page=<?php echo ($page + 1); ?>" class="page-btn">Next ›</a>
            <?php else: ?>
                <span class="page-btn disabled">Next ›</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <br>
    </div>
</main>

<?php 
$data_stmt->close();
$conn->close();
include 'footer.php'; 
?>