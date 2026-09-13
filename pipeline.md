# Penjelasan Baris per Baris: `pipeline.yml`

File `.github/workflows/pipeline.yml` adalah otak dari sistem CI/CD (Continuous Integration / Continuous Deployment) di repositori ini. File ini memberitahu GitHub Actions apa yang harus dilakukan setiap kali ada perubahan kode.

Berikut adalah penjelasan mendetail dari setiap bagian dalam file tersebut beserta alasannya:

---

### 1. Identitas dan Pemicu (Triggers)
```yaml
name: E-Jurnal CI/CD Pipeline Dasar
```
- **Penjelasan**: Memberikan nama untuk alur kerja (workflow) ini. Nama ini akan muncul di tab "Actions" di GitHub agar mudah dikenali.

```yaml
on:
  push:
    branches: [ "main", "staging", "production" ]
  pull_request:
    branches: [ "main", "staging", "production" ]
```
- **Penjelasan**: Blok `on` menentukan **kapan** pipeline ini akan berjalan.
- **Alasan**: Kita mengatur agar pipeline berjalan secara otomatis setiap kali ada `push` (kode dikirim langsung) ATAU `pull_request` (permintaan penggabungan kode) ke tiga cabang utama: `main`, `staging`, dan `production`. Cabang lain diabaikan agar tidak membuang kuota GitHub Actions.

---

### 2. Jobs: Pengecekan Kode PHP (`test-php`)
```yaml
jobs:
  test-php:
    name: Cek Sintaks PHP
    runs-on: ubuntu-latest
```
- **Penjelasan**: Memulai daftar pekerjaan (`jobs`). `test-php` adalah ID pekerjaan pertama. Pipeline akan menyewa sebuah komputer virtual gratis dari GitHub yang menggunakan sistem operasi Linux Ubuntu terbaru (`ubuntu-latest`).
- **Alasan**: Linux Ubuntu sangat stabil, cepat, dan menjadi standar industri untuk menjalankan server web dan script PHP.

```yaml
    steps:
      - name: Checkout Code
        uses: actions/checkout@v4
```
- **Penjelasan**: Menggunakan ekstensi resmi GitHub (`actions/checkout@v4`) untuk mengunduh (cloning) kode Anda dari repositori ke dalam komputer virtual mesin Ubuntu tersebut.
- **Alasan**: Tanpa ini, komputer virtual tidak akan memiliki file kode proyek Anda untuk dites.

```yaml
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
```
- **Penjelasan**: Memasang bahasa pemrograman PHP versi 8.2 di dalam komputer virtual.
- **Alasan**: Harus disesuaikan dengan versi PHP yang dipakai di server lokal (Laragon) atau server tujuan Anda. Versi 8.2 adalah versi modern yang cepat dan aman.

```yaml
      - name: Cek Error PHP
        run: find . -type f -name "*.php" -print0 | xargs -0 -n1 php -l > /dev/null
```
- **Penjelasan**: Perintah berbasis Linux. `find` mencari semua file berakhiran `.php`, lalu `php -l` (lint) mengecek satu per satu apakah ada salah ketik/syntax error. `> /dev/null` menyembunyikan teks output yang berhasil agar log tidak penuh, sehingga hanya error yang akan ditampilkan.
- **Alasan**: Langkah ini sangat krusial agar tidak ada kode PHP yang error (seperti kurang titik koma) yang terlanjur dirilis dan merusak website berakibat *Blank Putih*.

---

### 3. Jobs: Build Aset Frontend (`build-frontend`)
```yaml
  build-frontend:
    name: Build Aset Frontend
    runs-on: ubuntu-latest
```
- **Penjelasan**: Pekerjaan kedua. Berjalan di komputer virtual Linux baru secara bersamaan (paralel) dengan job `test-php`.

```yaml
    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
```
- **Penjelasan**: Mengambil kode sumber, lalu menginstal Node.js versi 20.
- **Alasan**: Karena project ini menggunakan `Vite` di folder `frontend`, Node.js wajib ada untuk menjalankan perintah kompilasi (build) Javascript dan CSS/SCSS.

```yaml
      - name: Install & Build
        working-directory: ./frontend
        run: |
          rm -rf node_modules
          npm install
          npx vite build
```
- **Penjelasan**: 
  - `working-directory: ./frontend`: Masuk ke folder `frontend`.
  - `rm -rf node_modules`: Menghapus folder dependensi bawaan (jika secara tidak sengaja ter-push dari Windows) agar tidak bentrok dengan eksekusi di Linux.
  - `npm install`: Mengunduh semua pustaka dari `package.json`.
  - `npx vite build`: Memproses (compile/minify) kodingan frontend agar siap dipakai untuk production.
- **Alasan**: Mencegah insiden *Permission Denied* pada Vite jika menggunakan `node_modules` Windows di Linux, serta memastikan aset FE di-build dengan benar sebelum masuk server.

---

### 4. Jobs: Deploy ke Staging (`deploy-staging`)
```yaml
  deploy-staging:
    name: Deploy ke Staging
    needs: [test-php, build-frontend]
```
- **Penjelasan**: Pekerjaan ketiga (uji coba server sebelum rilis final). Parameter `needs` mengatur bahwa job ini **HANYA** boleh berjalan JIKA `test-php` dan `build-frontend` telah berstatus "Sukses" (Hijau).
- **Alasan**: Guna CI/CD adalah mencegat kode rusak. Jika PHP atau Frontend-nya error, maka sistem otomatis membatalkan proses deploy.

```yaml
    if: github.ref == 'refs/heads/staging' && github.event_name == 'push'
    runs-on: ubuntu-latest
```
- **Penjelasan**: Kondisi (`if`) yang melarang job ini berjalan jika aksi bukan sebuah **push** murni ke cabang **staging**.

```yaml
    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Deploy Staging
        run: |
          echo "Memulai deploy ke staging..."
          echo "Deployment Staging Berhasil!"
```
- **Penjelasan**: Mensimulasikan proses transfer data dari GitHub ke server. Karena ini masih tingkat dasar, kita menggunakan perintah teks `echo` (print). Pada kondisi sesungguhnya, baris ini diganti dengan koneksi SSH/Rsync ke VPS/Hosting Anda.

---

### 5. Jobs: Deploy ke Production (`deploy-production`)
```yaml
  deploy-production:
    name: Deploy ke Production
    needs: [test-php, build-frontend]
    if: github.ref == 'refs/heads/production' && github.event_name == 'push'
    runs-on: ubuntu-latest
```
- **Penjelasan**: Konsepnya sama persis dengan `deploy-staging`, namun diperuntukkan pada server tahap akhir (Production/Live). Job ini **hanya** akan dijalankan kalau ada dorongan kode langsung ke branch **`production`**. Terlindungi kuat karena `needs` memastikan kodenya lolos ujian PHP dan Frontend sebelum benar-benar dirilis ke publik.
