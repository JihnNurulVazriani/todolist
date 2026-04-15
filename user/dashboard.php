<?php
// Memulai session untuk mengakses data user yang login
session_start();
// Menghubungkan ke database dari file konfigurasi
include '../config/koneksi.php';

// Validasi: hanya user dengan role 'user' yang boleh mengakses halaman ini
if ($_SESSION['user']['role'] != 'user') {
    // Jika bukan user, redirect ke halaman login
    header('Location: ../auth/login.php');
    exit;
}

// Memetakan status database ke label yang ditampilkan di UI (Indonesia)
$statusMap = [
    'proses' => 'proses',
    'in_progress' => 'buku yang di pinjam',
    'done' => 'buku yang sudah di kembalikan',
];

// Query semua data tasks dari database, diurutkan dari ID terbaru (descending)
$tasksResult = mysqli_query($conn, "SELECT * FROM tasks ORDER BY id DESC");
// Inisialisasi array untuk mengelompokkan task berdasarkan status
$tasksByStatus = [
    'proses' => [],
    'in_progress' => [],
    'done' => [],
];

// Looping setiap task hasil query
while ($task = mysqli_fetch_assoc($tasksResult)) {
    // Jika status kosong (''), dianggap sebagai 'in_progress' (buku yang dipinjam)
    if ($task['status'] === '') {
        $tasksByStatus['in_progress'][] = $task;
    } 
    // Jika status 'done', masukkan ke kelompok 'done'
    elseif ($task['status'] === 'done') {
        $tasksByStatus['done'][] = $task;
    } 
    // Selain itu (misal 'proses'), masukkan ke kelompok 'proses'
    else {
        $tasksByStatus['proses'][] = $task;
    }
}

// Menghitung jumlah task per status untuk ditampilkan di ringkasan
$counts = [
    'proses' => count($tasksByStatus['proses']),
    'in_progress' => count($tasksByStatus['in_progress']),
    'done' => count($tasksByStatus['done']),
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Perpustakaan</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* CSS untuk kartu tugas */
        .task-card {
            position: relative;
            background: #1e293b;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.2s ease;
            border: 1px solid #334155;
        }

        /* Efek hover pada kartu tugas */
        .task-card:hover {
            transform: translateY(-2px);
            border-color: #0ea5e9;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        /* Judul tugas */
        .task-card h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: white;
        }

        /* Deskripsi tugas */
        .task-card p {
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        /* Footer kartu (deadline & tombol) */
        .task-card footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #334155;
            padding-top: 10px;
        }

        /* Container tombol file */
        .file-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Styling dasar tombol file */
        .file-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        /* Tombol download */
        .file-download {
            background: #0f172a;
            color: #0ea5e9;
            border: 1px solid #334155;
        }

        .file-download:hover {
            background: #0ea5e9;
            color: white;
        }

        /* Tombol lihat file */
        .file-view {
            background: #0f172a;
            color: #22c55e;
            border: 1px solid #334155;
        }

        .file-view:hover {
            background: #22c55e;
            color: white;
        }

        /* Jika tidak ada file */
        .no-file {
            color: #475569;
            font-size: 11px;
            font-style: italic;
        }

        /* Modal untuk menampilkan file (gambar/PDF) */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
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
            max-height: 85vh;
            display: block;
            margin: auto;
        }

        /* Iframe PDF di dalam modal */
        .modal-content iframe {
            width: 85vw;
            height: 85vh;
            border: none;
        }

        /* Tombol close modal (X) */
        .close-modal {
            position: absolute;
            top: 15px;
            right: 25px;
            color: #fff;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            z-index: 1001;
        }

        .close-modal:hover {
            color: #0ea5e9;
        }

        /* Kolom kosong (tidak ada task) */
        .empty-column {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
            font-size: 13px;
            background: #0f172a;
            border-radius: 12px;
        }

        /* Baris ringkasan statistik (3 kolom) */
        .summary-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Kartu ringkasan */
        .summary-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid #334155;
        }

        /* Label ringkasan */
        .summary-label {
            display: block;
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        /* Angka ringkasan */
        .summary-card strong {
            font-size: 32px;
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Layout board kanban (3 kolom) */
        .board {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        /* Setiap kolom board */
        .board-column {
            background: #0f172a;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #1e293b;
        }

        /* Header kolom */
        .column-header {
            background: #1e293b;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
        }

        .column-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        /* Badge jumlah task di kolom */
        .column-count {
            background: #0ea5e9;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Area isi kolom (scrollable) */
        .column-body {
            padding: 16px;
            max-height: calc(100vh - 250px);
            overflow-y: auto;
        }

        /* Scrollbar kustom */
        .column-body::-webkit-scrollbar {
            width: 6px;
        }

        .column-body::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 3px;
        }

        .column-body::-webkit-scrollbar-thumb {
            background: #0ea5e9;
            border-radius: 3px;
        }

        /* Responsive: untuk layar <= 900px, semua kolom jadi 1 tumpuk */
        @media (max-width: 900px) {
            .board {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .summary-row {
                grid-template-columns: 1fr;
            }
            .modal-content iframe {
                width: 95vw;
                height: 70vh;
            }
        }
    </style>
</head>
<body>
    <div class="page-layout dashboard-page">
        <!-- Sidebar navigasi -->
        <aside class="sidebar">
            <div class="brand">
                <span class="brand-icon">📚</span>
                <span>Perpustakaan</span>
            </div>
            <nav>
                <a href="#" class="active">Dashboard</a>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </aside>

        <main class="main">
            <!-- Header halaman -->
            <div class="page-header">
                <div>
                    <p class="page-label">Perpustakaan</p>
                    <h1>Daftar Peminjaman</h1>
                    <p class="page-description">Lihat status peminjaman buku siswa dalam tiga kolom Open, In Progress, dan Done.</p>
                </div>
                <!-- Menampilkan nama user yang login -->
                <div class="user-tag">Halo, <?= htmlspecialchars($_SESSION['user']['username']) ?></div>
            </div>

            <!-- Ringkasan jumlah task per status -->
            <div class="summary-row">
                <?php foreach ($statusMap as $status => $label): ?>
                    <article class="summary-card">
                        <span class="summary-label"><?= $label ?></span>
                        <strong><?= $counts[$status] ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Board kanban: 3 kolom -->
            <section class="board">
                <?php foreach ($statusMap as $status => $label): ?>
                    <div class="board-column">
                        <div class="column-header">
                            <h2><?= $label ?></h2>
                            <span class="column-count"><?= $counts[$status] ?></span>
                        </div>
                        <div class="column-body">
                            <!-- Jika tidak ada task di kolom ini -->
                            <?php if (count($tasksByStatus[$status]) === 0): ?>
                                <div class="empty-column">📭 Belum ada buku di sini.</div>
                            <?php endif; ?>

                            <!-- Looping task di kolom ini -->
                            <?php foreach ($tasksByStatus[$status] as $task): 
                                // Mendapatkan ekstensi file (untuk menentukan cara tampil di modal)
                                $file_extension = '';
                                if (!empty($task['file'])) {
                                    $file_extension = strtolower(pathinfo($task['file_name'] ?? '', PATHINFO_EXTENSION));
                                }
                            ?>
                                <article class="task-card">
                                    <div>
                                        <!-- Judul task, di-escape untuk keamanan -->
                                        <h3><?= htmlspecialchars($task['title']) ?></h3>
                                        <!-- Deskripsi task, newline jadi <br> -->
                                        <p><?= nl2br(htmlspecialchars($task['description'])) ?></p>
                                    </div>
                                    <footer>
                                        <!-- Deadline -->
                                        <span>📅 Deadline: <?= htmlspecialchars($task['deadline']) ?></span>
                                        <div class="file-buttons">
                                            <!-- Jika ada file yang diupload -->
                                            <?php if (!empty($task['file_name']) && !empty($task['file'])): ?>
                                                <!-- Tombol download -->
                                                <a href="../<?= htmlspecialchars($task['file']) ?>" class="file-btn file-download" download="<?= htmlspecialchars($task['file_name']) ?>">
                                                    📎 Download
                                                </a>
                                                <!-- Tombol lihat (hanya untuk gambar dan PDF) -->
                                                <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'pdf'])): ?>
                                                    <button onclick="viewFile('../<?= htmlspecialchars($task['file']) ?>', '<?= $file_extension ?>')" class="file-btn file-view">
                                                        👁️ Lihat
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- Jika tidak ada file -->
                                                <span class="no-file">📄 Tidak ada file</span>
                                            <?php endif; ?>
                                        </div>
                                    </footer>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        </main>
    </div>

    <!-- Modal untuk melihat file (gambar/PDF) -->
    <div id="fileModal" class="modal">
        <span class="close-modal">&times;</span>
        <div class="modal-content" id="modalContent">
            <!-- Konten diisi JavaScript -->
        </div>
    </div>

    <script>
        // Mengambil elemen modal
        const modal = document.getElementById('fileModal');
        const modalContent = document.getElementById('modalContent');
        const closeBtn = document.querySelector('.close-modal');

        // Fungsi untuk melihat file di modal
        function viewFile(filePath, fileType) {
            modal.style.display = 'flex';
            modalContent.innerHTML = '';
            
            // Jika file PDF, tampilkan dengan iframe
            if (fileType === 'pdf') {
                const iframe = document.createElement('iframe');
                iframe.src = filePath;
                iframe.style.width = '85vw';
                iframe.style.height = '85vh';
                iframe.style.border = 'none';
                modalContent.appendChild(iframe);
            } 
            // Jika file gambar, tampilkan dengan tag img
            else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileType)) {
                const img = document.createElement('img');
                img.src = filePath;
                img.style.maxWidth = '90vw';
                img.style.maxHeight = '85vh';
                modalContent.appendChild(img);
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