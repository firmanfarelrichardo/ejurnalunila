<?php
session_start();
// FILE: api/submit_submission.php
// Fungsi: Menerima data dari formulir pendaftaran jurnal dan menyimpannya ke database.
// Skrip ini sekarang mengunggah semua data ke tabel 'jurnal_sumber' dengan status 'pending'.

// Keamanan: Pastikan hanya pengelola yang bisa mengakses skrip ini
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pengelola') {
    die("Akses ditolak. Kamu harus login sebagai pengelola.");
}

// Koneksi ke Database
$host = "localhost";
$user = "root";
$pass = "";
$db = "oai";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Ambil data dari form yang dikirim melalui POST
$nama_kontak = $_POST['nama_kontak'] ?? null;
$email_kontak = $_POST['email_kontak'] ?? null;
$institusi = $_POST['institusi'] ?? null;
$judul_jurnal_asli = $_POST['judul_jurnal_asli'] ?? null;
$judul_jurnal = $judul_jurnal_asli; // Menggunakan judul asli sebagai judul_jurnal sementara
$doi = $_POST['doi'] ?? null;
$journal_type = $_POST['journal_type'] ?? 'Journal';
$p_issn = $_POST['p_issn'] ?? null;
$e_issn = $_POST['e_issn'] ?? null;
$penerbit = $_POST['penerbit'] ?? null;
$country_of_publisher = $_POST['country_of_publisher'] ?? 'Indonesia (ID)';
$website_url = $_POST['website_url'] ?? null;
$journal_contact_phone = $_POST['journal_contact_phone'] ?? null;
$start_year = $_POST['start_year'] ?? null;
$issue_period = $_POST['issue_period'] ?? null;
$editorial_address = $_POST['editorial_address'] ?? null;
$aim_and_scope = $_POST['aim_and_scope'] ?? null;
$has_homepage = 1; // Default true, bisa diubah nanti
$is_using_ojs = 0; // Default false, bisa diubah nanti
$ojs_link = $_POST['ojs_link'] ?? null;
$open_access_link = $_POST['open_access_link'] ?? null;
$url_editorial_board = $_POST['url_editorial_board'] ?? null;
$url_contact = $_POST['url_contact'] ?? null;
$url_reviewer = $_POST['url_reviewer'] ?? null;
$url_google_scholar = $_POST['url_google_scholar'] ?? null;
$url_cover = $_POST['url_cover'] ?? null;
$link_sinta = $_POST['link_sinta'] ?? null;
$link_garuda = $_POST['link_garuda'] ?? null;
$link_oai = $_POST['link_oai'] ?? null;
$subject_arjuna = $_POST['subject_arjuna'] ?? null;
$sub_subject_arjuna = $_POST['sub_subject_arjuna'] ?? null;
$subject_garuda = $_POST['subject_garuda'] ?? null;
$akreditasi_sinta = $_POST['akreditasi_sinta'] ?? 'Belum Terakreditasi';
$index_scopus = $_POST['index_scopus'] ?? 'Belum Terindeks';
$fakultas = $_POST['fakultas'] ?? null;
$editorial_team = $_POST['editorial_team'] ?? null;
$status = 'pending'; // Status default saat pengajuan
$submitted_by_nip = $_SESSION['user_id'];
$pengelola_id = $submitted_by_nip; // Anggap pengelola_id sama dengan nip untuk sementara

// Query INSERT ke tabel jurnal_sumber yang baru
$stmt = $conn->prepare("INSERT INTO jurnal_sumber (
    pengelola_id, nama_kontak, email_kontak, institusi, judul_jurnal_asli, judul_jurnal, doi, journal_type,
    p_issn, e_issn, penerbit, country_of_publisher, website_url, journal_contact_phone,
    start_year, issue_period, editorial_address, aim_and_scope, has_homepage,
    is_using_ojs, ojs_link, open_access_link, url_editorial_board, url_contact,
    url_reviewer, url_google_scholar, url_cover, link_sinta, link_garuda, link_oai,
    subject_arjuna, sub_subject_arjuna, subject_garuda, akreditasi_sinta,
    index_scopus, fakultas, editorial_team, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("isssssssssssssssssisssssssssssssssssss",
    $pengelola_id, $nama_kontak, $email_kontak, $institusi, $judul_jurnal_asli, $judul_jurnal, $doi, $journal_type,
    $p_issn, $e_issn, $penerbit, $country_of_publisher, $website_url, $journal_contact_phone,
    $start_year, $issue_period, $editorial_address, $aim_and_scope, $has_homepage,
    $is_using_ojs, $ojs_link, $open_access_link, $url_editorial_board, $url_contact,
    $url_reviewer, $url_google_scholar, $url_cover, $link_sinta, $link_garuda, $link_oai,
    $subject_arjuna, $sub_subject_arjuna, $subject_garuda, $akreditasi_sinta,
    $index_scopus, $fakultas, $editorial_team, $status
);

if ($stmt->execute()) {
    echo "<h1>Pengajuan Berhasil!</h1>";
    echo "<p>Formulir jurnal kamu telah berhasil diunggah dan akan segera diverifikasi oleh Admin. Kamu akan diarahkan kembali ke dashboard.</p>";
    echo '<script>setTimeout(function(){ window.location.href = "../dashboard_pengelola.php"; }, 3000);</script>';
} else {
    echo "<h1>Error!</h1>";
    echo "<p>Terjadi kesalahan saat menyimpan data: " . $stmt->error . "</p>";
    echo "<a href='../register_journal.php'>Kembali ke Formulir</a>";
}

$stmt->close();
$conn->close();
?>