# 📚 Sistem Informasi Perpustakaan BSI

Selamat datang di repositori Sistem Informasi Perpustakaan BSI. Aplikasi web ini dirancang untuk mempermudah pengelolaan manajemen katalog perpustakaan dengan antarmuka yang modern, cepat, dan responsif.

🌐 **Live Demo / Hosting:** [Kunjungi Aplikasi di Sini](https://perpustakaan-bsi.page.gd/)

---

## 🚀 Pembaruan Terbaru: Sistem _Server-Side_

Repositori ini telah mendapatkan pembaruan besar dengan beralih menggunakan sistem **Server-Side** (Pemrosesan Sisi Server).

**Apa artinya secara sederhana?**
Pada sistem _client-side_ (sisi pengguna), _browser_ di HP atau laptop Anda (seperti Chrome/Safari) harus bekerja keras untuk mengolah data dan menampilkannya. Namun, dengan sistem **Server-Side** yang baru ini, semua "pekerjaan berat" seperti memproses ribuan data buku, mencari judul, atau mengelola transaksi peminjaman diselesaikan secara langsung oleh **server**.

**Apa keuntungannya?**

1. ⚡ **Lebih Cepat & Ringan:** Aplikasi akan terasa jauh lebih cepat saat memuat halaman karena _browser_ pengguna hanya menerima hasil jadinya saja.
2. 📱 **Ramah Spesifikasi Rendah:** Sangat lancar diakses menggunakan HP atau komputer dengan spesifikasi rendah karena perangkat Anda tidak perlu ikut memproses data.
3. 🔒 **Lebih Aman:** Proses logika dan data sensitif disembunyikan di dalam server, sehingga lebih aman dari manipulasi pihak luar.

---

## ✨ Fitur Utama

- 🔐 **Sistem Autentikasi:** Login Admin yang aman menggunakan PHP Sessions.
- 📚 **Manajemen Katalog (CRUD):** Tambah, baca, perbarui, dan hapus data koleksi buku.
- 🔍 **Pencarian & Filter Pintar:** Pencarian Server-Side dan fitur urutkan data (Terbaru, Terlama, A-Z).
- 📄 **Paginasi Otomatis:** Membagi daftar buku menjadi beberapa halaman untuk efisiensi beban kerja.
- 📊 **Dasbor Statistik:** Ringkasan jumlah koleksi, penulis unik, dan tahun terbit terbaru.
- 📥 **Ekspor Data:** Fitur unduh (download) data tabel buku ke dalam format Excel (CSV).
- 🖼️ **Upload Cover:** Mendukung unggah gambar langsung dari perangkat atau melalui tautan (URL).
- 🌓 **Dark & Light Mode:** Desain UI/UX modern dengan efek _Glassmorphism_ dan _toggle_ tema yang tersimpan otomatis di _browser_.

## 🛠️ Teknologi yang Digunakan

- **Backend:** Native PHP 8+ (Procedural dengan _Prepared Statements_ untuk keamanan ekstra)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3 (Native CSS Variables & Flexbox/Grid), Vanilla JavaScript
- **Ikon:** Google Material Symbols
- **Deployment:** CI/CD via GitHub Actions ke InfinityFree

## ⚙️ Cara Menjalankan di Lokal (Development)

Jika Anda ingin menjalankan aplikasi ini di komputer Anda sendiri, ikuti langkah-langkah berikut:

1. Pastikan Anda telah menginstal _local server_ seperti **Laragon** atau **XAMPP**.
2. _Clone_ repositori ini ke dalam folder `www` (Laragon) atau `htdocs` (XAMPP):
   ```bash
   git clone https://github.com/mymn-fy/tugas-w3-dwsp.git
   ```
3. Buka phpMyAdmin atau program _database_ pilihan Anda (seperti HeidiSQL), lalu buat _database_ baru bernama `perpustakaan_bsi`.
4. Impor tabel database (jika Anda memiliki file `.sql` _backup_, jalankan di database tersebut). Jika Anda menggunakan struktur standar, pastikan tabel `databuku` telah terbuat.
5. Aplikasi sudah siap diakses lewat _browser_ di `http://localhost/tugas-w3-dwsp`. File `koneksi.php` sudah dirancang pintar untuk mendeteksi lingkungan lokal (menggunakan _user_ `root` dan _password_ kosong).

### 🔑 Kredensial Akses Login

Untuk mengakses Dasbor Admin di lingkungan lokal maupun _live_, gunakan kredensial berikut:

- **Username:** `perpusbsi`
- **Password:** `admin100`
