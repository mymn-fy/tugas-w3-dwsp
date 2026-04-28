<?php
session_start();

// Tampilkan error sementara untuk debugging (Hapus 2 baris ini jika web sudah berjalan normal)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- LOGIKA LOGIN & LOGOUT ---
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

$login_error = '';
if (isset($_POST['proses_login'])) {
    if ($_POST['username'] === 'perpusbsi' && $_POST['password'] === 'admin100') {
        $_SESSION['is_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $login_error = "Username atau password salah!";
    }
}

// Jika belum login, tampilkan halaman login saja dan hentikan skrip
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Perpustakaan BSI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <style>
        .login-wrapper { max-width: 400px; margin: 10vh auto; }
        .error-alert { background: rgba(239,68,68,0.1); color: var(--danger); padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 0.9rem; border: 1px solid rgba(239,68,68,0.2); }
    </style>
</head>
<body>
    <div class="container login-wrapper animate">
        <header>
            <h1 style="font-size: 1.8rem;">Sistem Perpustakaan</h1>
            <p>Silakan masuk untuk mengelola data</p>
        </header>
        <div class="form-card">
            <?php if ($login_error) echo "<div class='error-alert'>$login_error</div>"; ?>
            <form method="POST" action="index.php">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus autocomplete="off">
                </div>
                <div class="form-group" style="margin-bottom: 25px;">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="proses_login" class="btn btn-primary" style="width: 100%;">Masuk ke Dasbor</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php
    exit; // Menghentikan loading HTML & Database utama jika belum login
}

include 'koneksi.php';

// 1. Logika HAPUS data
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];    
    $stmt = mysqli_prepare($conn, "DELETE FROM databuku WHERE kode_buku = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $_SESSION['flash_msg'] = "Koleksi buku berhasil dihapus!";
    header("Location: index.php");
    exit;
}

// 2. Logika SIMPAN (Tambah Baru atau Edit)
if (isset($_POST['proses_simpan'])) {
    $id = $_POST['editId'];
    $judul = $_POST['judul'];
    $penulis = $_POST['penulis'];
    $tahun = $_POST['tahun'];
    $penerbit = $_POST['penerbit'];
    $cover_url = $_POST['cover_url'];
    $deskripsi = $_POST['deskripsi'];

    $cover_path_for_db = $_POST['cover_lama'] ?? ''; // Default ke path cover lama atau kosong

    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) { // Prioritas 1: Proses File Upload
        $upload_dir = 'assets/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = uniqid() . '_' . basename($_FILES['cover']['name']);
        $target_file = $upload_dir . $file_name;

        // Validasi Ekstensi & Ukuran (Max 2MB, hanya JPG/PNG)
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB dalam bytes

        if (in_array($_FILES['cover']['type'], $allowed_types) && $_FILES['cover']['size'] <= $max_size) {
            // Validasi tambahan (pastikan ini benar-benar file gambar)
            $check = getimagesize($_FILES['cover']['tmp_name']);
            if ($check !== false) {
                if (move_uploaded_file($_FILES['cover']['tmp_name'], $target_file)) {
                    $cover_path_for_db = $target_file; // Path baru untuk disimpan ke DB

                    // Jika sedang edit & ada cover lama, hapus file lama untuk hemat storage
                    if (!empty($_POST['cover_lama']) && file_exists($_POST['cover_lama'])) {
                        unlink($_POST['cover_lama']);
                    }
                }
            }
        } else {
            echo "<script>alert('Gagal: Format gambar harus JPG/PNG dan maksimal ukuran 2MB!'); window.history.back();</script>";
            exit; // Hentikan proses simpan ke database jika file tidak memenuhi syarat
        }
    } else if (!empty($cover_url)) { // Prioritas 2: Gunakan URL jika tidak ada file di-upload
        $cover_path_for_db = $cover_url;
        // Jika cover lama adalah file lokal (bukan URL) dan URL baru diberikan, hapus file lama
        if (!empty($_POST['cover_lama']) && file_exists($_POST['cover_lama']) && !filter_var($_POST['cover_lama'], FILTER_VALIDATE_URL)) {
            unlink($_POST['cover_lama']);
        }
    }

    if ($id == "") {
        // Logic Tambah Baru (kode_buku di-handle oleh AUTO_INCREMENT)
        $stmt = mysqli_prepare($conn, "INSERT INTO databuku (judul_buku, pengarang, penerbit, tahun_terbit, cover, deskripsi) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssiss", $judul, $penulis, $penerbit, $tahun, $cover_path_for_db, $deskripsi);
        mysqli_stmt_execute($stmt);
    } else {
        // Logic Edit dengan Prepared Statement
        $stmt = mysqli_prepare($conn, "UPDATE databuku SET judul_buku=?, pengarang=?, tahun_terbit=?, penerbit=?, cover=?, deskripsi=? WHERE kode_buku=?");
        mysqli_stmt_bind_param($stmt, "ssisssi", $judul, $penulis, $tahun, $penerbit, $cover_path_for_db, $deskripsi, $id);
        mysqli_stmt_execute($stmt);
    }
    header("Location: index.php");
}

// Tangkap pesan flash jika ada, lalu segera hapus dari sesi
$flash_message = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_msg']);

// 3. Ambil data
// Logika Paginasi
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

$search_term = $_GET['search'] ?? '';
$sort_option = $_GET['sort'] ?? 'default';
$search_query_string = !empty($search_term) ? '&search=' . urlencode($search_term) : '';
$search_query_string .= $sort_option !== 'default' ? '&sort=' . urlencode($sort_option) : '';

$offset = ($current_page - 1) * $items_per_page;

// Menyiapkan query untuk total buku dan data buku dengan atau tanpa pencarian
$count_sql = "SELECT COUNT(*) AS total FROM databuku";
$data_sql = "SELECT * FROM databuku";
$params = [];
$types = "";

if (!empty($search_term)) {
    $search_like = "%{$search_term}%";
    $where_clause = " WHERE judul_buku LIKE ? OR pengarang LIKE ? OR penerbit LIKE ?";
    $count_sql .= $where_clause;
    $data_sql .= $where_clause;
    $params = [$search_like, $search_like, $search_like];
    $types = "sss";
}

// Logika Pengurutan
$order_clause = "kode_buku DESC"; // Default: Buku yang baru ditambahkan
if ($sort_option === 'tahun_terbaru') {
    $order_clause = "tahun_terbit DESC, kode_buku DESC";
} elseif ($sort_option === 'tahun_terlama') {
    $order_clause = "tahun_terbit ASC, kode_buku DESC";
} elseif ($sort_option === 'abjad_az') {
    $order_clause = "judul_buku ASC";
}

$data_sql .= " ORDER BY {$order_clause} LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

// Eksekusi query untuk total buku
$stmt_count = mysqli_prepare($conn, $count_sql);
if (!empty($search_term)) mysqli_stmt_bind_param($stmt_count, "sss", ...array_slice($params, 0, 3));
mysqli_stmt_execute($stmt_count);
$total_books = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['total'];
$total_pages = ceil($total_books / $items_per_page);

// Hitung statistik tambahan untuk dashboard
$author_query = mysqli_query($conn, "SELECT COUNT(DISTINCT pengarang) AS total_author FROM databuku");
$total_authors = mysqli_fetch_assoc($author_query)['total_author'] ?? 0;

$year_query = mysqli_query($conn, "SELECT MAX(tahun_terbit) AS newest_year FROM databuku");
$newest_year = mysqli_fetch_assoc($year_query)['newest_year'] ?? '-';


// Eksekusi query untuk data buku
$stmt_data = mysqli_prepare($conn, $data_sql);
if (!empty($types)) mysqli_stmt_bind_param($stmt_data, $types, ...$params);
mysqli_stmt_execute($stmt_data);
$query = mysqli_stmt_get_result($stmt_data);
$books = [];
while ($row = mysqli_fetch_assoc($query)) {
    $books[] = [
        'id'       => htmlspecialchars($row['kode_buku'] ?? ''),
        'judul'    => htmlspecialchars($row['judul_buku'] ?? ''),
        'penulis'  => htmlspecialchars($row['pengarang'] ?? ''),
        'tahun'    => htmlspecialchars($row['tahun_terbit'] ?? ''),
        'penerbit' => htmlspecialchars($row['penerbit'] ?? ''),
        'deskripsi'=> htmlspecialchars($row['deskripsi'] ?? ''),
        'cover'    => !empty($row['cover']) ? htmlspecialchars($row['cover']) : 'assets/nobook.png'
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Perpustakaan BSI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body>

    <!-- NAVBAR ELEGAN -->
    <nav class="navbar">
        <div class="navbar-inner">
            <div class="navbar-brand">
                <h1>Sistem Perpustakaan BSI</h1>
                <p>Katalog Digital Literasi BSI</p>
            </div>
            <div class="navbar-actions">
                <button id="themeToggle" class="btn btn-outline btn-icon-nav" title="Ganti Mode">
                    <span id="themeIcon" class="material-symbols-outlined" style="font-size: 1.3rem;">light_mode</span>
                </button>
                <a href="#" class="btn btn-outline btn-logout" onclick="konfirmasiLogout(event)" title="Keluar">
                    <span class="material-symbols-outlined" style="font-size: 1.3rem; margin-right: 6px;">logout</span>
                    <span class="logout-text">Keluar</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container" style="padding-top: 20px;">

        <!-- HALAMAN DAFTAR -->
        <section id="view-list" class="animate">
            
            <!-- DASHBOARD STATISTIK RINGKAS -->
            <div class="dashboard-card">
                <div class="dashboard-header">
                    <h2 class="dashboard-title">Dashboard Statistik</h2>
                    <a href="?export=csv" class="btn btn-primary" style="padding: 8px 15px; font-size: 0.85rem; border-radius: 8px;"><span class="material-symbols-outlined" style="font-size: 1.1rem;">download</span> Export Excel (CSV)</a>
                </div>
                <div class="stats-grid">
                    <div class="stat-capsule">
                        <div class="stat-icon"><span class="material-symbols-outlined" style="font-size: 1.6rem;">library_books</span></div>
                        <div class="stat-info">
                            <div class="stat-title">Total Koleksi</div>
                            <div class="stat-value"><?php echo $total_books; ?> <span>Buku</span></div>
                        </div>
                    </div>
                    <div class="stat-capsule">
                        <div class="stat-icon"><span class="material-symbols-outlined" style="font-size: 1.6rem;">group</span></div>
                        <div class="stat-info">
                            <div class="stat-title">Penulis Unik</div>
                            <div class="stat-value"><?php echo $total_authors; ?> <span>Orang</span></div>
                        </div>
                    </div>
                    <div class="stat-capsule">
                        <div class="stat-icon"><span class="material-symbols-outlined" style="font-size: 1.6rem;">auto_awesome</span></div>
                        <div class="stat-info">
                            <div class="stat-title">Tahun Terbaru</div>
                            <div class="stat-value"><?php echo $newest_year; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <form action="index.php" method="GET">
                <div class="toolbar">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Cari berdasarkan judul, penulis, penerbit..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="default" <?php echo $sort_option == 'default' ? 'selected' : ''; ?>>Urutkan: Baru Ditambahkan</option>
                        <option value="tahun_terbaru" <?php echo $sort_option == 'tahun_terbaru' ? 'selected' : ''; ?>>Urutkan: Tahun Terbaru</option>
                        <option value="tahun_terlama" <?php echo $sort_option == 'tahun_terlama' ? 'selected' : ''; ?>>Urutkan: Tahun Terlama</option>
                        <option value="abjad_az" <?php echo $sort_option == 'abjad_az' ? 'selected' : ''; ?>>Urutkan: Abjad Judul (A-Z)</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Cari</button>
                </div>
            </form>

            <div class="card-table">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul Buku</th>
                            <th>Penulis</th>
                            <th>Tahun</th>
                            <th>Penerbit</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="isiTabel"></tbody>
                </table>
            </div>

            <div class="action-footer" style="margin-top:20px; text-align:center;">
                <button class="btn btn-primary" onclick="bukaForm()">+ Tambah Koleksi Baru</button>
            </div>

            <!-- Paginasi -->
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?><?php echo $search_query_string; ?>">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <span class="current-page"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search_query_string; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?><?php echo $search_query_string; ?>">Next</a>
                <?php endif; ?>
            </div>

        </section>

        <!-- HALAMAN DETAIL -->
        <section id="view-detail" class="hidden animate">
            <button class="btn btn-outline" style="margin-bottom: 20px;" onclick="gantiHalaman('view-list')">← Kembali</button>
            <div id="kontenDetail"></div>
        </section>

        <!-- HALAMAN FORM -->
        <section id="view-form" class="hidden animate">
            <div class="form-card">
                <h2 id="formTitle" style="margin-top:0; color:var(--primary); margin-bottom: 25px;">Form Koleksi</h2>
                
                <form method="POST" action="index.php" enctype="multipart/form-data">
                    <div class="form-grid">
                        <input type="hidden" id="editId" name="editId">
                        <input type="hidden" id="f_cover_lama" name="cover_lama">
                        <div class="form-group">
                            <label>Judul Buku</label>
                            <input type="text" id="f_judul" name="judul" required>
                        </div>
                        <div class="form-group">
                            <label>Penulis</label>
                            <input type="text" id="f_penulis" name="penulis" required>
                        </div>
                        <div class="form-group">
                            <label>Tahun Terbit</label>
                            <input type="text" id="f_tahun" name="tahun">
                        </div>
                        <div class="form-group">
                            <label>Penerbit</label>
                            <input type="text" id="f_penerbit" name="penerbit">
                        </div>
                        <div class="form-group full-width">
                            <label>Upload Cover Buku</label>
                            <img id="cover_preview" src="" alt="Preview Cover" style="max-width: 150px; border-radius: 8px; margin-bottom: 10px; display: none;">
                            <input type="file" id="f_cover" name="cover" accept=".jpg, .jpeg, .png">
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Kosongkan jika tidak ingin mengubah cover.</small>
                        </div>
                        <div class="form-group full-width">
                            <label>Atau masukkan URL Cover</label>
                            <input type="text" id="f_cover_url" name="cover_url" placeholder="https://example.com/cover.jpg">
                        </div>
                        <div class="form-group full-width">
                            <label>Sinopsis / Deskripsi</label>
                            <textarea id="f_deskripsi" name="deskripsi" rows="5"></textarea>
                        </div>
                    </div>
                <div class="form-actions" style="margin-top: 30px; display: flex; gap: 12px;">
                        <button type="submit" name="proses_simpan" class="btn btn-primary">Simpan ke Database</button>
                        <button type="button" class="btn btn-outline" onclick="gantiHalaman('view-list')">Batal</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <!-- FOOTER (di luar .container agar full-width) -->
    <footer>
        <div class="footer-inner">
            <div class="footer-brand">Sistem Perpustakaan BSI</div>
            <div class="footer-copy">&copy; 2026 &mdash; Dikembangkan oleh <span><a href="https://github.com/mymn-fy/tugas-w3-dwsp" target="_blank" style="color: inherit; text-decoration: none;">Min</a></span>.</div>
        </div>
    </footer>

    <!-- MODAL KONFIRMASI HAPUS -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <h3 style="margin: 0; color: var(--text-main);">Konfirmasi Hapus</h3>
            <div class="modal-body">Apakah Anda yakin ingin menghapus buku ini? Data yang dihapus tidak dapat dikembalikan.</div>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="btn btn-outline" onclick="closeDeleteModal()">Batal</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Ya, Hapus Buku</button>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI KELUAR -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-box">
            <h3 style="margin: 0; color: var(--text-main);">Konfirmasi Keluar</h3>
            <div class="modal-body">Apakah Anda yakin ingin mengakhiri sesi dan keluar dari sistem?</div>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="btn btn-outline" onclick="closeLogoutModal()">Batal</button>
                <a href="?logout=true" class="btn btn-danger" style="text-decoration: none;">Ya, Keluar</a>
            </div>
        </div>
    </div>

    <?php if ($flash_message): ?>
    <!-- TOAST NOTIFICATION -->
    <div id="toast" class="toast">
        <span class="material-symbols-outlined" style="margin-right: 10px; font-size: 1.5rem; color: var(--primary);">check_circle</span> <?php echo $flash_message; ?>
    </div>
    <?php endif; ?>

    <script>
        // Variabel global untuk paginasi
        const currentPage = <?php echo $current_page; ?>;
        let daftarBuku = <?php echo json_encode($books); ?>;
    </script>
    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>