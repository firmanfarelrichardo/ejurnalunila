<?php include 'header.php'; ?>

<main>
    <div class="page-title-container">
        <div class="container">
            <h1>Telusuri Berdasarkan Fakultas</h1>
            <p>Pilih fakultas untuk melihat semua jurnal yang berafiliasi.</p>
        </div>
    </div>

    <div class="container page-content">
        <div class="fakultas-grid">
            <?php
            $fakultas_list = [
                'Teknik' => 'fas fa-cogs',
                'Pertanian' => 'fas fa-seedling',
                'Kedokteran' => 'fas fa-stethoscope',
                'Hukum' => 'fas fa-gavel',
                'Ilmu Sosial dan Politik' => 'fas fa-users',
                'MIPA' => 'fas fa-flask',
                'Keguruan dan Ilmu Pendidikan' => 'fas fa-chalkboard-teacher',
                'Ekonomi dan Bisnis' => 'fas fa-chart-line'
            ];

            foreach ($fakultas_list as $nama => $icon) {
                echo '<a href="jurnal_fakultas.php?fakultas=' . urlencode($nama) . '" class="fakultas-card">';
                echo '<div class="fakultas-icon"><i class="' . $icon . '"></i></div>';
                echo '<h3>' . htmlspecialchars($nama) . '</h3>';
                echo '<span class="fakultas-link">Lihat Artikel</span>';
                echo '</a>';
            }
            ?>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>