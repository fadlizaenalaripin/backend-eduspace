<?php
// eduspace-backend-php/test_db_connection.php

// Sertakan file konfigurasi database
require_once 'config/database.php';

// Buat instance kelas Database
$database = new Database();

// Coba hubungkan ke database
$conn = $database->connect();

// Jika koneksi berhasil, Anda akan melihat pesan "Database connected successfully!" dari database.php
// Jika tidak, Anda akan melihat pesan error.

// Anda bisa menambahkan logika lain di sini untuk menguji query jika diperlukan
// Misalnya:
// if ($conn) {
//     echo "Koneksi berhasil. Siap untuk query.";
// } else {
//     echo "Koneksi gagal.";
// }
?>
