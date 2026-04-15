<?php
// Memulai session untuk mengakses data user yang login
session_start();
// Menghubungkan ke database
include '../config/koneksi.php';

// Validasi: hanya user dengan role 'admin' yang boleh mengakses halaman ini
if ($_SESSION['user']['role'] != 'admin') {
    // Jika bukan admin, redirect ke halaman login
    header("Location: ../auth/login.php");
    exit;
}

// Mengambil semua data tasks dari database, diurutkan dari ID terbaru
$data = mysqli_query($conn, "SELECT * FROM tasks ORDER BY id DESC");

// Menghitung total seluruh buku (tasks)
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tasks"));
// Menghitung buku dengan status 'proses' atau 'available' (buku tersedia)
$open = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as open FROM tasks WHERE status IN ('proses','available')"));
// Menghitung buku dengan status 'in_progress' (sedang dipinjam)
$inProgress = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as in_progress FROM tasks WHERE status = 'in_progress'"));
// Menghitung buku dengan status 'done' (selesai/dikembalikan)
$done = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as done FROM tasks WHERE status = 'done'"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Perpustakaan</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* ============================================
           TABLE CONTAINER & RESPONSIVE
           ============================================ */
        /* Container tabel dengan overflow horizontal untuk responsive */
        .table-container {
            overflow-x: auto;
        }

        /* Lebar maksimal kolom file */
        .file-cell {
            max-width: 200px;
        }

        /* Container tombol aksi (Edit & Hapus) */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* ============================================
           STATUS BADGES
           ============================================ */
        /* Styling dasar badge status */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Badge untuk status tersedia (available/proses) */
        .status-available {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        /* Badge untuk status dipinjam (in_progress) */
        .status-borrowed {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        /* Badge untuk status selesai (done) */
        .status-done {
            background: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        /* ============================================
           MODAL / POPUP
           ============================================ */
        /* Modal untuk melihat file (gambar/PDF) */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
        }

        /* Konten modal */
        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
        }

        /* Gambar di dalam modal */
        .modal-content img {
            width: auto;
            max-width: 100%;
            max-height: 80vh;
            display: block;
            margin: auto;
        }

        /* Iframe PDF di dalam modal */
        .modal-content iframe {
            width: 800px;
            height: 600px;
            border: none;
        }

        /* Tombol close modal (X) */
        .close-modal {
            position: absolute;
            top: 15px;
            right: 25px;
            color: #000000;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            z-index: 1001;
        }

        .close-modal:hover {
            color: #0ea5e9;
        }

        /* ============================================
           FILE VIEW BUTTON & LINK
           ============================================ */
        /* Tombol untuk melihat file */
        .file-view-btn {
            background: #0ea5e9;
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 11px;
            margin-left: 8px;
            cursor: pointer;
            display: inline-block;
            border: none;
        }

        .file-view-btn:hover {
            background: #0284c7;
        }

        /* Link download file */
        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #0f172a;
            padding: 4px 10px;
            border-radius: 20px;
            color: #0ea5e9;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.2s ease;
        }

        .file-link:hover {
            background: #0ea5e9;
            color: white;
        }

        /* Wrapper untuk file (link download + tombol lihat) */
        .file-wrapper {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        /* ============================================
           PERBAIKAN TAMBAHAN
           ============================================ */
        /* Container tabel responsif */
        .table-responsive {
            overflow-x: auto;
            margin-top: 24px;
        }

        /* Styling tabel */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Styling sel tabel header dan data */
        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #141414;
        }

        /* Header tabel */
        th {
            background: #e0e4e9;
            font-weight: 600;
            color: #1e293b;
        }

        /* Efek hover pada baris tabel */
        tr:hover {
            background: #071420;
        }

        /* Styling dasar tombol */
        .btn {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
            transition: all 0.2s;
        }

        /* Tombol primary (biru) */
        .btn-primary {
            background: #0ea5e9;
            color: white;
        }

        .btn-primary:hover {
            background: #0284c7;
        }

        /* Tombol edit (oranye) */
        .btn-edit {
            background: #f59e0b;
            color: white;
        }

        .btn-edit:hover {
            background: #d97706;
        }

        /* Tombol delete (merah) */
        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
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
                <a href="#" class="active">📊 Dashboard</a>
                <a href="../auth/logout.php">🚪 Logout</a>
            </nav>
        </aside>

        <main class="main">
            <!-- Header halaman dashboard -->
            <div class="page-header">
                <div>
                    <h1>Dashboard Perpustakaan</h1>
                    <p style="color: #64748b; margin-top: 8px;">daftar buku perpustakaan</p>
                </div>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <!-- Tombol tambah buku -->
                    <a href="tambah.php" class="btn btn-primary">+ Tambah Buku</a>
                    <!-- Menampilkan nama admin yang login -->
                    <div class="user-tag">👋 Halo, <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Admin') ?></div>
                </div>
            </div>

            <!-- Kartu ringkasan statistik (summary cards) -->
            <div class="cards dashboard-summary">
                <div class="card">
                    <h3>📚 Total Buku</h3>
                    <p><?= $total['total'] ?></p>
                </div>
                <div class="card card-green">
                    <h3>✅ Buku Siap Dipinjam</h3>
                    <p><?= $open['open'] ?></p>
                </div>
                <div class="card card-blue">
                    <h3>📖 Buku Dipinjam</h3>
                    <p><?= $inProgress['in_progress'] ?></p>
                </div>
                <div class="card card-gray">
                    <h3>✔️ Buku Selesai</h3>
                    <p><?= $done['done'] ?></p>
                </div>
            </div>

            <!-- Tabel data buku -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul Buku</th>
                            <th>Pengarang / Deskripsi</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>File</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        // Looping setiap data buku
                        while ($d = mysqli_fetch_assoc($data)) {
                            // Menentukan label dan class status berdasarkan nilai status
                            $statusLabel = 'Tersedia';
                            $statusClass = 'status-available';
                            
                            if ($d['status'] === 'in_progress') {
                                $statusLabel = 'Dipinjam';
                                $statusClass = 'status-borrowed';
                            } elseif ($d['status'] === 'done') {
                                $statusLabel = 'Selesai';
                                $statusClass = 'status-done';
                            } elseif ($d['status'] === 'proses') {
                                $statusLabel = 'Tersedia';
                                $statusClass = 'status-available';
                            }
                            
                            // Mendapatkan ekstensi file untuk menentukan cara tampil di modal
                            $file_extension = '';
                            $file_name_display = '';
                            
                            if (!empty($d['file_name'])) {
                                $file_extension = strtolower(pathinfo($d['file_name'], PATHINFO_EXTENSION));
                                // Memotong nama file jika terlalu panjang (>20 karakter)
                                $file_name_display = strlen($d['file_name']) > 20 ? substr($d['file_name'], 0, 17) . '...' : $d['file_name'];
                            }
                        ?>
                        <tr>
                            <!-- Nomor urut -->
                            <td><?= $no++ ?></td>
                            <!-- Judul buku (dengan bold) -->
                            <td><strong><?= htmlspecialchars($d['title']) ?></strong></td>
                            <!-- Deskripsi, dipotong maksimal 50 karakter -->
                            <td><?= htmlspecialchars(substr($d['description'], 0, 50)) ?><?= strlen($d['description']) > 50 ? '...' : '' ?></td>
                            <!-- Deadline -->
                            <td><?= htmlspecialchars($d['deadline']) ?></td>
                            <!-- Badge status -->
                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <!-- Kolom file (download & lihat) -->
                            <td class="file-cell">
                                <?php if (!empty($d['file_name']) && !empty($d['file'])): ?>
                                    <div class="file-wrapper">
                                        <!-- Link download file -->
                                        <a href="../<?= htmlspecialchars($d['file']) ?>" class="file-link" download="<?= htmlspecialchars($d['file_name']) ?>">
                                            📎 <?= htmlspecialchars($file_name_display) ?>
                                        </a>
                                        <!-- Tombol lihat file (hanya untuk gambar dan PDF) -->
                                        <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'pdf'])): ?>
                                            <button onclick="viewFile('../<?= htmlspecialchars($d['file']) ?>', '<?= $file_extension ?>')" class="file-view-btn">
                                                👁️ Lihat
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Jika tidak ada file -->
                                    <span style="color: #475569; font-size: 12px;">- Tidak ada file -</span>
                                <?php endif; ?>
                            </td>
                            <!-- Tombol aksi (Edit & Hapus) -->
                            <td>
                                <div class="action-buttons">
                                    <a href="edit.php?id=<?= $d['id'] ?>" class="btn btn-edit">✏️ Edit</a>
                                    <a href="hapus.php?id=<?= $d['id'] ?>" class="btn btn-delete" onclick="return confirm('Yakin ingin menghapus buku ini?')">🗑️ Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                        
                        <!-- Jika tidak ada data sama sekali -->
                        <?php if (mysqli_num_rows($data) == 0): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 60px 20px;">
                                    <div style="font-size: 48px; margin-bottom: 16px;">📚</div>
                                    <div style="color: #64748b;">Belum ada data buku</div>
                                    <a href="tambah.php" class="btn btn-primary" style="margin-top: 16px; display: inline-block;">+ Tambah Buku Pertama</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal Popup untuk melihat file (gambar/PDF) -->
    <div id="fileModal" class="modal">
        <span class="close-modal">&times;</span>
        <div class="modal-content" id="modalContent">
            <!-- Konten akan diisi oleh JavaScript -->
        </div>
    </div>

    <script>
        // Mengambil elemen modal
        const modal = document.getElementById('fileModal');
        const modalContent = document.getElementById('modalContent');
        const closeBtn = document.querySelector('.close-modal');

        // Fungsi untuk menampilkan file di modal
        function viewFile(filePath, fileType) {
            modal.style.display = 'flex';
            modalContent.innerHTML = '';
            
            // Jika file PDF, tampilkan dengan iframe
            if (fileType === 'pdf') {
                const iframe = document.createElement('iframe');
                iframe.src = filePath;
                iframe.style.width = '90vw';
                iframe.style.height = '80vh';
                iframe.style.border = 'none';
                modalContent.appendChild(iframe);
            } 
            // Jika file gambar, tampilkan dengan tag img
            else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileType)) {
                const img = document.createElement('img');
                img.src = filePath;
                img.style.maxWidth = '90vw';
                img.style.maxHeight = '80vh';
                modalContent.appendChild(img);
            } 
            // Jika format tidak didukung untuk preview
            else {
                modalContent.innerHTML = '<div style="color: white; padding: 20px; text-align: center;">Format file tidak dapat ditampilkan. <a href="' + filePath + '" download style="color: #0ea5e9;">Download file</a></div>';
            }
        }

        // Event klik tombol close (X)
        closeBtn.onclick = function() {
            modal.style.display = 'none';
            modalContent.innerHTML = '';
        }

        // Event klik di luar modal -> tutup modal
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
                modalContent.innerHTML = '';
            }
        }

        // Event tombol ESC -> tutup modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.style.display === 'flex') {
                modal.style.display = 'none';
                modalContent.innerHTML = '';
            }
        });
    </script>
</body>
</html>