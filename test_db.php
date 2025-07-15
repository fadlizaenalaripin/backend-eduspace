<?php
require_once './config/database.php';

$db = (new Database())->connect();

if ($db) {
    echo "✅ Koneksi berhasil ke database!";
} else {
    echo "❌ Gagal konek ke database. Cek log.";
}
?>
