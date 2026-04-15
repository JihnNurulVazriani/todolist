<?php
$conn = mysqli_connect("localhost", "root", "", "todolist_jihan");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>