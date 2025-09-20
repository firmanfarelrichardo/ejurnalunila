<?php
include 'header.php';

// --- KONEKSI DATABASE ---
require_once './database/config.php';
$conn = connect_to_database();

// --- Query untuk mengambil data statistik artikel per tahun ---
$sql = "SELECT YEAR(date) as publication_year, COUNT(*) as article_count
        FROM artikel_oai
        WHERE date IS NOT NULL AND YEAR(date) > 1980
        GROUP BY publication_year
        ORDER BY publication_year ASC";

$result = $conn->query($sql);
$stats_data_for_chart = [];
$total_articles = 0;
$year_with_most_articles = null;
$max_articles_in_year = 0;
$max_count_for_chart = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stats_data_for_chart[] = $row;
        $total_articles += $row['article_count'];
        if ($row['article_count'] > $max_articles_in_year) {
            $max_articles_in_year = $row['article_count'];
            $year_with_most_articles = $row['publication_year'];
        }
        if ($row['article_count'] > $max_count_for_chart) {
            $max_count_for_chart = $row['article_count'];
        }
    }
}
$total_years = count($stats_data_for_chart);
$stats_data_for_table = array_reverse($stats_data_for_chart);
?>

<title>Statistik Publikasi - Portal Jurnal</title>

<main class="page-container">
    <div class="container">
        <div class="page-header">
            <h1>Statistik Publikasi Artikel</h1>
            <p>Ringkasan dan visualisasi data publikasi artikel berdasarkan tahun rilis.</p>
        </div>

        <?php if (!empty($stats_data_for_chart)): ?>
        
        <div class="stats-summary-grid">
            <div class="summary-card">
                <i class="fas fa-file-alt"></i>
                <div class="summary-info">
                    <span class="number"><?php echo number_format($total_articles); ?></span>
                    <span class="label">Total Artikel</span>
                </div>
            </div>
            <div class="summary-card">
                <i class="fas fa-calendar-alt"></i>
                <div class="summary-info">
                    <span class="number"><?php echo $total_years; ?></span>
                    <span class="label">Total Tahun</span>
                </div>
            </div>
            <div class="summary-card">
                <i class="fas fa-chart-line"></i>
                <div class="summary-info">
                    <span class="number"><?php echo $year_with_most_articles; ?></span>
                    <span class="label">Tahun Paling Produktif</span>
                </div>
            </div>
             <div class="summary-card">
                <i class="fas fa-arrow-up"></i>
                <div class="summary-info">
                    <span class="number"><?php echo number_format($max_articles_in_year); ?></span>
                    <span class="label">Publikasi Tertinggi</span>
                </div>
            </div>
        </div>

        <div class="stats-chart-container">
             <div class="spss-table-header">
                <h3>Grafik Publikasi per Tahun</h3>
            </div>
            <div class="chart">
                <?php foreach ($stats_data_for_chart as $data): ?>
                    <?php
                        $bar_height = ($max_count_for_chart > 0) ? ($data['article_count'] / $max_count_for_chart) * 100 : 0;
                    ?>
                    <div class="chart-bar-wrapper">
                        <div class="bar-value"><?php echo $data['article_count']; ?></div>
                        <div class="bar" style="height: <?php echo $bar_height; ?>%;"></div>
                        <div class="bar-label"><?php echo $data['publication_year']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="stats-table-container spss-style">
            <div class="spss-table-header">
                <h3>Data Rinci</h3>
            </div>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Tahun Publikasi</th>
                        <th>N (Jumlah Artikel)</th>
                        <th>Percent (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats_data_for_table as $data): ?>
                    <?php
                        $percentage = ($total_articles > 0) ? ($data['article_count'] / $total_articles) * 100 : 0;
                    ?>
                    <tr>
                        <td><?php echo $data['publication_year']; ?></td>
                        <td><?php echo number_format($data['article_count']); ?></td>
                        <td><?php echo number_format($percentage, 2); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th><?php echo number_format($total_articles); ?></th>
                        <th>100.00%</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php else: ?>
            <p>Tidak ada data statistik yang dapat ditampilkan saat ini.</p>
        <?php endif; ?>

    </div>
</main>

<?php
$conn->close();
include 'footer.php';
?>
</body>
</html>