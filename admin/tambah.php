<?php
// Memulai session untuk mengakses data user yang login
session_start();
// Menghubungkan ke database
include '../config/koneksi.php';

// ========== AMBIL USER_ID DARI SESSION (FLEKSIBEL) ==========
// Inisialisasi variabel user_id dengan null
$user_id = null;

// Cek berbagai kemungkinan struktur session (untuk kompatibilitas)
// Jika session menyimpan user_id sebagai ['user']['id']
if (isset($_SESSION['user']['id'])) {
    $user_id = $_SESSION['user']['id'];
} 
// Jika session menyimpan user_id langsung sebagai ['user_id']
elseif (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} 
// Jika session menyimpan user_id sebagai ['user']['user_id']
elseif (isset($_SESSION['user']['user_id'])) {
    $user_id = $_SESSION['user']['user_id'];
} 
// Jika session menyimpan user_id sebagai ['id_user']
elseif (isset($_SESSION['id_user'])) {
    $user_id = $_SESSION['id_user'];
} 
// Jika session menyimpan user_id sebagai ['id']
elseif (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
}

// Cek role admin dari berbagai kemungkinan struktur session
$is_admin = false;
// Cek dari ['user']['role']
if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] == 'admin') {
    $is_admin = true;
} 
// Cek dari ['role']
elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    $is_admin = true;
} 
// Cek dari ['user']['level']
elseif (isset($_SESSION['user']['level']) && $_SESSION['user']['level'] == 'admin') {
    $is_admin = true;
} 
// Cek dari ['level']
elseif (isset($_SESSION['level']) && $_SESSION['level'] == 'admin') {
    $is_admin = true;
}

// Jika bukan admin, redirect ke halaman login
if (!$is_admin) {
    header("Location: ../auth/login.php");
    exit;
}

// Jika user_id masih null (belum terdeteksi), ambil dari database
if (!$user_id) {
    // Coba ambil user admin pertama dari database
    $query_admin = mysqli_query($conn, "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1");
    if (mysqli_num_rows($query_admin) > 0) {
        $row_admin = mysqli_fetch_assoc($query_admin);
        $user_id = $row_admin['user_id'];
        
        // Simpan ke session agar tidak perlu query lagi di lain waktu
        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = 'admin';
    } else {
        // Jika tidak ada admin sama sekali di database, hentikan program dengan pesan error
        die("Error: Tidak ada user admin di database. Silakan buat user admin terlebih dahulu.");
    }
}

// Jika tombol simpan ditekan (method POST)
if (isset($_POST['simpan'])) {
    // Membersihkan input dari karakter berbahaya (SQL Injection)
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // ========== PROSES UPLOAD FILE ==========
    // Inisialisasi variabel file dengan nilai null (opsional)
    $file_name = null;
    $file_path = null;
    
    // Cek apakah ada file yang diupload dan tidak ada error
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
        // Tentukan direktori upload (folder uploads di luar folder admin)
        $upload_dir = '../uploads/';
        
        // Buat folder jika belum ada (dengan permission 0777)
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Ambil nama file asli dan ekstensinya
        $file_name_original = basename($_FILES['file_upload']['name']);
        $file_extension = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
        // Buat nama file unik dengan timestamp + random string
        $new_file_name = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $new_file_name;
        
        // Daftar ekstensi file yang diizinkan
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        
        // Jika ekstensi file diizinkan
        if (in_array($file_extension, $allowed_types)) {
            // Pindahkan file dari tmp ke folder tujuan
            if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target_file)) {
                // Simpan nama file asli dan path file ke database
                $file_name = mysqli_real_escape_string($conn, $file_name_original);
                $file_path = mysqli_real_escape_string($conn, 'uploads/' . $new_file_name);
            }
        }
    }
    
    // ========== QUERY INSERT ==========
    // Query INSERT dengan nilai file opsional (NULL jika tidak ada file)
    $query = "INSERT INTO tasks 
    (user_id, title, description, deadline, status, created_at, file_name, file) 
    VALUES ('$user_id', '$title', '$desc', '$deadline', '$status', NOW(), " . 
    ($file_name ? "'$file_name'" : "NULL") . ", " . 
    ($file_path ? "'$file_path'" : "NULL") . ")";

    // Eksekusi query
    if (mysqli_query($conn, $query)) {
        // Jika berhasil, redirect ke halaman dashboard
        header('Location: dashboard.php');
        exit;
    } else {
        // Jika gagal, tampilkan pesan error dari database
        echo "ERROR: " . mysqli_error($conn);
    }
}
?>

<!-- HTML form sama seperti sebelumnya -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* style sama seperti sebelumnya */
        /* Informasi tambahan untuk file */
        .file-info {
            margin-top: 8px;
            font-size: 12px;
            color: #94a3b8;
        }
        /* Styling untuk informasi file */
        .file-info i {
            font-style: normal;
            display: inline-block;
            padding: 4px 8px;
            background: #0f172a;
            border-radius: 4px;
        }
        /* Container preview gambar */
        .preview-image {
            margin-top: 10px;
        }
        /* Styling preview gambar */
        .preview-image img {
            max-width: 150px;
            border-radius: 8px;
            border: 1px solid #334155;
        }
        /* Container preview file non-gambar */
        .preview-file {
            margin-top: 10px;
            padding: 8px 12px;
            background: #0f172a;
            border-radius: 8px;
            font-size: 12px;
        }
        /* Styling input file */
        input[type="file"] {
            padding: 10px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            color: #e2e8f0;
            width: 100%;
        }
        /* Styling grup form */
        .form-group {
            margin-bottom: 20px;
        }
        /* Styling label form */
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="page-layout">
        <!-- Sidebar navigasi -->
        <aside class="sidebar">
            <div class="brand">
                <span class="brand-icon">📚</span>
                <span>Perpustakaan</span>
            </div>
            <nav>
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="../auth/logout.php">🚪 Logout</a>
            </nav>
        </aside>

        <main class="main">
            <!-- Header halaman tambah buku -->
            <div class="page-header">
                <div>
                    <h1>Tambah Buku Baru</h1>
                    <p style="color: #64748b; margin-top: 8px;">Tambahkan koleksi buku baru ke perpustakaan</p>
                </div>
                <div class="user-tag">👋 Halo, Admin</div>
            </div>

            <!-- Container form -->
            <div class="form-container">
                <!-- Form dengan method POST dan enctype multipart/form-data untuk upload file -->
                <form method="POST" enctype="multipart/form-data">
                    <!-- Input judul buku (wajib) -->
                    <div class="form-group">
                        <label>Judul Buku *</label>
                        <input type="text" name="title" placeholder="Masukkan judul buku" required>
                    </div>

                    <!-- Textarea deskripsi buku -->
                    <div class="form-group">
                        <label>Pengarang / Deskripsi</label>
                        <textarea name="description" placeholder="Masukkan nama pengarang atau deskripsi singkat" rows="4"></textarea>
                    </div>

                    <!-- Input deadline (wajib) -->
                    <div class="form-group">
                        <label>Deadline *</label>
                       <input type="date" name="deadline" required 
                           min="<?= date('Y-m-d', strtotime('+0 day')) ?>">
                        <div class="file-info">
                            <i>📅 Tanggal deadline/batas waktu pengembalian buku</i>
                        </div>
                    </div>

                    <!-- Dropdown status peminjaman -->
                    <div class="form-group">
                        <label>Status Peminjaman</label>
                        <select name="status">
                            <option value="proses">Tersedia</option>
                            <option value="in_progress">Dipinjam</option>
                            <option value="done">Selesai</option>
                        </select>
                    </div>

                    <!-- Input upload file -->
                    <div class="form-group">
                        <label>Upload File (Foto / Dokumen)</label>
                        <input type="file" name="file_upload" id="file_upload" 
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">
                        <div class="file-info">
                            <i>📄 Format yang didukung: JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX (Max 2MB)</i>
                        </div>
                        <!-- Tempat preview file yang dipilih -->
                        <div class="preview-image" id="preview"></div>
                    </div>

                    <!-- Tombol submit dan kembali -->
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" name="simpan" class="btn btn-primary">📥 Simpan Buku</button>
                        <a href="dashboard.php" class="btn btn-secondary">↩️ Kembali</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Event ketika file dipilih pada input file
        document.getElementById('file_upload').onchange = function(evt) {
            const preview = document.getElementById('preview');
            preview.innerHTML = ''; // Kosongkan preview sebelumnya
            const file = evt.target.files[0];
            
            if(file) {
                // Validasi ukuran file maksimal 2MB
                if(file.size > 2 * 1024 * 1024) {
                    preview.innerHTML = '<div class="preview-file" style="color: #ef4444;">❌ File terlalu besar! Maksimal 2MB</div>';
                    this.value = ''; // Reset input file
                    return;
                }
                
                // Ambil informasi file
                const fileType = file.type;
                const fileName = file.name;
                const fileExt = fileName.split('.').pop().toLowerCase();
                const fileSize = (file.size / 1024).toFixed(2);
                
                // Jika file adalah gambar, tampilkan preview gambar
                if(fileType.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.maxWidth = '150px';
                        img.style.borderRadius = '8px';
                        img.style.marginTop = '10px';
                        img.style.border = '1px solid #334155';
                        preview.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                } 
                // Jika file bukan gambar, tampilkan info file
                else {
                    const fileInfo = document.createElement('div');
                    fileInfo.className = 'preview-file';
                    
                    // Tentukan icon berdasarkan ekstensi file
                    let icon = '📄';
                    let typeName = 'Dokumen';
                    if(fileExt === 'pdf') {
                        icon = '📕';
                        typeName = 'PDF';
                    } else if(fileExt === 'doc' || fileExt === 'docx') {
                        icon = '📘';
                        typeName = 'Word';
                    } else if(fileExt === 'xls' || fileExt === 'xlsx') {
                        icon = '📗';
                        typeName = 'Excel';
                    }
                    
                    // Tampilkan informasi file
                    fileInfo.innerHTML = `${icon} ${typeName}: ${fileName} (${fileSize} KB)`;
                    preview.appendChild(fileInfo);
                }
            }
        }
    </script>
</body>
</html>