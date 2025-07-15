<?php
// eduspace-backend-php/api/auth/register.php

// --- DEBUG LOGGING ---
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php_error.log');
error_log("--- register.php START ---");

// --- CORS & Headers ---
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- LOAD DEPENDENCIES ---
require_once '../../config/database.php';
require_once '../../models/User.php';
error_log("register.php: Database and User model loaded.");

// --- DATABASE CONNECT ---
$database = new Database();
$db = $database->connect();

// --- CEK KONEKSI GAGAL ---
if ($db === null) {
    http_response_code(500);
    echo json_encode(['msg' => 'Gagal terhubung ke database. Cek php_error.log']);
    error_log("register.php: ❌ Database connection FAILED.");
    error_log("register.php: ⚠ Coba tes manual: mysql -u root dan pastikan database 'eduspace_db' ada.");
    exit();
}
error_log("register.php: ✅ Database connected.");

// --- BUAT USER OBJECT ---
$user = new User($db);

// --- GET INPUT ---
$input_data = file_get_contents("php://input");
error_log("register.php: Raw input data: " . $input_data);
$data = json_decode($input_data);

// --- VALIDASI JSON ---
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['msg' => 'Invalid JSON: ' . json_last_error_msg()]);
    error_log("register.php: ❌ JSON decode error: " . json_last_error_msg());
    exit();
}

// --- VALIDASI FIELD WAJIB ---
if (empty($data->username) || empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(['msg' => 'Mohon lengkapi semua kolom (username, email, password).']);
    error_log("register.php: ❌ Data tidak lengkap.");
    exit();
}

// --- SET USER PROPERTIES ---
$user->username = $data->username;
$user->email = $data->email;
$user->password = $data->password;
$user->fullName = isset($data->fullName) ? $data->fullName : $data->username;
$user->profilePicture = isset($data->profilePicture) ? $data->profilePicture : '';
error_log("register.php: User data disiapkan.");

// --- CEK EMAIL SUDAH ADA ---
if ($user->findByEmail()) {
    http_response_code(400);
    echo json_encode(['msg' => 'Email sudah terdaftar.']);
    error_log("register.php: ❌ Email sudah digunakan.");
    exit();
}

// --- CEK USERNAME SUDAH ADA ---
if ($user->findByUsername()) {
    http_response_code(400);
    echo json_encode(['msg' => 'Username sudah digunakan.']);
    error_log("register.php: ❌ Username sudah digunakan.");
    exit();
}

// --- VALIDASI PASSWORD ---
if (strlen($data->password) < 6) {
    http_response_code(400);
    echo json_encode(['msg' => 'Password minimal 6 karakter.']);
    error_log("register.php: ❌ Password terlalu pendek.");
    exit();
}

// --- REGISTER USER ---
if ($user->register()) {
    http_response_code(201);
    echo json_encode(['msg' => 'Pendaftaran berhasil! Silakan login.']);
    error_log("register.php: ✅ Pendaftaran berhasil.");
} else {
    http_response_code(500);
    echo json_encode(['msg' => 'Pendaftaran gagal.']);
    error_log("register.php: ❌ Pendaftaran gagal.");
}

// --- CLOSE DATABASE ---
$database->close();
error_log("--- register.php END ---");
?>
