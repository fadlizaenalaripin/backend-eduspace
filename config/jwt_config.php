<?php
// eduspace-backend-php/config/jwt_config.php

// Kunci rahasia untuk JWT Anda. Ini harus sangat kuat dan dirahasiakan!
// PASTIKAN KUNCI INI SAMA PERSIS DI SEMUA FILE YANG MENGGUNAKAN JWT (login.php, profile.php, progress/index.php)
define('JWT_SECRET_KEY', 'secret'); // Kunci rahasia sederhana untuk pengujian

// Waktu berlaku token (dalam detik)
// Ini adalah nilai default, tetapi bisa ditimpa di login.php
define('JWT_EXPIRATION_TIME', 3600); // 1 jam
?>
