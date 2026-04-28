<?php
// Mengaktifkan pelaporan error untuk mysqli sebagai exception
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Sesuaikan kredensial di bawah ini dengan detail database dari InfinityFree (vPanel)
// Informasi ini dapat ditemukan di menu "MySQL Databases"
$host = "sql207.infinityfree.com";        // Ganti dengan MySQL Host Name dari InfinityFree (contoh: sql123.epizy.com atau sql123.infinityfree.com)
$user = "if0_41767982";             // Ganti dengan MySQL User Name dari InfinityFree
$pass = "ou2AsaiKfnGT";    // Ganti dengan vPanel Password akun InfinityFree Anda
$db   = "if0_41767982_perpustakaan_bsi"; // Nama database di hosting (biasanya otomatis ditambah awalan id akun Anda)

try {
    $conn = mysqli_connect($host, $user, $pass, $db);
    // Mengatur character set koneksi. Penting untuk data non-latin.
    mysqli_set_charset($conn, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Di lingkungan produksi, error ini sebaiknya dicatat (log) ke file, bukan ditampilkan.
    // Untuk sekarang, kita tampilkan pesan yang lebih umum.
    // exit() adalah alternatif yang lebih terkontrol daripada die()
    exit("Terjadi masalah dengan koneksi database."); 
}
?>