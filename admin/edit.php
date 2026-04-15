<?php
// Menghubungkan ke database dari file konfigurasi
include '../config/koneksi.php';

// Mengambil parameter 'id' dari URL (GET request)
$id = $_GET['id'];
// Mengambil data task berdasarkan ID dari database
// Catatan: Query rentan SQL Injection karena $id tidak divalidasi/escape
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tasks WHERE id=$id"));

// Mengecek apakah tombol update telah ditekan
if(isset($_POST['update'])){
    // Mengambil data dari form
    $title = $_POST['title'];
    $description = $_POST['description'];
    $deadline = $_POST['deadline'];
    $status = $_POST['status'];

    // Query UPDATE untuk mengubah data task berdasarkan ID
    // Catatan: Query rentan SQL Injection
    mysqli_query($conn, "UPDATE tasks SET title='$title', description='$description', deadline='$deadline', status='$status' WHERE id=$id");
    
    // Setelah update berhasil, redirect ke halaman dashboard
    header("Location: dashboard.php");
    // Hentikan eksekusi script
    exit;
}
?>

<!-- Menghubungkan file CSS eksternal untuk styling -->
<link rel="stylesheet" href="../assets/style.css">

<!-- Container form untuk halaman edit buku -->
<div class="form-container">
    <!-- Judul halaman edit -->
    <h2>Edit Buku</h2>

    <!-- Form edit dengan method POST (tanpa enctype karena tidak upload file) -->
    <form method="POST">
        <!-- Input judul buku, value diisi dari data yang ada, required wajib diisi -->
        <!-- htmlspecialchars() digunakan untuk mencegah XSS -->
        <input type="text" name="title" value="<?= htmlspecialchars($data['title']) ?>" required>
        
        <!-- Textarea untuk deskripsi/pengarang -->
        <textarea name="description" placeholder="Pengarang / Deskripsi singkat"><?= htmlspecialchars($data['description']) ?></textarea>
        
        <!-- Label untuk field deadline -->
        <label for="deadline">Tanggal peminjaman</label>
        <!-- Input date untuk deadline, value diisi dari data yang ada -->
        <input type="date" id="deadline" name="deadline" value="<?= htmlspecialchars($data['deadline']) ?>">

        <!-- Label untuk dropdown status peminjaman -->
        <label for="status">Status Peminjaman</label>
        <!-- Dropdown untuk memilih status -->
        <select id="status" name="status">
            <!-- Option Proses, selected jika status saat ini adalah 'proses' -->
            <option value="proses" <?= $data['status'] === 'proses' ? 'selected' : '' ?>>Proses</option>
            <!-- Option Buku yang dipinjam, selected jika status saat ini adalah 'in_progress' -->
            <option value="in_progress" <?= $data['status'] === 'in_progress' ? 'selected' : '' ?>>Buku yang dipinjam</option>
            <!-- Option Buku yang dikembalikan, selected jika status saat ini adalah 'done' -->
            <option value="done" <?= $data['status'] === 'done' ? 'selected' : '' ?>>Buku yang di kembalikan</option>
        </select>

        <!-- Tombol submit untuk update data, name="update" digunakan untuk mengecek di PHP -->
        <button type="submit" name="update" class="btn btn-submit">Update Data</button>
        <!-- Tombol batal untuk kembali ke dashboard -->
        <a href="dashboard.php" class="btn btn-back">Batal</a>
    </form>
</div>