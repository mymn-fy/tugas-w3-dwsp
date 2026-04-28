<?php
// Mengaktifkan pelaporan error untuk mysqli sebagai exception
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Cek apakah web diakses lewat localhost atau domain berakhiran .test (Laragon)
$is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || strpos($_SERVER['HTTP_HOST'] ?? '', '.test') !== false;

if ($is_local) {
    // Kredensial untuk Laragon / localhost
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "perpustakaan_bsi";
} else {
    // Kredensial untuk InfinityFree
    $host = "sql207.infinityfree.com";
    $user = "if0_41767982";
    $pass = "ou2AsaiKfnGT";
    $db   = "if0_41767982_perpustakaan_bsi";
}

try {
    $conn = mysqli_connect($host, $user, $pass, $db);
    // Mengatur character set koneksi. Penting untuk data non-latin.
    mysqli_set_charset($conn, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Di lingkungan produksi, error ini sebaiknya dicatat (log) ke file, bukan ditampilkan.
    // Untuk sekarang, kita tampilkan pesan yang lebih umum.
    // exit() adalah alternatif yang lebih terkontrol daripada die()
    if ($is_local) {
        // Tampilkan pesan error detail jika di lokal untuk mempermudah perbaikan
        exit("Terjadi masalah dengan koneksi database lokal: " . $e->getMessage()); 
    } else {
        exit("Terjadi masalah dengan koneksi database."); 
    }
}
?>