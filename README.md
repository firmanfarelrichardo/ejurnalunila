# Dokumentasi E-Jurnal CI/CD Pipeline Dasar

Repositori ini dilengkapi dengan sistem integrasi dan pengiriman berkelanjutan (CI/CD) otomatis menggunakan **GitHub Actions**. Alur kerja (pipeline) ini didesain agar sederhana, ramah pemula, dan berfokus pada pengecekan dasar sebelum kode disebarkan (deploy) ke server.

## 🚀 Kapan Pipeline Berjalan?

Pipeline secara otomatis akan berjalan setiap kali ada tindakan:
- **Push** (mendorong kode) ke *branch*: `main`, `staging`, atau `production`.
- **Pull Request** (meminta penggabungan kode) menuju *branch*: `main`, `staging`, atau `production`.

## ⚙️ Tahapan (Jobs) Pipeline

Pipeline ini menjalankan instruksinya dalam 4 tahapan secara otomatis:

### 1. Pengecekan Sintaks PHP (`test-php`)
- **Tujuan:** Memastikan tidak ada kesalahan *typo* pada penulisan (*syntax error*) di setiap file `.php` karena ini dapat membuat website langsung *blank/error*.
- **Cara Kerja:** Menyiapkan PHP versi 8.2 dan menggunakan perintah pengecekan standar `php -l`.

### 2. Build Aset Frontend (`build-frontend`)
- **Tujuan:** Menyiapkan aset antarmuka tampilan (Vite/Node.js) sebelum aplikasi tayang.
- **Cara Kerja:** Menggunakan Node.js versi 20, masuk ke dalam folder `/frontend`, lalu menjalankan perintah `npm install` dan memproses kompilasi aset dengan `npx vite build`.

### 3. Deploy ke Server Uji Coba (`deploy-staging`)
- **Tujuan:** Mengirim perubahan Anda ke server tes *sementara* (Staging) tanpa mengganggu pengguna nyata.
- **Syarat Jalan:** Tahap ini **hanya aktif** saat kode didorong (di-push) ke cabang `staging`. Pengujian tahap 1 dan 2 sebelumnya juga harus **sukses 100%**.

### 4. Deploy ke Server Asli (`deploy-production`)
- **Tujuan:** Mengirim versi final website ke server **Live/Utama** yang diakses pengguna.
- **Syarat Jalan:** Hanya terjadi jika *push* diarahkan ke cabang `production`. Pengujian tahap 1 dan 2 juga wajib sukses lebih dulu.

## 💡 Alur Keja Standar (Git Workflow)

1. **Kerjakan di `main`**: Tulis koding Anda di sini. Ketika dipush, GitHub akan melakukan **test-php** dan **build-frontend** untuk membuktikan bahwa tidak ada eror.
2. **Kirim ke `staging`**: Jika yakin kodenya bekerja, pindahkan/gabung *branch* `main` ke `staging` untuk persiapan uji coba server simulasi (tim QA/tester).
3. **Kirim ke `production`**: Setelah lulus uji coba internal di server staging, barulah gabung (merge) kembali *branch* `staging` ke cabang `production` agar sistem mengotomasi *deployment* hingga kode langsung dinikmati publik.

---
*Lokasi file konfigurasi mesin GitHub Actions ini dapat kamu lihat atau modifikasi di:* `.github/workflows/pipeline.yml`
