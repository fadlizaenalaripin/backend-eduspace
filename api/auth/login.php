<?php
// eduspace-backend-php/api/auth/login.php

// Header yang diperlukan
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Tangani permintaan preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Sertakan file database dan model User
require_once '../../config/database.php';
require_once '../../models/User.php';

// Sertakan file untuk JWT
require_once '../../config/jwt_config.php';
require_once '../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Buat instance database
$database = new Database();
$db = $database->connect();

// Buat instance objek User
$user = new User($db);

// Dapatkan data yang diposting
$data = json_decode(file_get_contents("php://input"));

// Pastikan data tidak kosong
if (empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(array('msg' => 'Mohon masukkan email dan password.'));
    exit();
}

// Atur email pengguna dari data yang diterima
$user->email = $data->email;

// Cari pengguna berdasarkan email
if (!$user->findByEmail()) {
    http_response_code(401);
    echo json_encode(array('msg' => 'Kredensial tidak valid.'));
    exit();
}

// Verifikasi password
if (!password_verify($data->password, $user->password)) {
    http_response_code(401);
    echo json_encode(array('msg' => 'Kredensial tidak valid.'));
    exit();
}

// Jika login berhasil, buat JWT
$payload = array(
    "iss" => "http://eduspace-backend-php.test",
    "aud" => "http://eduspace-frontend.test",
    "iat" => time(),
    // --- PERBAIKAN DI SINI: Perpanjang masa berlaku token menjadi 24 jam (24 * 60 * 60) ---
    "exp" => time() + (24 * 60 * 60), // Expiration time (berlaku 24 jam)
    // --- AKHIR PERBAIKAN ---
    "data" => array(
        "id" => $user->id,
        "username" => $user->username,
        "email" => $user->email,
        "fullName" => $user->fullName,
        "profilePicture" => $user->profilePicture
    )
);

$secret_key = JWT_SECRET_KEY;

try {
    $jwt = JWT::encode($payload, $secret_key, 'HS256');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("msg" => "Gagal membuat token JWT.", "error" => $e->getMessage()));
    exit();
}

http_response_code(200);
echo json_encode(array(
    "msg" => "Login berhasil!",
    "token" => $jwt,
    "user" => array(
        "id" => $user->id,
        "username" => $user->username,
        "email" => $user->email,
        "fullName" => $user->fullName,
        "profilePicture" => $user->profilePicture
    )
));

$database->close();
?>
