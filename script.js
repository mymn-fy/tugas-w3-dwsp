function gantiHalaman(id) {
    document.querySelectorAll('section').forEach(s => s.classList.add('hidden'));
    document.getElementById(id).classList.remove('hidden');
}

function renderTabel() {
    const tbody = document.getElementById('isiTabel');
    
    // Jika tidak ada data buku
    if (daftarBuku.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="empty-state-cell">
                    <div class="empty-icon"><span class="material-symbols-outlined" style="font-size: inherit;">library_books</span></div>
                    <h3 class="empty-title">Tidak Ada Data Ditemukan</h3>
                    <p class="empty-desc">Belum ada koleksi buku di sini atau kata kunci pencarian Anda tidak cocok.</p>
                </td>
            </tr>
        `;
        return; // Hentikan fungsi sampai di sini
    }

    tbody.innerHTML = daftarBuku.map((b, i) => `
        <tr>
            <td data-label="No">${i+1}</td>
            <td data-label="Judul Buku"><span class="book-link" onclick="tampilkanDetail('${b.id}')">${b.judul}</span></td>
            <td data-label="Penulis">${b.penulis}</td>
            <td data-label="Tahun">${b.tahun}</td>
            <td data-label="Penerbit"><span class="badge">${b.penerbit}</span></td>
            <td data-label="Aksi" style="text-align:center;">
                <button class="btn-icon" title="Edit" onclick="bukaForm('${b.id}')"><span class="material-symbols-outlined" style="font-size: 1.1rem;">edit</span></button>
                <button class="btn-icon" title="Hapus" onclick="hapusBuku('${b.id}')"><span class="material-symbols-outlined" style="font-size: 1.1rem;">delete</span></button>
            </td>
        </tr>
    `).join('');
}

function tampilkanDetail(id) {
    const b = daftarBuku.find(x => x.id === id);
    document.getElementById('kontenDetail').innerHTML = `
        <div class="detail-card">
            <div class="cover-area"><img src="${b.cover}" onerror="this.src='assets/nobook.png'"></div>
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

let bookIdToDelete = null;

function hapusBuku(id) {
    bookIdToDelete = id;
    document.getElementById('deleteModal').classList.add('active');
}

function konfirmasiLogout(e) {
    if (e) e.preventDefault(); // Mencegah browser berpindah ke href="#"
    document.getElementById('logoutModal').classList.add('active');
}

function closeLogoutModal() {
    document.getElementById('logoutModal').classList.remove('active');
}

function closeDeleteModal() {
    bookIdToDelete = null;
    document.getElementById('deleteModal').classList.remove('active');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if(bookIdToDelete) window.location.href = "index.php?hapus=" + bookIdToDelete;
});

renderTabel();

// --- THEME TOGGLE LOGIC ---
const themeToggleBtn = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');

function updateThemeIcon(theme) {
    if(themeIcon) themeIcon.innerText = theme === 'light' ? 'dark_mode' : 'light_mode';
}

// Inisialisasi ikon saat pertama kali dimuat
const currentTheme = document.documentElement.getAttribute('data-theme');
updateThemeIcon(currentTheme);

if(themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
        let theme = document.documentElement.getAttribute('data-theme');
        let newTheme = theme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme);
    });
}

// --- TOAST NOTIFICATION LOGIC ---
const toast = document.getElementById('toast');
if (toast) {
    setTimeout(() => {
        toast.classList.add('show');
    }, 100); // Muncul melayang dari samping kanan (animasi masuk)

    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000); // Hilang otomatis setelah 4 detik (animasi keluar)
}