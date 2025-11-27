<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pengelola') {
    header("Location: login.php");
    exit();
}

// Konfigurasi database MySQL
require_once '../database/config.php';
$conn = connect_to_database();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pendaftaran Jurnal Baru</title> 
    <link rel="stylesheet" href="style.css">     
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        
        .checkbox-item {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 8px; 
            align-items: start; 
        }
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: white;
            font-size: 14px;
            text-align: center;
        }
        .success {
            background-color: #2ecc71; /* Hijau */
        }
        .error {
            background-color: #e74c3c; /* Merah */
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="sidebar-toggle-btn" id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                    <img src="../Images/logo-header-2024-normal.png" alt="Logo Universitas Lampung">
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_pengelola.php" ><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="tambah_jurnal.php" class="active"><i class="fas fa-plus-circle"></i> <span>Daftar Jurnal Baru</span></a></li>
                <li><a href="daftar_jurnal.php"><i class="fas fa-list-alt"></i> <span>Daftar & Status Jurnal</span></a></li>
                <li><a href="change_password.php"><i class="fas fa-lock"></i> <span>Ganti Password</span></a></li>
                <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
        
        <div class="main-content">
            
            <div class="header">
                <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
                <div class="user-profile">
                    <span>Role: Pengelola</span>
                    <a href="../api/logout.php">Logout</a>
                </div>
            </div>

            <?php
            // Cek apakah ada pesan sukses di session.
            if (isset($_SESSION['success_message'])) {
                echo '<div class="notification success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                unset($_SESSION['success_message']);
            }
            // Cek apakah ada pesan error di session.
            if (isset($_SESSION['error_message'])) {
                echo '<div class="notification error">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                unset($_SESSION['error_message']);
            }
            ?>
            
            <div class="form-container">
                <h1>Formulir Pendaftaran Jurnal</h1>
                <p>Silakan isi detail jurnal Anda selengkap mungkin.</p>

                <form id="journalForm" action="proses_tambah.php" method="POST">
                
                    <fieldset>
                        <legend>Contact Detail</legend>
                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="nama_kontak">Your Name*</label>
                                <input type="text" id="nama_kontak" name="nama_kontak" required>
                            </div>
                            <div class="form-group">
                                <label for="email_kontak">Your email (PIC of Journal)*</label>
                                <input type="email" id="email_kontak" name="email_kontak" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="institusi">Institution/Company*</label>
                            <input type="text" id="institusi" name="institusi" required>
                        </div>

                         <div class="form-group">
                            <label for="fakultas">Faculty*</label>
                            <select id="fakultas" name="fakultas" required>
                                <option value="">Select Faculty</option>
                                <option value="Fakultas Ekonomi dan Bisnis">Fakultas Ekonomi dan Bisnis</option>
                                <option value="Fakultas Hukum">Fakultas Hukum</option>
                                <option value="Fakultas Ilmu Sosial dan Ilmu Politik">Fakultas Ilmu Sosial dan Ilmu Politik</option>
                                <option value="Fakultas Kedokteran">Fakultas Kedokteran</option>
                                <option value="Fakultas Keguruan dan Ilmu Pendidikan">Fakultas Keguruan dan Ilmu Pendidikan</option>
                                <option value="Fakultas Matematika dan Ilmu Pengetahuan Alam">Fakultas Matematika dan Ilmu Pengetahuan Alam</option>
                                <option value="Fakultas Pertanian">Fakultas Pertanian</option>
                                <option value="Fakultas Teknik">Fakultas Teknik</option>
                            </select>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Journal Information</legend>
                        <div class="form-group">
                            <label for="judul_jurnal_asli">The Original Title of The journal</label>
                            <input type="text" id="judul_jurnal_asli" name="judul_jurnal_asli" >
                        </div>
                        <div class="form-group">
                            <label for="judul_jurnal">Title of The journal</label>
                            <input type="text" id="judul_jurnal" name="judul_jurnal" >
                        </div>
                        <div class="form-group">
                            <label for="doi">DOI</label>
                            <input type="text" id="doi" name="doi" placeholder="Ex: https://doi.org/10.xxx/xxxx">
                        </div>
                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="journal_type">Journal Type</label>
                                <select id="journal_type" name="journal_type">
                                    <option value="Journal">Journal</option>
                                    <option value="Conference">Conference</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="p_issn">ISSN</label>
                                <input type="text" id="p_issn" name="p_issn" placeholder="*without '-'">
                            </div>
                            <div class="form-group">
                                <label for="e_issn">e-ISSN</label>
                                <input type="text" id="e_issn" name="e_issn" placeholder="*without '-'">
                            </div>
                        </div>
                         
                        <div class="form-group">
                        <label for="akreditasi_sinta">Akreditasi SINTA</label>
                        <select id="akreditasi_sinta" name="akreditasi_sinta" >
                            <option value="Belum Terakreditasi">Belum Terakreditasi</option>
                            <option value="Sinta 1">Sinta 1</option>
                            <option value="Sinta 2">Sinta 2</option>
                            <option value="Sinta 3">Sinta 3</option>
                            <option value="Sinta 4">Sinta 4</option>
                            <option value="Sinta 5">Sinta 5</option>
                            <option value="Sinta 6">Sinta 6</option>
                        </select>
                    </div>
                     <div class="form-group">
                        <label for="index_scopus">Indeks Scopus</label>
                        <select id="index_scopus" name="index_scopus">
                            <option value="Belum Terindeks">Belum Terindeks</option>
                            <option value="Q1">Q1</option>
                            <option value="Q2">Q2</option>
                            <option value="Q3">Q3</option>
                            <option value="Q4">Q4</option>
                        </select>
                    </div>
                    </fieldset>

                    <fieldset>
                        <legend>Publisher & Journal Contact</legend>
                        <div class="form-group">
                            <label for="penerbit">Publisher</label>
                            <input type="text" id="penerbit" name="penerbit" placeholder="*type and choose your publisher. if not found, type your publisher" >
                        </div>
                         <div class="form-group">
                            <label for="country_of_publisher">Country of Publisher</label>
                            <input type="text" id="country_of_publisher" name="country_of_publisher" value="Indonesia (ID)" readonly>
                        </div>
                         <div class="form-group">
                            <label for="website_url">Journal Website</label>
                            <input type="url" id="website_url" name="website_url" placeholder="*start with 'http://' OR 'https://'" >
                        </div>
                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="journal_contact_name">Journal Contact name</label>
                                <input type="text" id="journal_contact_name" name="journal_contact_name" >
                            </div>
                            <div class="form-group">
                                <label for="journal_official_email">Journal Official Email</label>
                                <input type="email" id="journal_official_email" name="journal_official_email" >
                            </div>
                            <div class="form-group">
                                <label for="journal_contact_phone">Journal Contact Phone</label>
                                <input type="tel" id="journal_contact_phone" name="journal_contact_phone">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="start_year">Start year since online full text content is available</label>
                            <input type="number" id="start_year" name="start_year" min="1900" max="2099" step="1" placeholder="YYYY" >
                        </div>
                        <div class="form-group">
                            <label for="issue_period">Period Of Issue Per Year</label>
                            <div class="checkbox-group">
                        <?php $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']; ?>
                        <?php foreach($months as $month): ?>
                        <div>
                            <input type="checkbox" id="month_<?php echo strtolower($month); ?>" name="issue_period[]" value="<?php echo $month; ?>">
                            <label for="month_<?php echo strtolower($month); ?>" class="form-checkbox-label"><?php echo $month; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                        </div>
                        <div class="form-group">
                            <label for="editorial_team">Journal editorial Team</label>
                            <textarea id="editorial_team" name="editorial_team" rows="4" ></textarea>
                        </div>
                        <div class="form-group">
                            <label for="editorial_address">Journal editorial address</label>
                            <textarea id="editorial_address" name="editorial_address" rows="4" ></textarea>
                        </div>
                        <div class="form-group">
                            <label for="aim_and_scope">Description of the aim and scope of the journal</label>
                            <textarea id="aim_and_scope" name="aim_and_scope" rows="6" ></textarea>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Additional Information & URLs</legend>
                        <div class="form-group">
                            <label>Does the journal has a homepage?</label>
                            <div class="radio-group">
                                <label><input type="radio" name="has_homepage" value="1" checked> Yes</label>
                                <label><input type="radio" name="has_homepage" value="0"> No</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Is the journal already used Open Journal System (OJS from PKP) as journal management and publishing system?</label>
                            <div class="radio-group">
                                <label><input type="radio" name="is_using_ojs" value="1"> Yes</label>
                                <label><input type="radio" name="is_using_ojs" value="0" checked> No</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ojs_link">If YES, Please provide us with a link:</label>
                            <input type="url" id="ojs_link" name="ojs_link">
                        </div>
                        <div class="form-group">
                            <label for="open_access_link">If the title is open access, please provide an example link to the online full text:</label>
                            <input type="url" id="open_access_link" name="open_access_link">
                        </div>
                        <div class="form-group">
                            <label for="url_editorial_board">URL of the editorial board:</label>
                            <input type="url" id="url_editorial_board" name="url_editorial_board" >
                        </div>
                        <div class="form-group">
                            <label for="url_contact">URL of the Contact:</label>
                            <input type="url" id="url_contact" name="url_contact" >
                        </div>
                        <div class="form-group">
                            <label for="url_reviewer">URL of reviewer:</label>
                            <input type="url" id="url_reviewer" name="url_reviewer">
                        </div>
                        <div class="form-group">
                            <label for="url_google_scholar">URL of Google Scholar:</label>
                            <input type="url" id="url_google_scholar" name="url_google_scholar">
                        </div>
                        <div class="form-group">
                        <label for="link_sinta">URL of Sinta:</label>
                        <input type="url" id="link_sinta" name="link_sinta">
                    </div>
                     <div class="form-group">
                        <label for="link_garuda">URL of Garuda:</label>
                        <input type="url" id="link_garuda" name="link_garuda">
                    </div>
                        <div class="form-group">
                            <label for="url_cover">URL of journal's Cover:</label>
                            <input type="url" id="url_cover" name="url_cover">
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Subject Area</legend>
                         <div class="form-group">
                            <label for="subject_arjuna">Subject Area Arjuna</label>
                            <select id="subject_arjuna" name="subject_arjuna">
                                <option value="">Pilih Subject Area...</option>
                                <option value="Biokimia, Genetika dan Biologi Molekuler">Biokimia, Genetika dan Biologi Molekuler</option>
                                <option value="Bisnis, Menejemen, dan Akutansi (semua kategori)">Bisnis, Menejemen, dan Akutansi (semua kategori)</option>
                                <option value="Energi (semua kategori)">Energi (semua kategori)</option>
                                <option value="Fisika dan Astronomi">Fisika dan Astronomi</option>
                                <option value="Ilmu Bumi dan Planet (semua kategori)">Ilmu Bumi dan Planet (semua kategori)</option>
                                <option value="Ilmu Ekonomi, Ekonometrika dan Keuangan (semua kategori)">Ilmu Ekonomi, Ekonometrika dan Keuangan (semua kategori)</option>
                                <option value="Ilmu Komputer (semua kategori)">Ilmu Komputer (semua kategori)</option>
                                <option value="Ilmu Lingkungan (semua ketegori)">Ilmu Lingkungan (semua ketegori)</option>
                                <option value="Ilmu Material (semua kategori)">Ilmu Material (semua kategori)</option>
                                <option value="Ilmu Pengambilan Keputusan (semua kategori)">Ilmu Pengambilan Keputusan (semua kategori)</option>
                                <option value="Ilmu Pertanian dan Biologi (Semua)">Ilmu Pertanian dan Biologi (Semua)</option>
                                <option value="Ilmu Sosial">Ilmu Sosial</option>
                                <option value="Ilmu Syaraf">Ilmu Syaraf</option>
                                <option value="Imunologi dan Mikrobiologi (semua kategori)">Imunologi dan Mikrobiologi (semua kategori)</option>
                                <option value="Kedokteran">Kedokteran</option>
                                <option value="Kedokteran Gigi">Kedokteran Gigi</option>
                                <option value="Kedokteran Hewan">Kedokteran Hewan</option>
                                <option value="Keperawatan">Keperawatan</option>
                                <option value="Kimia(semua kategori)">Kimia(semua kategori)</option>
                                <option value="Matematika">Matematika</option>
                                <option value="Pharmacology, Toxicology and Pharmaceutics">Pharmacology, Toxicology and Pharmaceutics</option>
                                <option value="Profesi Kesehatan">Profesi Kesehatan</option>
                                <option value="Psikologi">Psikologi</option>
                                <option value="Seni dan Humaniora">Seni dan Humaniora</option>
                                <option value="Teknik (semua kategori)">Teknik (semua kategori)</option>
                                <option value="Teknik Kimia (semua kategori)">Teknik Kimia (semua kategori)</option>
                                <option value="Umum">Umum</option>
                                </select>
                        </div>
                        <div class="form-group">
                            <label for="sub_subject_arjuna">Sub Subject Area Arjuna</label>
                            <select id="sub_subject_arjuna" name="sub_subject_arjuna">
                                <option value="">Pilih Sub Subject...</option>
                                </select>
                        </div>

                        <div class="form-group">
    <label>Subject Area Garuda (max. 5): *</label>
    <div class="checkbox-grid" id="garuda-subjects">
        <?php 
            $garuda_subjects = [
                'Religion' => 'Religion', 'Aerospace Engineering' => 'Engineering', 'Agriculture, Biological Sciences & Forestry' => 'Agriculture', 'Arts' => 'Art',
                'Humanities' => 'Humanities', 'Astronomy' => 'Science', 'Automotive Engineering' => 'Engineering', 'Biochemistry, Genetics & Molecular Biology' => 'Science',
                'Chemical Engineering, Chemistry & Bioengineering' => 'Engineering', 'Chemistry' => 'Science', 'Civil Engineering, Building, Construction & Architecture' => 'Engineering', 'Computer Science & IT' => 'Science',
                'Control & Systems Engineering' => 'Engineering', 'Decision Sciences, Operations Research & Management' => 'Science', 'Dentistry' => 'Health', 'Earth & Planetary Sciences' => 'Science',
                'Economics, Econometrics & Finance' => 'Economy', 'Education' => 'Education', 'Electrical & Electronics Engineering' => 'Engineering', 'Energy' => 'Science',
                'Engineering' => 'Engineering', 'Environmental Science' => 'Social', 'Health Professions' => 'Health', 'Immunology & microbiology' => 'Science',
                'Industrial & Manufacturing Engineering' => 'Engineering', 'Language, Linguistic, Communication & Media' => 'Education', 'Law, Crime, Criminology & Criminal Justice' => 'Social', 'Library & Information Science' => 'Science',
                'Materials Science & Nanotechnology' => 'Science', 'Mathematics' => 'Education', 'Mechanical Engineering' => 'Engineering', 'Medicine & Pharmacology' => 'Health',
                'Neuroscience' => 'Science', 'Nursing' => 'Health', 'Physics' => 'Science', 'Public Health' => 'Health', 'Social Sciences' => 'Social',
                'Transportation' => 'Engineering', 'Veterinary' => 'Health', 'Other' => 'Education'
            ]; 
        ?>
        <?php foreach($garuda_subjects as $subject => $category): 
                $id_safe_subject = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $subject));
        ?>
            <div class="checkbox-item">
                <input 
                    type="checkbox" 
                    id="garuda_<?php echo $id_safe_subject; ?>" 
                    name="subject_garuda[]" 
                    value="<?php echo htmlspecialchars($subject); ?>" 
                    class="garuda-checkbox">
                <label 
                    for="garuda_<?php echo $id_safe_subject; ?>" 
                    class="form-checkbox-label">
                    <?php echo htmlspecialchars($subject); ?> (<?php echo htmlspecialchars($category); ?>)
                </label>
            </div>
        <?php endforeach; ?>
    </div>
</div>
                    </fieldset>

                    <div class="form-actions">
                        <button type="button" id="reviewButton" class="btn btn-secondary">Review Formulir</button>
                        <button type="button" id="submitButton" class="btn btn-primary">Submit My Suggestion</button>
                    </div>
                </form>
            </div>
            
        </div> </div> <script src="script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const subjectSelect = document.getElementById('subject_arjuna');
        const subSubjectSelect = document.getElementById('sub_subject_arjuna');
        const subSubjectMap = {
            'Biokimia, Genetika dan Biologi Molekuler': ['Biofisika', 'Biokimia', 'Biokimia Klinis', 'Biokimia, Genetika dan Biologi Molekuler (Lain-Lain)', 'Biologi Molekuler', 'Biologi Perkembangan', 'Biologi Sel', 'Biologi Struktur', 'Bioteknologi', 'Endokrinologi', 'Fisiologi', 'Genetika', 'Kedokteran Molekuler', 'Penelitian Kanker', 'Penuaan'],
            'Bisnis, Menejemen, dan Akutansi (semua kategori)': ['Akuntansi', 'Bisnis dan Menejemen Intetrnasional', 'Bisnis, Menejemen, dan Akutansi (Lain-Lain)', 'Menejemen Teknologi dan Inovasi', 'Pemasaran', 'Perilaku Organisasi dan Menejemen Sumber Daya Manusia', 'Relasi Industri', 'Sistem Informasi Menejemen', 'Strategi dan Menejemen', 'Tourism, Leisure and Hospitality Management'],
            'Energi (semua kategori)': ['Energi (Lain-Lain)', 'Energi Terbarukan, Keberlanjutan dan Lingkungan', 'Teknik Energi dan Teknologi Daya', 'Teknik dan Energi Nuklir', 'Teknologi Bahan Bakar'],
            'Fisika dan Astronomi': ['Akustik dan Ultrasonik', 'Astronomi dan Astrofisika', 'Condensed Matter Physics', 'Fisika Nonlinear dan Statistk', 'Fisika Nuklir dan Energi Tinggi', 'Fisika Nuklir dan Molekuler, dan Ooptik', 'Fisika dan Astronomi (Lain-Lain)', 'Instrumentasi', 'Permukaan dan Interface', 'Radiasi'],
            'Ilmu Bumi dan Planet (semua kategori)': ['Geofisika', 'Geokimia danPetrologi', 'Geologi', 'Geologi Ekonomi', 'Ilmu Atmosfir', 'Ilmu Bumi dan Planet (Lain-Lain)', 'Komputer pada Ilmu Bumi', 'Oseanografi', 'Paleontologi', 'Proses Permukaan Bumi', 'Ruang Angkasa dan Ilmu Planet', 'Styraytigrafi', 'Teknik Geologi dan Geologi Teknik'],
            'Ilmu Ekonomi, Ekonometrika dan Keuangan (semua kategori)': ['Ilmu Ekomomi dan Ekonometrika', 'Ilmu Ekonomi, Ekonometrika dan Keuangan (Lain-Lain)', 'Keuangan'],
            'Ilmu Komputer (semua kategori)': ['Aplikasi Ilmu Komputer', 'Grafik Komputer dan Desain Berbantu Komputer', 'Ilmu Komputer (Lain-Lain)', 'Interaksi Komputer-Manusia', 'Jaringan Komputer dan Komunikasi', 'Kecerdasan Buatan', 'Pemrosesan Sinyal', 'Perangkat Keras dan Arsitektur', 'Perangkat Lunak', 'Sistem Informasi', 'Teori Komputasi dan Matematika', 'Visi Komputer dan Pengenalan Pola'],
            'Ilmu Lingkungan (semua ketegori)': ['Ekologi', 'Ekologi Modeling', 'Global dan Perubahan Planet', 'Ilmu Lingkungan (Lain-Lain)', 'Kesehatan, Toksikologi dan Mutasi Gen', 'Kimia Lingkungan', 'Konservasi Alam dan Lahan', 'Manajemen Limbah dan Disposal', 'Manajemen, Monitoring, Kebijakan dan Hukum', 'Polusi', 'Sain dan Teknologi Air', 'Teknik Lingkungan'],
            'Ilmu Material (semua kategori)': ['Biomaterial', 'Elektronik, Optik dan Materi Magnetik', 'Ilmu Material (Lain-Lain)', 'Keramik dan Komposit', 'Kimia Material', 'Logam dan Paduan Logam', 'Permukaan, Pelapisan dan Film', 'Polimer dan Plastik'],
            'Ilmu Pengambilan Keputusan (semua kategori)': ['Ilmu Manajemen dan Riset Operasi', 'Ilmu Pengambilan Keputusan (Lain-Lain)', 'Sistem Informasi dan Manajemen', 'Statistik, Kemungkinan dan Ketidak Pastian'],
            'Ilmu Pertanian dan Biologi (Semua)': ['Agronomi dan Ilmu Tanaman', 'Ecology, Evolution, Behavior and Systematics', 'Holtikkultura', 'Ilmu Hewan dan Zoologi', 'Ilmu Makanan', 'Ilmu Perairan', 'Ilmu Pertanian dan Biologi (Lain-Lain)', 'Ilmu Serangga', 'Ilmu Tanah', 'Ilmu Tumbuhan', 'Kehutanan'],
            'Ilmu Sosial': ['Administrasi Publik', 'Antropologi', 'Arkeologi', 'Demografi', 'Geografi, Perencanaan dan Pengembangan', 'GFaktor Manusia dan Ergonomi', 'Hukum', 'Ilmu Politik dan Hubungan Internasional', 'Ilmu Sosial (Lain-Lain)', 'Kajian Budaya', 'Kesehatan (Ilmu Sosial)', 'Komunikasi', 'Life-span and Life-course Studies', 'Linguistik dan Bahasa', 'Pendidikan', 'Perkembangan', 'Perpustakan dan Informasi', 'Riset Keselamatan', 'Sosiologi dan Ilmu Politik', 'Studi Urban', 'Stugi Gender', 'Transportasi'],
            'Ilmu Syaraf': ['Ilmu Syaraf (Lain-Lain)', 'Ilmu Syaraf Kognitif', 'Ilmu Syaraf Perilaku', 'Ilmu Syaraf Perkembangan', 'Ilmu Syaraf Seluler dan Molekuler', 'Neurologi', 'Psikiatri Biologi', 'Sistem Endokrin dan Automatis', 'Sistem Sensori'],
            'Imunologi dan Mikrobiologi (semua kategori)': ['Imunologi', 'Imunologi dan Mikrobiologi (Lain-Lain)', 'Mikrobiologi', 'Mikrobiologi dan Bioteknologi Terapan', 'Parasitologi', 'Virologi'],
            'Kedokteran': ['Anatomi', 'Anesthesiology and Pain Medicine', 'Biokimia, Kedokteran', 'Cardiology and Cardiovascular Medicine', 'Critical Care and Intensive Care Medicine', 'Dermatologi', 'Embriologi', 'Endokrinologi, Diabetes dan Metabolisme', 'Epidemiologi', 'Family (Medis)', 'Fisiologi (Medis)', 'Genetika (Klinis)', 'Geriatrics and Gerontology', 'Hematologi', 'Hepatologi', 'Histologi', 'Ilmu Pencernaan', 'Imunologi dan Alergi', 'Informatika Kesehatan', 'Kebijakan Kesehatan', 'Kedokteran (Lain-Lain)', 'Kedokteran Emergensi', 'Kedokteran Internal', 'Kedokteran Keluarga', 'Kedokteran Reproduksi dan Alternatif', 'Kedokteran Paru dan Pernafasan', 'Kedokteran Radiologi Nuklir dan Imaging', 'Kedokteran Reproduksi', 'Kesehatan Masyarakat, Kesehatan Lingkungan dan Pekerjaan', 'Mikrobiologi (Medis)', 'NTT Nephrology', 'Neurologi Klinis', 'Obstetrics and gynaecology', 'Onkologi', 'Opthalmology', 'Ortopedi dan Kedokteran Kesehatan', 'Otorhinolaryngology', 'Patologi dan Kedokteran Kesehatan', 'Pediatrics, Perinatology, and Child Health', 'Pembedahan', 'Penyakit Menular', 'Petunjuk Obat-obatan', 'Psikiatri dan Kesehatan Mental', 'Rehabilitasi', 'Review dan Referensi, Kedokteran', 'Rheumatology', 'Transplantasi', 'Urologi'],
            'Kedokteran Gigi': ['Bedah Mulut', 'Kebersihan Gigi', 'Kedokteran Gigi (Lain-Lain)', 'Ortodonti', 'Perawatan Gigi', 'Periodontik'],
            'Kedokteran Hewan': ['Equine', 'Food Animals', 'Hewan Kecil', 'Kedokteran Hewan (Lain-Lain)', 'Zoologi'],
            'Keperawatan': ['Advanced and Specialised Nursing', 'Asemen dan Diagnosis', 'Bedah Keperawatan', 'Care Planning', 'Community and Home Care', 'Critical Care', 'Farmakologi (Keperawatan)', 'Fundamental dan Ketrampilan', 'Gerontologi', 'Isu, Etik dan Aspek', 'Kedaruratan', 'Kepemimpinan dan Menejemen', 'Keperawatan (Lain-Lain)', 'Kesehatan Mental Psikitri', 'LPN dan LVN', 'Maternity and Midwifery', 'Nurse Assisting', 'Nutrition and Dietetics', 'Oncology (Keperawatan)', 'Patofisiologi', 'Pediatrik', 'Review dan Persiapan', 'Teori dan Teori'],
            'Kimia(semua kategori)': ['Inorganic Chemistry', 'Kimia (Lain-Lain)', 'Kimia Analitik', 'Kimia Fisik', 'Kimia Organik', 'Kimia Teori dan Fisik', 'Spektroskopi'],
            'Matematika': ['Aljabar dan Teori Bilangan', 'Analisis', 'Analisis Numerikal', 'Fisika Matematik', 'Geometri dan Topologi', 'Ilmu Komputer Teoritis', 'Kontrol dan Optimasi', 'Logika', 'Matematika (Lain-Lain)', 'Matematika Diskrit dan Kombinatori', 'Matematika Komputer', 'Matematika Terapan', 'Pemodelan dan Simulasi', 'Statistik dan Probabilitas'],
            'Pharmacology, Toxicology and Pharmaceutics': ['Farmakologi', 'Ilmu Farmasi', 'Penemuan Obat', 'Pharmacology, Toxicology and Pharmaceutics (Lain-Lain)', 'Toksikologi'],
            'Profesi Kesehatan': ['Bimbingan Kesehatan dan Transkripsi', 'Chiropractics', 'Farmasi', 'Menejemen Informasi Kesehatan', 'Occupational Therapy', 'Optometri', 'Pelayanan Medis Emergensi', 'Perawatan Pernafasan', 'Podiatri', 'Profesi Kesehatan (Lain-Lain)', 'Speech and Hearing', 'Teknologi Laboratorium Medis', 'Teknologi Ultrasound dan Radiologi', 'Terapi Fisik, Olah Raga dan Rehabilitasi', 'Terapi Manual dan Pelengkap', 'Terminologi Medis'],
            'Psikologi': ['Neuropsychology and Physiological Psychology', 'Psikologi (Lain-Lain)', 'Psikologi Klinis', 'Psikologi Kognitif dan Experimental', 'Psikologi Pendidikan dan perkembangan', 'Psikologi Sosial', 'Psikologi Terapan'],
            'Seni dan Humaniora': ['Arkeologi', 'Bahasa dan Linguistik', 'Filsafat', 'Klasik', 'Konservasi', 'Museology', 'Musik', 'Sastra dan Teori Sastra', 'Sejarah', 'Sejarah dan Filsafat Ilmu', 'Seni Visual dan Seni Pertunjukan', 'Seni dan Humaniora (Lain-Lain)', 'Studi Agama'],
            'Teknik (semua kategori)': ['Arsitektur', 'Bangunan dan Konstruksi', 'Keselamatan, Resiko, Reabilitas dan Kualitas', 'Komputasi Mekanik', 'Mekanik Material', 'Teknik (Lain-Lain)', 'Teknik Biomedis', 'Teknik Kelautan', 'Teknik Kontrol dan Sistem', 'Teknik Listrik dan Elektro', 'Teknik Mesin', 'Teknik Otomottif', 'Teknik Ruang Angkasa', 'Teknik Sipil dan Struktur', 'Teknologi Media', 'Ternik Industri dan Manufaktur'],
            'Teknik Kimia (semua kategori)': ['Bioengineering', 'Filtrasi dan Seperasi', 'Fluid Flow and Transfer Processes', 'Katalisis', 'Kesehatan dan Keamanan Kimia', 'Kimia Proses dan Teknologi', 'Koloid dan Kimia Permukaan', 'Teknik Kimia (Lain-Lain)'],
            'Umum': ['Umum']
        };

        // Fungsi untuk memperbarui dropdown sub-subjek
        function updateSubSubjects() {
            const selectedSubject = subjectSelect.value;
            const subSubjects = subSubjectMap[selectedSubject] || []; 

            subSubjectSelect.innerHTML = '';

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Pilih Sub Subjek...';
            subSubjectSelect.appendChild(defaultOption);

            subSubjects.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub;
                option.textContent = sub;
                subSubjectSelect.appendChild(option);
            });
        }

        subjectSelect.addEventListener('change', updateSubSubjects);
        updateSubSubjects();
    });
    </script>
</body>
</html>