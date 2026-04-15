<?php
include '../config/koneksi.php';

$id = $_POST['id'];

if(isset($_POST['borrow'])){
    $status = 'in_progress';
} elseif(isset($_POST['return'])){
    $status = 'done';
} elseif(isset($_POST['status'])){
    $status = $_POST['status'];
} else {
    $status = 'proses';
}

mysqli_query($conn, "UPDATE tasks SET status='$status' WHERE id='$id'");

header("Location: dashboard.php");
exit;