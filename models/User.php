<?php
// eduspace-backend-php/models/User.php

class User {
    // Koneksi database dan nama tabel
    private $conn;
    private $table = 'users';

    // Properti objek pengguna
    public $id;
    public $username;
    public $email;
    public $password; // Password akan di-hash
    public $fullName;
    public $profilePicture;
    public $bio; // <<<--- PASTIKAN PROPERTI INI ADA
    public $firebaseUid; // Jika Anda menggunakan Firebase UID
    public $created_at;
    public $updated_at;

    // Konstruktor dengan koneksi database
    public function __construct($db) {
        $this->conn = $db;
    }

    // Metode untuk mendaftarkan pengguna baru
    public function register() {
        // Query untuk membuat pengguna
        $query = 'INSERT INTO ' . $this->table . '
                  SET
                    username = :username,
                    email = :email,
                    password = :password,
                    fullName = :fullName,
                    profilePicture = :profilePicture';
                    // bio tidak disertakan di sini karena biasanya diisi setelah pendaftaran

        // Siapkan pernyataan
        $stmt = $this->conn->prepare($query);

        // Bersihkan data (sanitasi)
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->fullName = htmlspecialchars(strip_tags($this->fullName));
        $this->profilePicture = htmlspecialchars(strip_tags($this->profilePicture));

        // Hash password sebelum menyimpan
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        // Ikat data
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $hashed_password); // Gunakan password yang di-hash
        $stmt->bindParam(':fullName', $this->fullName);
        $stmt->bindParam(':profilePicture', $this->profilePicture);

        // Jalankan query
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId(); // Dapatkan ID pengguna yang baru dibuat
            return true;
        }

        error_log("User registration error: " . $stmt->errorInfo()[2]); // Log error ke server
        return false;
    }

    // Metode untuk mencari pengguna berdasarkan email
    public function findByEmail() {
        // Query untuk mendapatkan pengguna
        $query = 'SELECT
                    id, username, email, password, fullName, profilePicture, bio, firebaseUid, created_at, updated_at
                  FROM
                    ' . $this->table . '
                  WHERE
                    email = :email
                  LIMIT 0,1';

        // Siapkan pernyataan
        $stmt = $this->conn->prepare($query);

        // Ikat email
        $stmt->bindParam(':email', $this->email);

        // Jalankan query
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Atur properti
        if ($row) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password']; // Ini adalah password yang di-hash dari DB
            $this->fullName = $row['fullName'];
            $this->profilePicture = $row['profilePicture'];
            $this->bio = $row['bio']; // <<<--- PASTIKAN INI DISET
            $this->firebaseUid = $row['firebaseUid'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Metode untuk memverifikasi password
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }

    // Metode untuk mencari pengguna berdasarkan ID (digunakan di profile.php)
    public function findById() {
        $query = 'SELECT
                    id, username, email, password, fullName, profilePicture, bio, firebaseUid, created_at, updated_at
                  FROM
                    ' . $this->table . '
                  WHERE
                    id = :id
                  LIMIT 0,1';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->fullName = $row['fullName'];
            $this->profilePicture = $row['profilePicture'];
            $this->bio = $row['bio']; // <<<--- PASTIKAN INI DISET
            $this->firebaseUid = $row['firebaseUid'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Metode untuk mencari pengguna berdasarkan username
    public function findByUsername() {
        $query = 'SELECT
                    id, username, email, password, fullName, profilePicture, bio, firebaseUid, created_at, updated_at
                  FROM
                    ' . $this->table . '
                  WHERE
                    username = :username
                  LIMIT 0,1';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $this->username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->fullName = $row['fullName'];
            $this->profilePicture = $row['profilePicture'];
            $this->bio = $row['bio']; // <<<--- PASTIKAN INI DISET
            $this->firebaseUid = $row['firebaseUid'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Metode untuk memperbarui pengguna (misalnya, setelah login Firebase)
    public function update() {
        $query = 'UPDATE ' . $this->table . '
                  SET
                    username = :username,
                    fullName = :fullName,
                    profilePicture = :profilePicture,
                    bio = :bio,
                    email = :email,
                    updated_at = CURRENT_TIMESTAMP
                  WHERE
                    id = :id';

        $stmt = $this->conn->prepare($query);

        // Bersihkan data
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->fullName = htmlspecialchars(strip_tags($this->fullName));
        $this->profilePicture = htmlspecialchars(strip_tags($this->profilePicture));
        $this->bio = htmlspecialchars(strip_tags($this->bio)); // <<<--- PASTIKAN INI DISET
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Ikat data
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':fullName', $this->fullName);
        $stmt->bindParam(':profilePicture', $this->profilePicture);
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }

        error_log("User update error: " . $stmt->errorInfo()[2]); // Log error ke server
        return false;
    }
}
