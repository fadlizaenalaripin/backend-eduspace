<?php
// eduspace-backend-php/api/user/profile.php

// Header yang diperlukan untuk CORS
header('Access-Control-Allow-Origin: *'); // Izinkan akses dari domain manapun (untuk pengembangan)
header('Content-Type: application/json'); // Tentukan tipe konten sebagai JSON
// --- PERBAIKAN DI SINI: Tambahkan PUT ke daftar metode yang diizinkan ---
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS'); // Hanya GET, PUT, dan OPTIONS untuk endpoint profil
// --- AKHIR PERBAIKAN ---
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Tangani permintaan preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); // Kirim status OK (200) untuk preflight
    exit(); // Hentikan eksekusi skrip
}

// Sertakan file database, model, dan JWT
require_once '../../config/database.php';
require_once '../../models/User.php'; // Pastikan Anda memiliki model User
require_once '../../config/jwt_config.php';
require_once '../../vendor/autoload.php'; // Pastikan composer autoload ada

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Buat instance database
$database = new Database();
$db = $database->connect();

// Buat instance objek User
$user = new User($db);

// Dapatkan header Authorization
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

$user_id_from_token = null;

// Verifikasi token JWT
if (empty($auth_header)) {
    http_response_code(401); // Unauthorized
    echo json_encode(array("msg" => "Akses ditolak. Token tidak ditemukan."));
    exit();
}

$token = null;
if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    $token = $matches[1];
}

if (!$token) {
    http_response_code(401); // Unauthorized
    echo json_encode(array("msg" => "Akses ditolak. Format token tidak valid."));
    exit();
}

try {
    $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, 'HS256'));
    $user_id_from_token = $decoded->data->id;
} catch (Exception $e) {
    http_response_code(401); // Unauthorized
    echo json_encode(array("msg" => "Akses ditolak. Token tidak valid atau kedaluwarsa.", "error" => $e->getMessage()));
    exit();
}

// Set ID pengguna dari token
$user->id = $user_id_from_token;

// Tangani permintaan GET (untuk membaca profil)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($user->findById()) {
        $user_arr = array(
            "id" => $user->id,
            "username" => $user->username,
            "email" => $user->email,
            "fullName" => $user->fullName,
            "profilePicture" => $user->profilePicture,
            "bio" => $user->bio,
            "created_at" => $user->created_at,
            "updated_at" => $user->updated_at
        );
        http_response_code(200);
        echo json_encode(array("user" => $user_arr));
    } else {
        http_response_code(404);
        echo json_encode(array("msg" => "Profil pengguna tidak ditemukan."));
    }
} 
// Tangani permintaan PUT (untuk memperbarui profil)
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Dapatkan data yang dikirimkan melalui PUT request
    // Untuk multipart/form-data, PHP secara otomatis mengisi $_POST dan $_FILES
    // Namun, untuk PUT dengan multipart/form-data, kita perlu mengaksesnya secara berbeda
    // Jika Anda mengirim FormData, PHP akan mengisi $_POST dan $_FILES secara otomatis
    // untuk PUT, asalkan Content-Type adalah 'multipart/form-data'.
    // Jika Anda mengirim JSON, Anda akan menggunakan file_get_contents("php://input")

    // Ambil data dari $_POST (jika FormData)
    $user->username = isset($_POST['username']) ? $_POST['username'] : $user->username;
    $user->email = isset($_POST['email']) ? $_POST['email'] : $user->email;
    $user->fullName = isset($_POST['fullName']) ? $_POST['fullName'] : $user->fullName;
    $user->dateOfBirth = isset($_POST['dateOfBirth']) ? $_POST['dateOfBirth'] : null; // Sesuaikan dengan kolom di DB
    $user->phoneNumber = isset($_POST['phoneNumber']) ? $_POST['phoneNumber'] : null; // Sesuaikan dengan kolom di DB
    $user->bio = isset($_POST['bio']) ? $_POST['bio'] : null; // Pastikan ini juga diambil jika ada di form

    // Tangani upload gambar profil
    if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/'; // Pastikan direktori ini ada dan writable
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid() . '_' . basename($_FILES['profilePicture']['name']);
        $targetFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profilePicture']['tmp_name'], $targetFilePath)) {
            $user->profilePicture = '/uploads/' . $fileName; // Simpan path relatif
        } else {
            http_response_code(500);
            echo json_encode(array("msg" => "Gagal mengunggah gambar profil."));
            exit();
        }
    } else {
        // Jika tidak ada file baru diupload, pertahankan gambar profil yang sudah ada
        // Anda perlu mengambil data user saat ini terlebih dahulu untuk mendapatkan profilePicture yang lama
        if ($user->findById()) { // Panggil findById lagi untuk mendapatkan data lama
            // profilePicture sudah diisi dari database oleh findById()
        }
    }

    // Panggil metode update di model User
    if ($user->update()) {
        http_response_code(200);
        echo json_encode(array(
            "msg" => "Profil berhasil diperbarui.",
            "user" => array(
                "id" => $user->id,
                "username" => $user->username,
                "email" => $user->email,
                "fullName" => $user->fullName,
                "profilePicture" => $user->profilePicture,
                "bio" => $user->bio,
                "created_at" => $user->created_at,
                "updated_at" => $user->updated_at
            )
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("msg" => "Gagal memperbarui profil."));
    }
}
// Jika metode request tidak didukung
else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("msg" => "Metode request tidak diizinkan."));
}

// Tutup koneksi database (opsional)
$database->close();
?>
