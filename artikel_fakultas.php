<?php 
include 'header.php';

// --- PENGATURAN & PENGAMBILAN PARAMETER ---
$results_per_page = 10;
$fakultas = isset($_GET['fakultas']) ? trim($_GET['fakultas']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

if (empty($fakultas)) {
    echo "<main><div class='container my-5'><h1>Fakultas tidak valid.</h1><a href='fakultas.php'>Kembali ke daftar fakultas</a></div></main>";
    include 'footer.php';
    exit();
}

// --- LOGIKA DATABASE ---
require_once './database/config.php';
$conn = connect_to_database();

$base_sql = "FROM artikel_oai a JOIN jurnal_sumber j ON a.source1 = j.journal_title COLLATE utf8mb4_unicode_ci";
$where_clauses = ["j.fakultas = ?"];
$param_types = "s";
$param_values = [$fakultas];

if (!empty($search_query)) {
    $where_clauses[] = "(a.title LIKE ? OR a.description LIKE ? OR a.creator1 LIKE ?)";
    $param_types .= "sss";
    $search_term = "%" . $search_query . "%";
    array_push($param_values, $search_term, $search_term, $search_term);
}
$where_sql = " WHERE " . implode(" AND ", $where_clauses);

// Query untuk menghitung total hasil
// PERBAIKAN: Tambahkan spasi sebelum $base_sql
$count_sql = "SELECT COUNT(a.id) " . $base_sql . $where_sql;
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($param_types, ...$param_values);
$count_stmt->execute();
$total_results = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_results / $results_per_page);
$count_stmt->close();

// Query utama untuk mengambil data artikel
// PERBAIKAN: Tambahkan spasi sebelum $base_sql
$data_sql = "SELECT a.title, a.description, a.creator1, a.creator2, a.source1, a.identifier1 " . $base_sql . $where_sql . " ORDER BY a.id DESC LIMIT ? OFFSET ?";
$param_types .= "ii";
array_push($param_values, $results_per_page, $offset);

$data_stmt = $conn->prepare($data_sql);
$data_stmt->bind_param($param_types, ...$param_values);
$data_stmt->execute();
$result = $data_stmt->get_result();

?>

<main class="flex-shrink-0">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <h1 class="h2">Fakultas <?php echo htmlspecialchars($fakultas); ?></h1>
                <p class="mb-0 text-muted">Ditemukan <?php echo $total_results; ?> artikel.</p>
            </div>
        </div>
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <form action="artikel_fakultas.php" method="GET" class="d-flex">
                    <input type="hidden" name="fakultas" value="<?php echo htmlspecialchars($fakultas); ?>">
                    <input type="search" name="q" class="form-control" placeholder="Cari artikel di fakultas ini..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-danger ms-2">Cari</button>
                </form>
            </div>
            <div class="col-md-6">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-md-end mb-0">
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
        </div>
        <div class="list-group">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <a href="<?php echo htmlspecialchars($row['identifier1']); ?>" target="_blank" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?php echo htmlspecialchars($row['title']); ?></h5>
                        </div>
                        <?php 
                            $creators = array_filter([$row['creator1'], $row['creator2']]);
                            echo '<p class="mb-1 text-muted"><em>' . htmlspecialchars(implode(', ', $creators)) . '</em></p>';
                        ?>
                        <small>Sumber: <?php echo htmlspecialchars($row['source1']); ?></small>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-warning">Tidak ada artikel yang ditemukan dengan kriteria Anda.</div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php 
$data_stmt->close();
$conn->close();
include 'footer.php'; 
?>