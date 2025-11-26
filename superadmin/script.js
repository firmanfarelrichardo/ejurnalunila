// pengelola/script.js
// Menjalankan script setelah seluruh halaman HTML selesai dimuat.
// Ini adalah praktik terbaik untuk memastikan semua elemen (seperti tombol dan form)
// sudah siap untuk dimanipulasi oleh JavaScript.
document.addEventListener('DOMContentLoaded', function() {

    // --- 1. Mengambil Elemen-elemen Penting dari Halaman ---
    const journalForm = document.getElementById('journalForm');
    const submitButton = document.getElementById('submitButton');
    const reviewButton = document.getElementById('reviewButton');

    // Memastikan elemen-elemen tersebut ada sebelum menambahkan event listener
    if (submitButton) {
        // --- 2. Logika untuk Tombol Submit dengan Pop-up Konfirmasi ---
        submitButton.addEventListener('click', function(e) {
            // e.preventDefault(); // Tidak perlu karena tipe tombolnya sudah 'button'

            // Menggunakan SweetAlert2 untuk menampilkan dialog konfirmasi
            Swal.fire({
                title: 'Konfirmasi Pengiriman',
                text: "Apakah Anda yakin semua data yang diisi sudah benar?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Kirim Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                // 'then' akan dijalankan setelah pengguna menutup pop-up.
                // 'result.isConfirmed' akan bernilai true jika pengguna menekan tombol "Ya, Kirim Sekarang!".
                if (result.isConfirmed) {
                    // Jika dikonfirmasi, kita akan mengirimkan formulir secara manual.
                    journalForm.submit();
                }
            });
        });
    }

    if (reviewButton) {
        // --- 3. Logika untuk Tombol Review Formulir ---
        reviewButton.addEventListener('click', function() {
            // Mengumpulkan semua data dari form menjadi satu objek
            const formData = new FormData(journalForm);
            
            // Mengubah FormData menjadi objek biasa agar lebih mudah diakses
            const data = Object.fromEntries(formData.entries());

            // Mengumpulkan nilai dari checkbox yang dipilih
            const issuePeriods = Array.from(journalForm.querySelectorAll('input[name="issue_period[]"]:checked'))
                                     .map(cb => cb.value)
                                     .join(', ') || 'Tidak ada';
            const subjectGaruda = Array.from(journalForm.querySelectorAll('input[name="subject_garuda[]"]:checked'))
                                     .map(cb => cb.value)
                                     .join(', ') || 'Tidak ada';

            // Membuat konten HTML untuk ditampilkan di dalam pop-up review
            // Menggunakan template literal (backticks ``) untuk membuat string multi-baris
            const reviewContent = `
                <div style="text-align: left; max-height: 70vh; overflow-y: auto; padding-right: 15px;">
                    <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-top:0;">📋 Pratinjau Data Jurnal</h3>
                    <p><strong>Nama Kontak:</strong> ${data.nama_kontak || '-'}</p>
                    <p><strong>Email Kontak:</strong> ${data.email_kontak || '-'}</p>
                    <p><strong>Institusi:</strong> ${data.institusi || '-'}</p>
                    <hr>
                    <p><strong>Judul Jurnal Asli:</strong> ${data.judul_jurnal_asli || '-'}</p>
                    <p><strong>Judul Jurnal:</strong> ${data.judul_jurnal || '-'}</p>
                    <p><strong>DOI:</strong> ${data.doi || '-'}</p>
                    <p><strong>Penerbit:</strong> ${data.penerbit || '-'}</p>
                    <p><strong>Website:</strong> ${data.website_url || '-'}</p>
                    <p><strong>Periode Terbit:</strong> ${issuePeriods}</p>
                    <hr>
                    <p><strong>URL Editorial Board:</strong> ${data.url_editorial_board || '-'}</p>
                    <p><strong>URL Kontak:</strong> ${data.url_contact || '-'}</p>
                    <p><strong>Subjek Garuda:</strong> ${subjectGaruda}</p>
                    <hr>
                    <p style="font-style: italic; color: #555;">Ini adalah pratinjau. Silakan periksa kembali data Anda sebelum melakukan pengiriman.</p>
                </div>
            `;

            // Menampilkan pop-up review menggunakan SweetAlert2
            Swal.fire({
                title: 'Review Formulir',
                html: reviewContent, // Menyisipkan konten HTML yang sudah kita buat
                width: '80%', // Membuat pop-up lebih lebar agar muat banyak informasi
                confirmButtonText: 'Tutup',
                icon: 'info'
            });
        });
    }

    
});