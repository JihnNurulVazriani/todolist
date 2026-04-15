<?php
// Memulai session untuk menyimpan data user yang login
session_start();
// Menghubungkan ke database dari file konfigurasi
include '../config/koneksi.php';

// Mengecek apakah form login telah disubmit (tombol login ditekan)
if(isset($_POST['login'])){
    // Mengambil username dari input form
    $username = $_POST['username'];
    // Mengambil password dan mengenkripsinya dengan MD5 (catatan: MD5 tidak aman untuk production)
    $password = md5($_POST['password']);

    // Query untuk mencari user dengan username dan password yang cocok
    $data = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    // Mengambil hasil query sebagai array asosiatif
    $user = mysqli_fetch_assoc($data);

    // Jika user ditemukan (login berhasil)
    if($user){
        // Menyimpan data user ke session
        $_SESSION['user'] = $user;

        // Redirect berdasarkan role user
        if($user['role'] == 'admin'){
            // Jika admin, arahkan ke dashboard admin
            header("Location: ../admin/dashboard.php");
        } else {
            // Jika bukan admin (user biasa), arahkan ke dashboard user
            header("Location: ../user/dashboard.php");
        }
        // Hentikan eksekusi script setelah redirect
        exit;
    } else {
        // Jika login gagal, set pesan error
        $error_message = 'Username atau password salah.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk</title>
    <!-- Menghubungkan file CSS eksternal -->
    <link rel="stylesheet" href="../assets/style.css">
</head>
<!-- Class login-body digunakan untuk styling khusus halaman login (dari style.css) -->
<body class="login-body">
    <!-- Kartu/login container -->
    <div class="login-card">
        <!-- Judul halaman login -->
        <h1>Masuk</h1>

        <!-- Menampilkan pesan error jika login gagal -->
        <?php if(!empty($error_message)): ?>
            <!-- htmlspecialchars() digunakan untuk mencegah XSS (Cross Site Scripting) -->
            <div class="login-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Form login dengan method POST -->
        <form method="POST" class="login-form">
            <!-- Label untuk field username -->
            <label for="username">Username</label>
            <!-- Input username, required berarti wajib diisi -->
            <input type="text" id="username" name="username" placeholder="Masukkan username" required>

            <!-- Label untuk field password -->
            <label for="password">Password</label>
            <!-- Input password dengan type password (tersembunyi), required wajib diisi -->
            <input type="password" id="password" name="password" placeholder="Masukkan password" required>

            <!-- Tombol submit login, name="login" digunakan untuk mengecek di PHP -->
            <button type="submit" name="login" class="btn-submit">Masuk</button>
        </form>

        <!-- Footer dengan link ke halaman registrasi -->
        <div class="login-footer">
            Belum punya akun? <a href="register.php">Daftar</a>
        </div>
    </div>
</body>
</html>