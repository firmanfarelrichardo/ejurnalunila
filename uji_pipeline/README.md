# Panduan Menguji Kegagalan CI/CD Pipeline

Folder ini berisi contoh-contoh kode rusak yang dapat digunakan untuk menguji apakah GitHub Actions (CI/CD Pipeline) Anda benar-benar berfungsi menangkap error sebelum kode masuk ke server.

## 1. Menguji Kegagalan PHP (Tahap `test-php`)
Pipeline Anda saat ini menggunakan perintah `php -l` untuk mengecek validitas sintaks PHP. Pipeline akan **gagal** (warna merah di GitHub) jika ada file `.php` yang memiliki error penulisan (syntax error).

**Cara Menguji:**
1. Ubah nama file `contoh_error_sintaks.php.txt` menjadi `contoh_error_sintaks.php`.
2. Commit dan push ke GitHub (misalnya ke branch `main`).
3. Buka tab **Actions** di GitHub, Anda akan melihat job `Cek Sintaks PHP` gagal dengan pesan seperti: *PHP Parse error: syntax error, unexpected token...*
4. Setelah puas melihatnya gagal, hapus file tersebut atau kembalikan ekstensinya menjadi `.txt`, lalu push kembali agar hijau (sukses).

## 2. Menguji Kegagalan Frontend (Tahap `build-frontend`)
Pipeline Vite Anda akan **gagal** jika proses *build* aset tidak dapat dikompilasi karena ada error fatal di JavaScript atau SCSS.

**Cara Menguji:**
1. Salin kode yang ada di dalam `contoh_error_vite.js.txt`.
2. Tempelkan kode tersebut ke dalam file `frontend/src/main.js`.
3. Commit dan push ke GitHub.
4. Buka tab **Actions** di GitHub, Anda akan melihat job `Build Aset Frontend` gagal karena Rollup/Vite tidak bisa membacanya (*SyntaxError*).
5. Segera hapus kode tersebut dari `main.js` dan push kembali untuk menormalkan pipeline.
