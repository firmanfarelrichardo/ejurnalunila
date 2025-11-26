<?php 
include 'header.php';

// Daftar fakultas statis
$fakultas_list = [
    'Fakultas Teknik' => 'fas fa-cogs',
    'Fakultas Pertanian' => 'fas fa-seedling',
    'Fakultas Kedokteran' => 'fas fa-stethoscope',
    'Fakultas Hukum' => 'fas fa-gavel',
    'Fakultas Ilmu Sosial dan Ilmu Politik' => 'fas fa-users',
    'Fakultas Matematika dan Ilmu Pengetahuan Alam' => 'fas fa-flask',
    'Fakultas Keguruan dan Ilmu Pendidikan' => 'fas fa-chalkboard-teacher',
    'Fakultas Ekonomi dan Bisnis' => 'fas fa-chart-line'
];

$fakultas_abbreviations = [
    'Fakultas Teknik' => 'FT',
    'Fakultas Pertanian' => 'FP',
    'Fakultas Kedokteran' => 'FK',
    'Fakultas Hukum' => 'FH',
    'Fakultas Ilmu Sosial dan Ilmu Politik' => 'FISIP',
    'Fakultas Matematika dan Ilmu Pengetahuan Alam' => 'FMIPA',
    'Fakultas Keguruan dan Ilmu Pendidikan' => 'FKIP',
    'Fakultas Ekonomi dan Bisnis' => 'FEB'
];

require_once './database/config.php';
$conn = connect_to_database();

$journal_counts = [];
$raw_chart_data = [];
$raw_non_sinta = [];
$chart_data_json = '[]';

if (!$conn->connect_error) {

    // --- TOTAL JURNAL UNTUK KARTU ---
    $count_sql = "SELECT fakultas, COUNT(id) AS jumlah
                  FROM jurnal_sumber
                  WHERE fakultas IS NOT NULL AND fakultas != ''
                  GROUP BY fakultas";
    $result_counts = $conn->query($count_sql);
    if ($result_counts) {
        while ($row = $result_counts->fetch_assoc()) {
            $journal_counts[$row['fakultas']] = $row['jumlah'];
        }
    }

    // --- DATA SINTA 1 - 6 UNTUK CHART & TABEL ---
    $chart_sql = "SELECT fakultas, akreditasi_sinta, COUNT(id) AS jumlah
                  FROM jurnal_sumber
                  WHERE fakultas IS NOT NULL 
                    AND fakultas != '' 
                    AND akreditasi_sinta LIKE 'Sinta%'
                  GROUP BY fakultas, akreditasi_sinta";
    $result_chart = $conn->query($chart_sql);

    if ($result_chart) {
        while ($row = $result_chart->fetch_assoc()) {
            $raw_chart_data[$row['fakultas']][$row['akreditasi_sinta']] = $row['jumlah'];
        }
    }

    // --- DATA NON-SINTA ---
    $non_sinta_sql = "SELECT fakultas, COUNT(id) AS jumlah
                      FROM jurnal_sumber
                      WHERE fakultas IS NOT NULL 
                        AND fakultas != ''
                        AND (
                            akreditasi_sinta IS NULL 
                            OR akreditasi_sinta = ''
                            OR akreditasi_sinta NOT LIKE 'Sinta%'
                        )
                      GROUP BY fakultas";
    $result_non = $conn->query($non_sinta_sql);

    if ($result_non) {
        while ($row = $result_non->fetch_assoc()) {
            $raw_non_sinta[$row['fakultas']] = $row['jumlah'];
        }
    }

    // --- CHART DATA ---
    $fakultas_full_names = array_keys($fakultas_list);
    $chart_labels_short = array_values($fakultas_abbreviations);

    $sinta_levels = ['Sinta 1', 'Sinta 2', 'Sinta 3', 'Sinta 4', 'Sinta 5', 'Sinta 6'];
    $sinta_colors = [
        'Sinta 1' => 'rgba(217, 30, 24, 0.7)',
        'Sinta 2' => 'rgba(30, 139, 195, 0.7)',
        'Sinta 3' => 'rgba(241, 196, 15, 0.7)',
        'Sinta 4' => 'rgba(46, 204, 113, 0.7)',
        'Sinta 5' => 'rgba(142, 68, 173, 0.7)',
        'Sinta 6' => 'rgba(230, 126, 34, 0.7)'
    ];

    $datasets = [];
    foreach ($sinta_levels as $sinta) {
        $data_points = [];
        foreach ($fakultas_full_names as $fakultas) {
            $data_points[] = $raw_chart_data[$fakultas][$sinta] ?? 0;
        }

        $datasets[] = [
            'label' => $sinta,
            'data' => $data_points,
            'backgroundColor' => $sinta_colors[$sinta],
            'borderRadius' => 4,
        ];
    }

    $chart_data_final = [
        'labels' => $chart_labels_short,
        'datasets' => $datasets
    ];

    $chart_data_json = json_encode($chart_data_final);

    $conn->close();
}
?>

<main>
    <div class="page-title-container">
        <div class="container">
            <h1>Telusuri Berdasarkan Fakultas</h1>
            <p>Pilih fakultas untuk melihat semua jurnal yang berafiliasi.</p>
        </div>
    </div>

    <div class="container page-content">

        <!-- GRID FAKULTAS -->
        <div class="fakultas-grid">
            <?php foreach ($fakultas_list as $nama_lengkap => $icon): ?>
                <?php 
                    $nama_tampil = str_replace('Fakultas ', '', $nama_lengkap);
                    $jumlah_jurnal = $journal_counts[$nama_lengkap] ?? 0;
                ?>
                <a href="jurnal_fak.php?fakultas=<?= urlencode($nama_lengkap) ?>" class="fakultas-card">
                    <div class="fakultas-icon"><i class="<?= $icon ?>"></i></div>
                    <h3><?= htmlspecialchars($nama_tampil) ?></h3>
                    <span class="fakultas-link"><?= $jumlah_jurnal ?> Jurnal</span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- CHART -->
        <div class="stats-chart-container" style="margin-top: 50px;">
            <div class="spss-table-header">
                <h3>Statistik Jurnal Terakreditasi SINTA per Fakultas</h3>
            </div>
            <div class="chart">
                <canvas id="fakultasChart"></canvas>
            </div>
        </div>

        <!-- TABEL DETAIL -->
        <div class="stats-table-container spss-style" style="margin-top: 50px;">
            <div class="spss-table-header">
                <h3>Rincian Jurnal per Fakultas (SINTA + Non-SINTA)</h3>
            </div>

            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Nama Fakultas</th>
                        <th>Total</th>
                        <th>S1</th>
                        <th>S2</th>
                        <th>S3</th>
                        <th>S4</th>
                        <th>S5</th>
                        <th>S6</th>
                        <th>Non-SINTA</th>
                    </tr>
                </thead>

                <tbody>
                <?php
                    $grand_total = 0;
                    $sinta_totals = array_fill_keys($sinta_levels, 0);
                    $total_non_sinta = 0;

                    foreach ($fakultas_list as $nama_fakultas => $icon):

                        $s1 = $raw_chart_data[$nama_fakultas]['Sinta 1'] ?? 0;
                        $s2 = $raw_chart_data[$nama_fakultas]['Sinta 2'] ?? 0;
                        $s3 = $raw_chart_data[$nama_fakultas]['Sinta 3'] ?? 0;
                        $s4 = $raw_chart_data[$nama_fakultas]['Sinta 4'] ?? 0;
                        $s5 = $raw_chart_data[$nama_fakultas]['Sinta 5'] ?? 0;
                        $s6 = $raw_chart_data[$nama_fakultas]['Sinta 6'] ?? 0;

                        $non = $raw_non_sinta[$nama_fakultas] ?? 0;

                        $total = $s1 + $s2 + $s3 + $s4 + $s5 + $s6 + $non;

                        // SUM KESELURUHAN
                        $sinta_totals['Sinta 1'] += $s1;
                        $sinta_totals['Sinta 2'] += $s2;
                        $sinta_totals['Sinta 3'] += $s3;
                        $sinta_totals['Sinta 4'] += $s4;
                        $sinta_totals['Sinta 5'] += $s5;
                        $sinta_totals['Sinta 6'] += $s6;
                        $total_non_sinta += $non;

                        $grand_total += $total;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($nama_fakultas) ?></td>
                        <td><strong><?= $total ?></strong></td>
                        <td><?= $s1 ?></td>
                        <td><?= $s2 ?></td>
                        <td><?= $s3 ?></td>
                        <td><?= $s4 ?></td>
                        <td><?= $s5 ?></td>
                        <td><?= $s6 ?></td>
                        <td><?= $non ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>

                <tfoot>
                    <tr>
                        <th>Total Jurnal</th>
                        <th><?= $grand_total ?></th>
                        <th><?= $sinta_totals['Sinta 1'] ?></th>
                        <th><?= $sinta_totals['Sinta 2'] ?></th>
                        <th><?= $sinta_totals['Sinta 3'] ?></th>
                        <th><?= $sinta_totals['Sinta 4'] ?></th>
                        <th><?= $sinta_totals['Sinta 5'] ?></th>
                        <th><?= $sinta_totals['Sinta 6'] ?></th>
                        <th><?= $total_non_sinta ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('fakultasChart');
    if (ctx) {
        const chartData = <?= $chart_data_json ?>;

        new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Jumlah Jurnal Berdasarkan Peringkat SINTA' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                }
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>
