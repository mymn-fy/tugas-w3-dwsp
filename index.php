<?php
// Tampilkan error sementara untuk debugging (Hapus 2 baris ini jika web sudah berjalan normal)
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';

// 1. Logika HAPUS data
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];    
    $stmt = mysqli_prepare($conn, "DELETE FROM databuku WHERE kode_buku = ?");
    mysqli_stmt_bind_param($stmt, "s", $id);
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
    $cover = $_POST['cover'];
    $deskripsi = $_POST['deskripsi'];

    if ($id == "") {
        // Logic Tambah Baru dengan Prepared Statement
        $kode_baru = "B" . time();
        $stmt = mysqli_prepare($conn, "INSERT INTO databuku (kode_buku, judul_buku, pengarang, penerbit, tahun_terbit, cover, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssiss", $kode_baru, $judul, $penulis, $penerbit, $tahun, $cover, $deskripsi);
        mysqli_stmt_execute($stmt);
    } else {
        // Logic Edit dengan Prepared Statement
        $stmt = mysqli_prepare($conn, "UPDATE databuku SET judul_buku=?, pengarang=?, tahun_terbit=?, penerbit=?, cover=?, deskripsi=? WHERE kode_buku=?");
        mysqli_stmt_bind_param($stmt, "ssissss", $judul, $penulis, $tahun, $penerbit, $cover, $deskripsi, $id);
        mysqli_stmt_execute($stmt);
    }
    header("Location: index.php");
}

// 3. Ambil data
$query = mysqli_query($conn, "SELECT * FROM databuku ORDER BY kode_buku DESC");
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
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; line-height: 1.6; display: flex; flex-direction: column; min-height: 100vh; }
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
            <div class="toolbar">
                <div class="search-box">
                    <input type="text" id="inputPencarian" placeholder="Cari buku..." onkeyup="handleSearchKey(event)">
                </div>
                <button class="btn btn-primary" onclick="filterTabel()">Cari</button>
            </div>

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
                
                <form method="POST" action="index.php">
                    <div class="form-grid">
                        <input type="hidden" id="editId" name="editId">
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
                            <label>URL / Path Cover Buku (e.g. assets/buku.jpg)</label>
                            <input type="text" id="f_cover" name="cover">
                        </div>
                        <div class="form-group full-width">
                            <label>Sinopsis / Deskripsi</label>
                            <textarea id="f_deskripsi" name="deskripsi" rows="5"></textarea>
                        </div>
                    </div>
                    <div style="margin-top: 30px; display: flex; gap: 12px;">
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
        let daftarBuku = <?php echo json_encode($books); ?>;

        function gantiHalaman(id) {
            document.querySelectorAll('section').forEach(s => s.classList.add('hidden'));
            document.getElementById(id).classList.remove('hidden');
        }

        function renderTabel() {
            const tbody = document.getElementById('isiTabel');
            tbody.innerHTML = daftarBuku.map((b, i) => `
                <tr>
                    <td>${i+1}</td>
                    <td><span class="book-link" onclick="tampilkanDetail('${b.id}')">${b.judul}</span></td>
                    <td>${b.penulis}</td>
                    <td>${b.tahun}</td>
                    <td><span class="badge">${b.penerbit}</span></td>
                    <td style="text-align:center;">
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
            document.getElementById('formTitle').innerText = id ? "Edit Koleksi" : "Tambah Koleksi";
            document.getElementById('editId').value = id || "";
            document.getElementById('f_judul').value = b ? b.judul : "";
            document.getElementById('f_penulis').value = b ? b.penulis : "";
            document.getElementById('f_tahun').value = b ? b.tahun : "";
            document.getElementById('f_penerbit').value = b ? b.penerbit : "";
            document.getElementById('f_cover').value = b ? b.cover : "";
            document.getElementById('f_deskripsi').value = b ? b.deskripsi : "";
            gantiHalaman('view-form');
        }

        function hapusBuku(id) {
            if(confirm("Hapus buku?")) window.location.href = "index.php?hapus=" + id;
        }

        function filterTabel() {
            const key = document.getElementById('inputPencarian').value.toLowerCase();
            const rows = document.querySelectorAll('#isiTabel tr');
            rows.forEach(r => r.style.display = r.innerText.toLowerCase().includes(key) ? '' : 'none');
        }

        function handleSearchKey(e) { if(e.key === "Enter") filterTabel(); }

        renderTabel();
    </script>
</body>
</html>