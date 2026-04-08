<?php
// FILE INI SENGAJA DIBUAT UNTUK MENGGAGALKAN PIPELINE
// GitHub Actions (job: test-php) akan mendeteksi syntax error di file ini
// karena kurangnya titik koma (;) pada baris di bawah ini dan kurung kurawal yang tidak tertutup

echo "Teks ini tidak diakhiri dengan titik koma"

function fungsiYangRusak() {
    echo "Fungsi ini tidak memiliki kurung kurawal penutup";

// Ketika file ini di-push ke GitHub, pipeline akan merah (Failed)
// Hapus file ini jika Anda ingin pipeline kembali sukses (Passed).
