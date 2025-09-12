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
                'Fakultas Teknik' => 'fas fa-cogs',
                'Fakultas Pertanian' => 'fas fa-seedling',
                'Fakultas Kedokteran' => 'fas fa-stethoscope',
                'Fakultas Hukum' => 'fas fa-gavel',
                'Fakultas Ilmu Sosial dan Ilmu Politik' => 'fas fa-users',
                'Fakultas Matematika dan Ilmu Pengetahuan Alam' => 'fas fa-flask',
                'Fakultas Keguruan dan Ilmu Pendidikan' => 'fas fa-chalkboard-teacher',
                'Fakultas Ekonomi dan Bisnis' => 'fas fa-chart-line'
            ];

            foreach ($fakultas_list as $nama_lengkap => $icon) {
                // Tampilkan hanya nama pendek di kartu
                $nama_pendek = str_replace('Fakultas ', '', $nama_lengkap);
                if (strpos($nama_pendek, 'dan') !== false) { // Logika untuk nama panjang
                    $nama_pendek = str_replace(['Ilmu Sosial dan Ilmu Politik', 'Keguruan dan Ilmu Pendidikan', 'Ekonomi dan Bisnis', 'Matematika dan Ilmu Pengetahuan Alam'], ['FISIP', 'FKIP', 'FEB', 'FMIPA'], $nama_lengkap);
                }

                // Kirim nama lengkap di URL
                echo '<a href="jurnal_fak.php?fakultas=' . urlencode($nama_lengkap) . '" class="fakultas-card">';
                echo '<div class="fakultas-icon"><i class="' . $icon . '"></i></div>';
                echo '<h3>' . htmlspecialchars($nama_pendek) . '</h3>'; // Tampilkan nama pendek
                echo '<span class="fakultas-link">Lihat Jurnal</span>';
                echo '</a>';
            }
            ?>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>