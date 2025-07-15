<?php
// eduspace-backend-php/api/progress/index.php

// Header yang diperlukan untuk CORS
header('Access-Control-Allow-Origin: *'); // Izinkan akses dari domain manapun (untuk pengembangan)
header('Content-Type: application/json'); // Tentukan tipe konten sebagai JSON
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); // Izinkan metode HTTP yang relevan, TERMASUK OPTIONS
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With'); // Izinkan header yang relevan

// Tangani permintaan preflight OPTIONS
// Browser akan mengirim permintaan OPTIONS sebelum permintaan sebenarnya (GET, POST, PUT, DELETE)
// untuk memeriksa apakah permintaan lintas-origin diizinkan.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); // Kirim status OK (200) untuk preflight
    exit(); // Hentikan eksekusi skrip setelah mengirim header OPTIONS
}

// Sertakan file database, model, dan JWT
require_once '../../config/database.php';
require_once '../../models/UserProgress.php';
require_once '../../config/jwt_config.php';
require_once '../../vendor/autoload.php'; // Pastikan composer autoload ada jika Anda menggunakan JWT

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Buat instance database
$database = new Database();
$db = $database->connect();

// Buat instance objek UserProgress
$progress = new UserProgress($db);

// Dapatkan metode HTTP
$method = $_SERVER['REQUEST_METHOD'];

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
    // Pastikan JWT_SECRET_KEY didefinisikan di config/jwt_config.php
    $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, 'HS256'));
    $user_id_from_token = $decoded->data->id; // Pastikan ini mengambil ID yang benar dari payload JWT
} catch (Exception $e) {
    http_response_code(401); // Unauthorized
    echo json_encode(array("msg" => "Akses ditolak. Token tidak valid atau kedaluwarsa.", "error" => $e->getMessage()));
    exit();
}

// Proses permintaan berdasarkan metode HTTP
switch ($method) {
    case 'POST': // Membuat progres baru
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->course_id) || empty($data->lesson_id) || empty($data->status)) {
            http_response_code(400);
            echo json_encode(array('msg' => 'Mohon lengkapi course_id, lesson_id, dan status.'));
            exit();
        }

        $progress->user_id = $user_id_from_token; // Ambil user_id dari token
        $progress->course_id = $data->course_id;
        $progress->lesson_id = $data->lesson_id;
        $progress->status = $data->status;
        $progress->quizPassed = isset($data->quizPassed) ? $data->quizPassed : false;

        if ($progress->create()) {
            http_response_code(201); // Created
            echo json_encode(array('msg' => 'Progres berhasil dicatat.', 'id' => $progress->id));
        } else {
            // Jika create() mengembalikan false karena data sudah ada (INSERT IGNORE)
            http_response_code(200); // OK
            echo json_encode(array('msg' => 'Progres sudah ada untuk pelajaran ini.'));
        }
        break;

    case 'GET': // Mengambil progres
        // Jika ada parameter course_id dan lesson_id, ambil progres tunggal
        if (isset($_GET['course_id']) && isset($_GET['lesson_id'])) {
            $progress->user_id = $user_id_from_token;
            $progress->course_id = $_GET['course_id'];
            $progress->lesson_id = $_GET['lesson_id'];

            if ($progress->readSingle()) {
                http_response_code(200);
                echo json_encode(array(
                    'id' => $progress->id,
                    'user_id' => $progress->user_id,
                    'course_id' => $progress->course_id,
                    'lesson_id' => $progress->lesson_id,
                    'status' => $progress->status,
                    'quizPassed' => (bool)$progress->quizPassed, // Pastikan boolean
                    'updated_at' => $progress->updated_at
                ));
            } else {
                http_response_code(404); // Not Found
                echo json_encode(array('msg' => 'Progres tidak ditemukan.'));
            }
        } else { // Jika tidak ada parameter spesifik, ambil semua progres pengguna
            $progress->user_id = $user_id_from_token;
            $stmt = $progress->readAllByUserId();
            $num = $stmt->rowCount();

            if ($num > 0) {
                $progress_arr = array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    $progress_item = array(
                        'id' => $id,
                        'user_id' => $user_id,
                        'course_id' => $course_id,
                        'lesson_id' => $lesson_id,
                        'status' => $status,
                        'quizPassed' => $quizPassed,
                        'updated_at' => $updated_at
                    );
                    array_push($progress_arr, $progress_item);
                }
                http_response_code(200);
                // Format ulang data agar sesuai dengan ekspektasi frontend (objek bersarang)
                $formatted_progress = [];
                foreach ($progress_arr as $item) {
                    if (!isset($formatted_progress[$item['course_id']])) {
                        $formatted_progress[$item['course_id']] = [];
                    }
                    $formatted_progress[$item['course_id']][$item['lesson_id']] = [
                        'status' => $item['status'],
                        'quizPassed' => (bool)$item['quizPassed'], // Pastikan boolean
                        'unlocked' => true // Asumsi jika ada di DB, itu sudah terbuka
                    ];
                }
                echo json_encode($formatted_progress); // Mengembalikan objek bersarang
            } else {
                http_response_code(200); // OK, tapi tidak ada konten
                echo json_encode(array('msg' => 'Tidak ada progres ditemukan untuk pengguna ini.'));
            }
        }
        break;

    case 'PUT': // Memperbarui progres
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->course_id) || empty($data->lesson_id) || empty($data->status)) {
            http_response_code(400);
            echo json_encode(array('msg' => 'Mohon lengkapi course_id, lesson_id, dan status untuk update.'));
            exit();
        }

        $progress->user_id = $user_id_from_token;
        $progress->course_id = $data->course_id;
        $progress->lesson_id = $data->lesson_id;
        $progress->status = $data->status;
        $progress->quizPassed = isset($data->quizPassed) ? $data->quizPassed : false; // Izinkan update quizPassed

        if ($progress->update()) {
            http_response_code(200);
            echo json_encode(array('msg' => 'Progres berhasil diperbarui.'));
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(array('msg' => 'Gagal memperbarui progres. Pastikan progres ada.'));
        }
        break;

    case 'DELETE': // Menghapus progres
        $data = json_decode(file_get_contents("php://input")); // Menggunakan body untuk DELETE

        if (empty($data->course_id) || empty($data->lesson_id)) {
            http_response_code(400);
            echo json_encode(array('msg' => 'Mohon lengkapi course_id dan lesson_id untuk delete.'));
            exit();
        }

        $progress->user_id = $user_id_from_token;
        $progress->course_id = $data->course_id;
        $progress->lesson_id = $data->lesson_id;

        if ($progress->delete()) {
            http_response_code(200);
            echo json_encode(array('msg' => 'Progres berhasil dihapus.'));
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(array('msg' => 'Gagal menghapus progres. Pastikan progres ada.'));
        }
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(array('msg' => 'Metode tidak diizinkan.'));
        break;
}

// Tutup koneksi database (opsional)
$database->close();
?>
