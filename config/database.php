<?php
// eduspace-backend-php/config/database.php

class Database {
    // Properti koneksi database
    private $host = 'localhost'; // Host MySQL Anda (biasanya localhost untuk Laragon)
    private $db_name = 'eduspace_db'; // Nama database yang Anda buat di phpMyAdmin
    private $username = 'root'; // Username database Anda (default Laragon)
    private $password = 'root'; // <--- BARIS INI DIUBAH: Password database Anda
    private $conn;

    // Metode untuk mendapatkan koneksi database
    public function connect() {
        $this->conn = null;

        try {
            // Menggunakan PDO (PHP Data Objects) untuk koneksi database
            // Ini adalah cara yang lebih modern dan aman untuk berinteraksi dengan database di PHP
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Mengatur mode error untuk PDO
            $this->conn->exec('set names utf8'); // Mengatur encoding karakter
        } catch(PDOException $e) {
            error_log('Connection Error: ' . $e->getMessage()); // Catat error ke log server
            return null; // Kembalikan null jika koneksi gagal
        }

        return $this->conn;
    }

    // Metode untuk menutup koneksi (opsional, PHP akan menutupnya secara otomatis)
    public function close() {
        $this->conn = null;
    }
}

// Untuk menguji koneksi secara langsung (Anda bisa menghapus baris ini nanti)
// $database = new Database();
// $db = $database->connect();
?>
