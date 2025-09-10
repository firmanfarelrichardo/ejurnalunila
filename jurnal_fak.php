<?php 
include 'header.php';

// --- BAGIAN PHP UNTUK MENGAMBIL DATA (TIDAK ADA PERUBAHAN) ---
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
$where_clauses = ["fakultas = ?"];
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

<main class="flex-shrink-0">
    <div class="container py-4">

        <div class="page-sub-header mb-4">
            <h1 class="h2 mb-0">Fakultas <?php echo htmlspecialchars($fakultas); ?></h1>
            <p class="text-muted mb-0">Universitas Lampung</p>
            <span class="fw-bold"><?php echo $total_results; ?> Jurnal Ditemukan</span>
        </div>

        <div class="filter-bar-container mb-4">
            <form action="jurnal_fak.php" method="GET" class="mini-search-form">
                <input type="hidden" name="fakultas" value="<?php echo htmlspecialchars($fakultas); ?>">
                <div class="input-group">
                    <input type="search" name="q" class="form-control" placeholder="Cari Jurnal, P_ISSN, E_ISSN..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button class="btn btn-danger" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
            <nav>
                <ul class="pagination mb-0">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                            <a class="page-link" href="?fakultas=<?php echo urlencode($fakultas); ?>&q=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>

        <div class="journal-list-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <div class="journal-list-item">
                    <div class="journal-item-content">
                        <a href="detail_jurnal.php?id=<?php echo $row['id']; ?>" class="journal-title-link">
                           <?php echo htmlspecialchars($row['judul_jurnal_asli']); ?>
                        </a>
                        <div class="journal-meta">
                            <span>Universitas Lampung</span>
                            <span>JOURNAL</span>
                            <span>ISSN : <?php echo htmlspecialchars($row['p_issn'] ?? '-'); ?></span>
                            <span>EISSN : <?php echo htmlspecialchars($row['e_issn'] ?? '-'); ?></span>
                        </div>
                        <div class="journal-tags">
                            <span class="tag"><?php echo htmlspecialchars($fakultas); ?></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-warning">Tidak ada jurnal yang ditemukan dengan kriteria Anda.</div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php 
$data_stmt->close();
$conn->close();
include 'footer.php'; 
?>