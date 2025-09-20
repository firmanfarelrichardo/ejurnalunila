<?php
echo "<pre style='font-family: monospace; line-height: 1.5; color: #333; background-color: #f5f5f5; padding: 15px;'>";
set_time_limit(0); // Izinkan skrip berjalan lama

require_once './database/config.php';
$conn = connect_to_database();

echo "<strong>Memulai proses sinkronisasi data...</strong>\n";

// 1. Ambil semua jurnal sumber sebagai referensi
$jurnals = [];
// Gunakan nama kolom yang benar dari database baru Anda: 'judul_jurnal_asli'
$result_jurnals = $conn->query("SELECT id, judul_jurnal_asli FROM jurnal_sumber");
while($row = $result_jurnals->fetch_assoc()) {
    $jurnals[] = $row;
}
echo "Referensi " . count($jurnals) . " jurnal berhasil diambil.\n\n";

// 2. Ambil semua artikel yang belum disinkronkan
$result_articles = $conn->query("SELECT id, source1 FROM artikel_oai WHERE journal_source_id IS NULL");
echo "<strong>Memproses " . $result_articles->num_rows . " artikel...</strong>\n";

$update_count = 0;
while($article = $result_articles->fetch_assoc()) {
    $source1_text = $article['source1'];
    $matched_journal_id = null;

    // 3. Cari jurnal yang paling cocok dari referensi
    foreach($jurnals as $jurnal) {
        // Cek apakah judul jurnal (dari jurnal_sumber) ada di dalam string source1 (dari artikel_oai)
        if (strpos($source1_text, $jurnal['judul_jurnal_asli']) !== false) {
            $matched_journal_id = $jurnal['id'];
            break; // Hentikan jika sudah ketemu
        }
    }

    // 4. Jika ditemukan, update baris artikel
    if ($matched_journal_id) {
        $update_stmt = $conn->prepare( "UPDATE artikel_oai SET journal_source_id = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $matched_journal_id, $article['id']);
        $update_stmt->execute();
        $update_stmt->close();
        $update_count++;
    } else {
        echo "   - <span style='color: orange;'>Peringatan:</span> Tidak ditemukan jurnal yang cocok untuk source1: '" . htmlspecialchars($source1_text) . "'\n";
    }
}

echo "\n<strong style='color: green;'>Proses Selesai.</strong> " . $update_count . " baris artikel berhasil disinkronkan.\n";
echo "</pre>";
$conn->close();
?>