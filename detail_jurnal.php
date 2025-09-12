<?php 
include 'header.php';

// --- PENGAMBILAN PARAMETER ---
$journal_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$article_search = isset($_GET['q_article']) ? trim($_GET['q_article']) : '';
$year_filter = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$issue_filter = isset($_GET['issue']) ? trim($_GET['issue']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$results_per_page = 10;
$offset = ($page - 1) * $results_per_page;

if ($journal_id === 0) {
    echo "<main><div class='container my-5'><h1>Jurnal tidak ditemukan.</h1></div></main>";
    include 'footer.php'; exit();
}

// --- KONEKSI & AMBIL DATA JURNAL ---
$host = "localhost"; $user = "root"; $pass = ""; $db = "oai";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// Menggunakan nama kolom dari database baru Anda
$stmt_journal = $conn->prepare("SELECT * FROM jurnal_sumber WHERE id = ?");
$stmt_journal->bind_param("i", $journal_id);
$stmt_journal->execute();
$journal = $stmt_journal->get_result()->fetch_assoc();
$stmt_journal->close();

if (!$journal) {
    echo "<main><div class='container my-5'><h1>Jurnal tidak ditemukan.</h1></div></main>";
    include 'footer.php'; exit();
}

// --- LOGIKA PENCARIAN & PAGINATION ARTIKEL ---
$article_base_sql = "FROM artikel_oai WHERE journal_source_id = ?";
$param_types = "i";
$param_values = [$journal_id];

if (!empty($article_search)) {
    $article_base_sql .= " AND (title LIKE ? OR creator1 LIKE ?)";
    $param_types .= "ss";
    $search_term = "%" . $article_search . "%";
    array_push($param_values, $search_term, $search_term);
}

if ($year_filter > 0) {
    $article_base_sql .= " AND YEAR(date) = ?";
    $param_types .= "i";
    array_push($param_values, $year_filter);
}

if (!empty($issue_filter)) {
    $article_base_sql .= " AND source1 LIKE ?";
    $param_types .= "s";
    array_push($param_values, "%" . $issue_filter . "%");
}

// Hitung total artikel
$count_sql = "SELECT COUNT(*) " . $article_base_sql;
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($param_types, ...$param_values);
$count_stmt->execute();
$total_articles = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_articles / $results_per_page);
$count_stmt->close();

// Ambil data artikel
$data_sql = "SELECT title, creator1, creator2, creator3, identifier1, description, source1, date " . $article_base_sql . " ORDER BY date DESC, id DESC LIMIT ? OFFSET ?";
$param_types .= "ii";
array_push($param_values, $results_per_page, $offset);
$data_stmt = $conn->prepare($data_sql);
$data_stmt->bind_param($param_types, ...$param_values);
$data_stmt->execute();
$articles_result = $data_stmt->get_result();

// Ambil tahun untuk filter
$year_sql = "SELECT DISTINCT YEAR(date) as year FROM artikel_oai WHERE journal_source_id = ? AND date IS NOT NULL ORDER BY year DESC";
$year_stmt = $conn->prepare($year_sql);
$year_stmt->bind_param("i", $journal_id);
$year_stmt->execute();
$years_result = $year_stmt->get_result();

// Ambil issues untuk filter
$issue_sql = "SELECT DISTINCT source1 FROM artikel_oai WHERE journal_source_id = ? AND source1 IS NOT NULL ORDER BY source1 DESC LIMIT 20";
$issue_stmt = $conn->prepare($issue_sql);
$issue_stmt->bind_param("i", $journal_id);
$issue_stmt->execute();
$issues_result = $issue_stmt->get_result();

// Generate dummy data untuk chart (dalam implementasi nyata, ambil dari database)
$current_year = date('Y');
$start_year = 2010; // Ganti dengan tahun mulai dari database
$chart_data = [];
for ($y = $start_year; $y <= $current_year; $y++) {
    $chart_data[] = ['year' => $y, 'visits' => rand(20, 100)];
}
?>

<style>
    .page-container { background-color: #f8f9fa; min-height: 100vh; }
    .main-layout { display: flex; gap: 20px; margin-top: 20px; }
    
    /* Sidebar Styles */
    .sidebar-left { flex: 0 0 250px; }
    .sidebar-right {flex: 0 0 380px; }
    .sidebar-card { background: white; border-radius: 8px; padding: 15px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .sidebar-card h6 { font-size: 14px; font-weight: 600; margin-bottom: 15px; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 8px; }
    
    /* Main Content */
    .main-content { flex: 1; min-width: 0; }
    
    /* Cover Section */
    .cover-container { text-align: center; }
    .cover-image { width: 100%; max-width: 180px; height: auto; border-radius: 4px; box-shadow: 0 3px 6px rgba(0,0,0,0.15); }
    
    /* Chart Container */
    .chart-container { height: 200px; }
    
    /* External Links */
    .external-links { list-style: none; padding: 0; margin: 0; }
    .external-links li { margin-bottom: 10px; }
    .external-links a { 
        display: flex; align-items: center; padding: 8px 12px; 
        background: #f8f9fa; border-radius: 4px; text-decoration: none; 
        color: #333; transition: all 0.3s; font-size: 14px;
    }
    .external-links a:hover { background: #007bff; color: white; transform: translateX(5px); }
    .external-links i { margin-right: 8px; width: 20px; text-align: center; }
    
    /* Contact Info */
    .contact-info p { margin-bottom: 8px; font-size: 14px; }
    .contact-info i { color: #007bff; margin-right: 8px; width: 20px; text-align: center; }
    
    /* Filter Section */
    .filter-section { margin-bottom: 15px; }
    .year-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; max-height: 300px; overflow-y: auto; }
    .year-btn, .issue-btn { 
        padding: 6px 10px; background: #f8f9fa; border: 1px solid #dee2e6; 
        border-radius: 4px; text-decoration: none; color: #333; 
        font-size: 13px; text-align: center; transition: all 0.2s;
    }
    .year-btn:hover, .issue-btn:hover { background: #007bff; color: white; border-color: #007bff; }
    .year-btn.active, .issue-btn.active { background: #007bff; color: white; border-color: #007bff; }
    .issue-list { max-height: 400px; overflow-y: auto; }
    .issue-item { 
        padding: 8px; margin-bottom: 8px; background: #f8f9fa; 
        border-radius: 4px; font-size: 13px; cursor: pointer; transition: all 0.2s;
    }
    .issue-item:hover { background: #e9ecef; }
    
    /* Journal Details Card */
    .journal-header { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .journal-title { font-size: 24px; font-weight: 600; color: #2c3e50; margin-bottom: 20px; }
    .journal-meta { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 15px; }
    .meta-item { font-size: 14px; }
    .meta-item strong { color: #666; }
    .journal-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 15px; }
    .tag { 
        padding: 4px 12px; background: #e3f2fd; color: #1976d2; 
        border-radius: 15px; font-size: 12px; font-weight: 500;
    }
    
    /* Article List */
    .articles-card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .articles-header { 
        display: flex; justify-content: between; align-items: center; 
        padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; margin-bottom: 20px;
    }
    .article-item { padding: 20px 10px; border-bottom: 1px solid #f0f0f0; }
    .article-item:last-child { border-bottom: none; }
    .article-title { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
    .article-title a { color: #2c3e50; text-decoration: none; }
    .article-title a:hover { color: #007bff; }
    .article-authors { color: #666; font-size: 14px; margin-bottom: 8px; }
    .article-abstract { color: #777; font-size: 14px; line-height: 1.6; margin-bottom: 8px; }
    .article-source { color: #999; font-size: 13px; font-style: italic; }
    
    /* Search Form */
    .search-form { flex: 1; max-width: 400px; margin-left: auto; }
    
    /* Pagination */
    .pagination { margin-top: 20px; }

    .cari-artikel {max-width: 400px; margin-left: auto;}

    .input-group-lg .form-control {
    height: 28px; /* Anda bisa sesuaikan nilainya */
    font-size: 1rem; /* Perbesar sedikit ukuran font placeholder & input */
    border-radius: 5px;
}
    .input-group-lg .btn {
        height: 28px; /* Pastikan tingginya SAMA dengan .form-control */
        font-size: 0.8rem;
        border-radius: 4px
    }
</style>

<main class="page-container">
    <div class="container-fluid">
        <h1 class="journal-title mt-4"><?php echo htmlspecialchars($journal['judul_jurnal_asli']); ?></h1>
        
        <div class="main-layout">
            <!-- LEFT SIDEBAR -->
            <div class="sidebar-left">
                <!-- Cover -->
                <div class="sidebar-card cover-container">
                    <img src="<?php echo htmlspecialchars(!empty($journal['url_cover']) ? $journal['url_cover'] : 'https://via.placeholder.com/180x250.png?text=Cover'); ?>" 
                         class="cover-image" alt="Journal Cover">
                </div>
                
                <!-- Visit Statistics Chart -->
                <div class="sidebar-card">
                    <h6><i class="fas fa-chart-line"></i> Statistik Kunjungan</h6>
                    <div class="chart-container">
                        <canvas id="visitChart"></canvas>
                    </div>
                </div>
                
                <!-- External Links -->
                <div class="sidebar-card">
                    <h6><i class="fas fa-link"></i> Tautan Eksternal</h6>
                    <ul class="external-links">
                        <li><a href="<?php echo htmlspecialchars($journal['website_url']); ?>" target="_blank">
                            <i class="fas fa-globe"></i> Website OAI</a></li>
                        <li><a href="<?php echo htmlspecialchars($journal['website_url']); ?>/about/editorialTeam" target="_blank">
                            <i class="fas fa-users"></i> Editorial Team</a></li>
                        <li><a href="https://scholar.google.com/scholar?q=<?php echo urlencode($journal['judul_jurnal_asli']); ?>" target="_blank">
                            <i class="fas fa-graduation-cap"></i> Google Scholar</a></li>
                        <li><a href="https://sinta.kemdikbud.go.id/journals/search?q=<?php echo urlencode($journal['judul_jurnal_asli']); ?>" target="_blank">
                            <i class="fas fa-award"></i> SINTA</a></li>
                    </ul>
                </div>
                
                <!-- Contact Information -->
                <div class="sidebar-card">
                    <h6><i class="fas fa-address-card"></i> Informasi Kontak</h6>
                    <div class="contact-info">
                        <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($journal['nama_kontak'] ?? 'Tidak tersedia'); ?></p>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($journal['email_kontak'] ?? 'Tidak tersedia'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- MAIN CONTENT -->
            <div class="main-content">
                <!-- Journal Details -->
                <div class="journal-header">
                    <div class="journal-meta">
                        <div class="meta-item"><strong>Penerbit:</strong> <?php echo htmlspecialchars($journal['penerbit'] ?? 'Universitas Lampung'); ?></div>
                        <div class="meta-item"><strong>P-ISSN:</strong> <?php echo htmlspecialchars($journal['p_issn'] ?? '-'); ?></div>
                        <div class="meta-item"><strong>E-ISSN:</strong> <?php echo htmlspecialchars($journal['e_issn'] ?? '-'); ?></div>
                        <div class="meta-item"><strong>DOI:</strong> <?php echo htmlspecialchars($journal['doi'] ?? '-'); ?></div>
                    </div>
                    <div class="journal-tags">
                        <span class="tag"><?php echo htmlspecialchars($journal['fakultas']); ?></span>
                        <span class="tag"><?php echo htmlspecialchars($journal['subject_arjuna']); ?></span>
                        <span class="tag"><?php echo htmlspecialchars($journal['sub_subject_arjuna']); ?></span>
                    </div>
                    <?php if (!empty($journal['aim_and_scope'])): ?>
                    <div style="margin-top: 20px;">
                        <strong>Tentang Jurnal: </strong>
                        <p style="margin-top: 10px; color: #666; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($journal['aim_and_scope'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Articles List -->
                <div class="articles-card">
                    <div class="articles-header">
                        <h5 style="margin: 0;">Artikel (<?php echo $total_articles; ?>)</h5>
                        <form action="detail_jurnal.php" method="GET" class="cari-artikel">
                            <input type="hidden" name="id" value="<?php echo $journal_id; ?>">
                            <?php if ($year_filter): ?>
                                <input type="hidden" name="year" value="<?php echo $year_filter; ?>">
                            <?php endif; ?>
                            <?php if ($issue_filter): ?>
                                <input type="hidden" name="issue" value="<?php echo htmlspecialchars($issue_filter); ?>">
                            <?php endif; ?>
                            <div class="input-group input-group-lg">
                                <input type="search" name="q_article" class="form-control" 
                                       placeholder="Cari artikel..." 
                                       value="<?php echo htmlspecialchars($article_search); ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <?php if ($articles_result->num_rows > 0): ?>
                        <?php while ($article = $articles_result->fetch_assoc()): ?>
                            <div class="article-item">
                                <div class="article-title">
                                    <?php $link = !empty($article['identifier1']) ? $article['identifier1'] : '#'; ?>
                                    <a href="<?php echo htmlspecialchars($link); ?>" target="_blank">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </div>
                                <div class="article-authors">
                                    <?php 
                                        $creators = array_filter([$article['creator1'], $article['creator2'], $article['creator3']]);
                                        echo htmlspecialchars(implode(', ', $creators));
                                    ?>
                                </div>
                                <?php if (!empty($article['description'])): ?>
                                <div class="article-abstract">
                                    <?php 
                                        $description = strip_tags($article['description']);
                                        echo htmlspecialchars(substr($description, 0, 300)) . (strlen($description) > 300 ? '...' : '');
                                    ?>
                                </div>
                                <?php endif; ?>
                                <div class="article-source">
                                    <?php echo htmlspecialchars($article['source1']); ?>
                                    <?php if (!empty($article['date'])): ?>
                                        | <?php echo date('Y', strtotime($article['date'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        
                        <?php if ($total_pages > 1): ?>
                        <nav class="pagination justify-content-center">
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                        <a class="page-link" href="?id=<?php echo $journal_id; ?>&q_article=<?php echo urlencode($article_search); ?>&year=<?php echo $year_filter; ?>&issue=<?php echo urlencode($issue_filter); ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($total_pages > 10): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <li class="page-item">
                                        <a class="page-link" href="?id=<?php echo $journal_id; ?>&q_article=<?php echo urlencode($article_search); ?>&year=<?php echo $year_filter; ?>&issue=<?php echo urlencode($issue_filter); ?>&page=<?php echo $total_pages; ?>">
                                            <?php echo $total_pages; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #999; padding: 40px 0;">
                            Tidak ada artikel yang ditemukan untuk kriteria pencarian ini.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- RIGHT SIDEBAR -->
            <div class="sidebar-right">
                <!-- Year Filter -->
                <div class="sidebar-card">
                    <h6><i class="fas fa-calendar"></i> Filter Berdasarkan Tahun</h6>
                    <div class="filter-section">
                        <div class="year-grid">
                            <a href="?id=<?php echo $journal_id; ?>" 
                               class="year-btn <?php echo $year_filter == 0 ? 'active' : ''; ?>">Semua</a>
                            <?php while ($year_row = $years_result->fetch_assoc()): ?>
                                <?php if ($year_row['year']): ?>
                                <a href="?id=<?php echo $journal_id; ?>&year=<?php echo $year_row['year']; ?>" 
                                   class="year-btn <?php echo $year_filter == $year_row['year'] ? 'active' : ''; ?>">
                                    <?php echo $year_row['year']; ?>
                                </a>
                                <?php endif; ?>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Issue Filter -->
                <div class="sidebar-card">
                    <h6><i class="fas fa-book"></i> Filter Berdasarkan Issue</h6>
                    <div class="issue-list">
                        <div class="issue-item" onclick="window.location.href='?id=<?php echo $journal_id; ?>'">
                            <strong>Semua Issue</strong>
                        </div>
                        <?php while ($issue_row = $issues_result->fetch_assoc()): ?>
                            <?php if (!empty($issue_row['source1'])): ?>
                            <div class="issue-item" onclick="window.location.href='?id=<?php echo $journal_id; ?>&issue=<?php echo urlencode($issue_row['source1']); ?>'">
                                <?php echo htmlspecialchars($issue_row['source1']); ?>
                            </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Chart.js untuk statistik -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart untuk statistik kunjungan
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('visitChart');
    if (ctx) {
        const chartData = <?php echo json_encode($chart_data); ?>;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.map(d => d.year),
                datasets: [{
                    label: 'Kunjungan',
                    data: chartData.map(d => d.visits),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 20
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            autoSkip: true,
                            maxTicksLimit: 8
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php 
$data_stmt->close();
$year_stmt->close();
$issue_stmt->close();
$conn->close();
include 'footer.php'; 
?>