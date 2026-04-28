<?php
// Tampilkan error sementara untuk debugging (Hapus 2 baris ini jika web sudah berjalan normal)
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';

// 1. Logika HAPUS data
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];    
    $stmt = mysqli_prepare($conn, "DELETE FROM databuku WHERE kode_buku = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: index.php");
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

// 3. Ambil data
// Logika Paginasi
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

$search_term = $_GET['search'] ?? '';
$search_query_string = !empty($search_term) ? '&search=' . urlencode($search_term) : '';

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

$data_sql .= " ORDER BY kode_buku DESC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

// Eksekusi query untuk total buku
$stmt_count = mysqli_prepare($conn, $count_sql);
if (!empty($search_term)) mysqli_stmt_bind_param($stmt_count, "sss", ...array_slice($params, 0, 3));
mysqli_stmt_execute($stmt_count);
$total_books = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['total'];
$total_pages = ceil($total_books / $items_per_page);

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
        'cover'    => $row['cover'] ? htmlspecialchars($row['cover']) : 'assets/default.jpg'
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
    
    <style>
        :root {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --primary: #48a39e;
            --primary-hover: #367c78;
            --accent: #F2AB3B;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --danger: #ef4444;
            --border: rgba(255, 255, 255, 0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; line-height: 1.6; display: flex; flex-direction: column; min-height: 100vh; overflow-x: hidden; }
        .container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; flex: 1; }
        .hidden { display: none !important; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate { animation: fadeIn 0.4s ease forwards; }
        header { text-align: center; margin-bottom: 40px; }
        header h1 { font-size: 2.2rem; font-weight: 800; color: var(--primary); margin: 0; }
        header p { color: var(--text-muted); margin-top: 8px; font-size: 0.95rem; }
        .toolbar { display: flex; gap: 10px; margin-bottom: 20px; background: var(--bg-card); padding: 10px; border-radius: 14px; border: 1px solid var(--border); }
        .search-box { flex: 1; }
        .search-box input { width: 100%; background: #0f172a; border: 1px solid var(--border); padding: 12px 18px; border-radius: 10px; color: white; font-size: 0.95rem; }
        .btn { padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: var(--transition); border: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-main); }
        .card-table { background: var(--bg-card); border-radius: 18px; border: 1px solid var(--border); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(72, 163, 158, 0.1); padding: 18px 20px; color: var(--primary); font-size: 0.8rem; text-transform: uppercase; text-align: left; border-bottom: 1px solid var(--border); }
        td { padding: 16px 20px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        .book-link { color: var(--text-main); font-weight: 600; text-decoration: none; cursor: pointer; }
        .badge { background: rgba(72, 163, 158, 0.15); color: var(--primary); padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .detail-card { background: var(--bg-card); padding: 40px; border-radius: 20px; border: 1px solid var(--border); display: flex; gap: 40px; }
        .cover-area { flex: 0 0 240px; }
        .cover-area img { width: 100%; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .meta-label { color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px; }
        .form-card { background: var(--bg-card); padding: 35px; border-radius: 20px; border: 1px solid var(--border); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.85rem; color: var(--text-muted); }
        .form-group input, .form-group textarea { background: #0f172a; border: 1px solid var(--border); padding: 12px; border-radius: 10px; color: white; }
        .btn-icon { background: rgba(255,255,255,0.05); border: none; cursor: pointer; padding: 10px; border-radius: 8px; color: var(--text-muted); margin-left: 5px; }

        /* ===== FOOTER ===== */
        footer {
            background: var(--bg-card);
            border-top: 1px solid var(--border);
            margin-top: 60px;
            padding: 28px 20px;
            text-align: center;
        }
        .footer-inner {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .footer-brand {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 0.03em;
        }
        .footer-copy {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .footer-copy span {
            color: var(--accent);
            font-weight: 600;
        }

        /* ===== RESPONSIVE (MOBILE) ===== */
        @media (max-width: 768px) {
            .container { padding: 25px 15px; }
            header h1 { font-size: 1.8rem; }
            .toolbar { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
            
            /* Mengubah Tabel Menjadi Tampilan Card di HP */
            .card-table { background: transparent; border: none; overflow: visible; }
            .card-table table, .card-table thead, .card-table tbody, .card-table th, .card-table td, .card-table tr { display: block; }
            .card-table thead { display: none; }
            .card-table tr { margin-bottom: 15px; background: var(--bg-card); border-radius: 14px; border: 1px solid var(--border); padding: 10px; }
            .card-table td { display: flex; justify-content: space-between; align-items: center; padding: 12px 10px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: right; }
            .card-table td:last-child { border-bottom: none; justify-content: center; padding-top: 15px; }
            .card-table td::before { content: attr(data-label); font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; text-align: left; margin-right: 15px; }
            .card-table td:last-child::before { display: none; }

            .detail-card { flex-direction: column; padding: 25px 20px; gap: 25px; align-items: center; text-align: center; }
            .cover-area { flex: auto; max-width: 220px; }
            .meta-grid { grid-template-columns: 1fr; gap: 15px; text-align: left; }
            .form-card { padding: 25px 15px; }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .form-actions { flex-direction: column; }

            /* Paginasi Responsif */
            .pagination { flex-wrap: wrap; justify-content: center; }
            .pagination a, .pagination span {
                padding: 8px 12px;
                margin: 5px;
            }
        }

        /* Paginasi */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 5px;
        }
        .pagination a, .pagination span {
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-main);
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            transition: var(--transition);
        }
        .pagination a:hover {
            background-color: var(--primary);
            color: #fff;
        }
        .pagination .current-page {
            background-color: var(--primary);
            color: #fff;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="container">
        <header>
            <h1>Sistem Perpustakaan BSI</h1>
            <p>Katalog Digital Literasi BSI</p>
        </header>

        <!-- HALAMAN DAFTAR -->
        <section id="view-list" class="animate">
            <form action="index.php" method="GET">
                <div class="toolbar">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Cari berdasarkan judul, penulis, penerbit..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
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
            <div class="footer-brand">BSI Digital Library</div>
            <div class="footer-copy">&copy; 2026 &mdash; Dikembangkan oleh <span>Min</span>.</div>
        </div>
    </footer>

    <script>
        // Variabel global untuk paginasi
        const currentPage = <?php echo $current_page; ?>;
        let daftarBuku = <?php echo json_encode($books); ?>;

        function gantiHalaman(id) {
            document.querySelectorAll('section').forEach(s => s.classList.add('hidden'));
            document.getElementById(id).classList.remove('hidden');
        }

        function renderTabel() {
            const tbody = document.getElementById('isiTabel');
            tbody.innerHTML = daftarBuku.map((b, i) => `
                <tr>
                    <td data-label="No">${i+1}</td>
                    <td data-label="Judul Buku"><span class="book-link" onclick="tampilkanDetail('${b.id}')">${b.judul}</span></td>
                    <td data-label="Penulis">${b.penulis}</td>
                    <td data-label="Tahun">${b.tahun}</td>
                    <td data-label="Penerbit"><span class="badge">${b.penerbit}</span></td>
                    <td data-label="Aksi" style="text-align:center;">
                        <button class="btn-icon" onclick="bukaForm('${b.id}')">✎</button>
                        <button class="btn-icon" onclick="hapusBuku('${b.id}')">🗑</button>
                    </td>
                </tr>
            `).join('');
        }

        function tampilkanDetail(id) {
            const b = daftarBuku.find(x => x.id === id);
            document.getElementById('kontenDetail').innerHTML = `
                <div class="detail-card">
                    <div class="cover-area"><img src="${b.cover}" onerror="this.src='https://via.placeholder.com/300x450?text=No+Cover'"></div>
                    <div class="info-area">
                        <span class="meta-label">Judul</span><h2>${b.judul}</h2>
                        <div class="meta-grid">
                            <div><span class="meta-label">Penulis</span><div class="meta-value">${b.penulis}</div></div>
                            <div><span class="meta-label">Tahun</span><div class="meta-value">${b.tahun}</div></div>
                            <div><span class="meta-label">Penerbit</span><div class="meta-value">${b.penerbit}</div></div>
                        </div>
                        <div style="margin-top:20px;"><span class="meta-label">Sinopsis</span><p>${b.deskripsi}</p></div>
                    </div>
                </div>
            `;
            gantiHalaman('view-detail');
        }

        function bukaForm(id = null) {
            const b = daftarBuku.find(x => x.id === id);
            const coverPreview = document.getElementById('cover_preview');

            document.getElementById('formTitle').innerText = id ? "Edit Koleksi" : "Tambah Koleksi";
            document.getElementById('editId').value = id || "";
            document.getElementById('f_judul').value = b ? b.judul : "";
            document.getElementById('f_penulis').value = b ? b.penulis : "";
            document.getElementById('f_tahun').value = b ? b.tahun : "";
            document.getElementById('f_penerbit').value = b ? b.penerbit : "";

            // Handle cover preview dan path lama
            document.getElementById('f_cover_lama').value = b ? b.cover : "";
            const coverUrlInput = document.getElementById('f_cover_url');

            if (b && b.cover) { // Jika ada data cover
                coverPreview.src = b.cover;
                coverPreview.style.display = 'block';
                // Cek apakah cover adalah URL, jika ya, isikan ke input URL
                if (b.cover.startsWith('http')) {
                    coverUrlInput.value = b.cover;
                } else {
                    coverUrlInput.value = "";
                }
            } else {
                coverPreview.style.display = 'none';
                coverUrlInput.value = "";
            }
            document.getElementById('f_cover').value = ""; // Reset input file

            document.getElementById('f_deskripsi').value = b ? b.deskripsi : "";
            gantiHalaman('view-form');
        }

        // Validasi input file di sisi klien (browser) & Live Preview
        document.getElementById('f_cover').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) { // 2MB
                    alert('Ukuran file maksimal 2MB!');
                    this.value = ""; // Reset input form
                    return;
                }
                const allowedTypes = ['image/jpeg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file hanya boleh JPG atau PNG!');
                    this.value = ""; // Reset input form
                    return;
                }
                
                // Menampilkan preview gambar yang baru dipilih secara live
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('cover_preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Live preview saat pengguna mengetik di input URL
        document.getElementById('f_cover_url').addEventListener('input', function() {
            const url = this.value;
            const preview = document.getElementById('cover_preview');
            if (url) {
                preview.src = url;
                preview.style.display = 'block';
            } else if (!document.getElementById('f_cover').files[0]) {
                // Sembunyikan preview hanya jika input file juga kosong
                preview.style.display = 'none';
            }
        });

        function hapusBuku(id) {
            if(confirm("Hapus buku?")) window.location.href = "index.php?hapus=" + id;
        }

        renderTabel();
    </script>
</body>
</html>