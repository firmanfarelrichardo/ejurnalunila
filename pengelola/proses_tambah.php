<?php
// Memulai session untuk menyimpan pesan notifikasi.
session_start();

// Periksa apakah pengguna sudah login dan memiliki peran pengelola
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pengelola') {
    header("Location: login.php");
    exit();
}

// Konfigurasi database MySQL
$host = "localhost";
$user = "root";
$pass = "";
$db = "oai";
$conn = new mysqli($host, $user, $pass, $db);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Memeriksa apakah permintaan datang dari metode POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. Mengambil Data dari Form ---
    $nama_kontak = trim($_POST['nama_kontak']);
    $email_kontak = trim($_POST['email_kontak']);
    $institusi = trim($_POST['institusi']);
    $fakultas = $_POST['fakultas'];
    $judul_jurnal_asli = trim($_POST['judul_jurnal_asli']);
    $judul_jurnal = trim($_POST['judul_jurnal']);
    $doi = trim($_POST['doi']);
    $journal_type = $_POST['journal_type'];
    $p_issn = trim($_POST['p_issn']);
    $e_issn = trim($_POST['e_issn']);
    $akreditasi_sinta = $_POST['akreditasi_sinta'];
    $index_scopus = $_POST['index_scopus'];
    $penerbit = trim($_POST['penerbit']);
    $country_of_publisher = $_POST['country_of_publisher'];
    $website_url = trim($_POST['website_url']);
    $journal_contact_name = trim($_POST['journal_contact_name']);
    $journal_official_email = trim($_POST['journal_official_email']);
    $journal_contact_phone = trim($_POST['journal_contact_phone']);
    $start_year = $_POST['start_year'];
    $issue_period = isset($_POST['issue_period']) ? implode(',', $_POST['issue_period']) : '';
    $editorial_team = trim($_POST['editorial_team']);
    $editorial_address = trim($_POST['editorial_address']);
    $aim_and_scope = trim($_POST['aim_and_scope']);
    $has_homepage = $_POST['has_homepage'];
    $is_using_ojs = $_POST['is_using_ojs'];
    $ojs_link = trim($_POST['ojs_link']);
    $open_access_link = trim($_POST['open_access_link']);
    $url_editorial_board = trim($_POST['url_editorial_board']);
    $url_contact = trim($_POST['url_contact']);
    $url_reviewer = trim($_POST['url_reviewer']);
    $url_google_scholar = trim($_POST['url_google_scholar']);
    $link_sinta = trim($_POST['link_sinta']);
    $link_garuda = trim($_POST['link_garuda']);
    $url_cover = trim($_POST['url_cover']);
    $subject_arjuna = $_POST['subject_arjuna'];
    $sub_subject_arjuna = $_POST['sub_subject_arjuna'];
    $subject_garuda = isset($_POST['subject_garuda']) ? implode(',', $_POST['subject_garuda']) : '';
    
    $pengelola_id = $_SESSION['user_id'];

    // --- 2. Menyiapkan Query SQL ---
    $sql = "INSERT INTO jurnal_sumber (
                pengelola_id, nama_kontak, email_kontak, institusi, fakultas, judul_jurnal_asli, 
                judul_jurnal, doi, journal_type, p_issn, e_issn, akreditasi_sinta, index_scopus,
                penerbit, country_of_publisher, website_url, journal_contact_name, journal_official_email,
                journal_contact_phone, start_year, issue_period, editorial_team, editorial_address,
                aim_and_scope, has_homepage, is_using_ojs, ojs_link, open_access_link,
                url_editorial_board, url_contact, url_reviewer, url_google_scholar, link_sinta,
                link_garuda, url_cover, subject_arjuna, sub_subject_arjuna, subject_garuda
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // --- 3. Mengikat Parameter (Binding Parameters) ---
        // PERBAIKAN FINAL: String tipe data dikoreksi menjadi 38 karakter.
        mysqli_stmt_bind_param($stmt, "isssssssssssssssssissssiisssssssssssss",
            $pengelola_id, $nama_kontak, $email_kontak, $institusi, $fakultas, $judul_jurnal_asli,
            $judul_jurnal, $doi, $journal_type, $p_issn, $e_issn, $akreditasi_sinta, $index_scopus,
            $penerbit, $country_of_publisher, $website_url, $journal_contact_name, $journal_official_email,
            $journal_contact_phone, $start_year, $issue_period, $editorial_team, $editorial_address,
            $aim_and_scope, $has_homepage, $is_using_ojs, $ojs_link, $open_access_link,
            $url_editorial_board, $url_contact, $url_reviewer, $url_google_scholar, $link_sinta,
            $link_garuda, $url_cover, $subject_arjuna, $sub_subject_arjuna, $subject_garuda
        );

        // --- 4. Mengeksekusi Statement ---
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Selamat! Jurnal Anda telah berhasil didaftarkan dan sedang menunggu review.";
        } else {
            $_SESSION['error_message'] = "Terjadi kesalahan. Gagal mendaftarkan jurnal: " . mysqli_stmt_error($stmt);
        }
        
        mysqli_stmt_close($stmt);

    } else {
        $_SESSION['error_message'] = "Terjadi kesalahan. Gagal menyiapkan statement SQL: " . mysqli_error($conn);
    }

    mysqli_close($conn);

    // --- 5. Mengarahkan Kembali Pengguna ---
    header("Location: tambah_jurnal.php");
    exit();

} else {
    die("Akses tidak diizinkan.");
}
?>