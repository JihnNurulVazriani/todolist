<?php
include '../config/koneksi.php';

if(isset($_POST['register'])){
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    $role = $_POST['role'];
    
    // CEK APAKAH USERNAME SUDAH ADA
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    
    if(mysqli_num_rows($cek) > 0){
        // Kalau sudah ada, kasih pesan error
        $error = "Username sudah terdaftar! Silakan gunakan username lain.";
    } else {
        // Kalau belum ada, lanjutkan registrasi
        $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
        
        if(mysqli_query($conn, $query)){
            header("Location: login.php");
            exit;
        } else {
            $error = "Registrasi gagal: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h1>Daftar</h1>
        
        <!-- TAMPILKAN PESAN ERROR JIKA ADA -->
        <?php if(isset($error)): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Masukkan username" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password" required>

            <label for="role">Pilih Role</label>
            <select id="role" name="role" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>

            <button type="submit" name="register" class="btn-submit">Daftar</button>
        </form>

        <div class="login-footer">
            Sudah punya akun? <a href="login.php">Masuk</a>
        </div>
    </div>
</body>
</html>