<?php
// eduspace-backend-php/models/UserProgress.php

class UserProgress {
    // Koneksi database dan nama tabel
    private $conn;
    private $table = 'user_progress';

    // Properti objek progres pengguna
    public $id;
    public $user_id;
    public $course_id;
    public $lesson_id;
    public $status; // 'unlocked', 'in-progress', 'completed'
    public $quizPassed; // BOOLEAN
    public $updated_at;

    // Konstruktor dengan koneksi database
    public function __construct($db) {
        $this->conn = $db;
    }

    // Metode untuk membuat/mencatat progres baru
    public function create() {
        // Query untuk membuat progres baru
        // Menggunakan INSERT IGNORE untuk menghindari duplikasi pada UNIQUE (user_id, course_id, lesson_id)
        $query = 'INSERT IGNORE INTO ' . $this->table . '
                  SET
                    user_id = :user_id,
                    course_id = :course_id,
                    lesson_id = :lesson_id,
                    status = :status,
                    quizPassed = :quizPassed';

        // Siapkan pernyataan
        $stmt = $this->conn->prepare($query);

        // Bersihkan data
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->course_id = htmlspecialchars(strip_tags($this->course_id));
        $this->lesson_id = htmlspecialchars(strip_tags($this->lesson_id));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->quizPassed = filter_var($this->quizPassed, FILTER_VALIDATE_BOOLEAN); // Pastikan boolean

        // Ikat data
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':course_id', $this->course_id);
        $stmt->bindParam(':lesson_id', $this->lesson_id);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':quizPassed', $this->quizPassed, PDO::PARAM_BOOL);

        // Jalankan query
        if ($stmt->execute()) {
            // Jika baris baru disisipkan (bukan diabaikan karena duplikasi)
            if ($stmt->rowCount() > 0) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            // Jika diabaikan (sudah ada)
            return false;
        }

        // Cetak error jika ada
        printf("Error: %s.\n", $stmt->errorInfo()[2]);
        return false;
    }

    // Metode untuk membaca progres pengguna berdasarkan user_id, course_id, dan lesson_id
    public function readSingle() {
        $query = 'SELECT
                    id, user_id, course_id, lesson_id, status, quizPassed, updated_at
                  FROM
                    ' . $this->table . '
                  WHERE
                    user_id = :user_id AND course_id = :course_id AND lesson_id = :lesson_id
                  LIMIT 0,1';

        $stmt = $this->conn->prepare($query);

        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->course_id = htmlspecialchars(strip_tags($this->course_id));
        $this->lesson_id = htmlspecialchars(strip_tags($this->lesson_id));

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':course_id', $this->course_id);
        $stmt->bindParam(':lesson_id', $this->lesson_id);

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->course_id = $row['course_id'];
            $this->lesson_id = $row['lesson_id'];
            $this->status = $row['status'];
            $this->quizPassed = $row['quizPassed'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Metode untuk membaca semua progres untuk user_id tertentu
    public function readAllByUserId() {
        $query = 'SELECT
                    id, user_id, course_id, lesson_id, status, quizPassed, updated_at
                  FROM
                    ' . $this->table . '
                  WHERE
                    user_id = :user_id
                  ORDER BY
                    updated_at DESC';

        $stmt = $this->conn->prepare($query);
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();

        return $stmt; // Mengembalikan objek statement untuk di-fetch di API
    }


    // Metode untuk memperbarui progres
    public function update() {
        $query = 'UPDATE ' . $this->table . '
                  SET
                    status = :status,
                    quizPassed = :quizPassed,
                    updated_at = CURRENT_TIMESTAMP
                  WHERE
                    user_id = :user_id AND course_id = :course_id AND lesson_id = :lesson_id';

        $stmt = $this->conn->prepare($query);

        // Bersihkan data
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->course_id = htmlspecialchars(strip_tags($this->course_id));
        $this->lesson_id = htmlspecialchars(strip_tags($this->lesson_id));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->quizPassed = filter_var($this->quizPassed, FILTER_VALIDATE_BOOLEAN);

        // Ikat data
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':course_id', $this->course_id);
        $stmt->bindParam(':lesson_id', $this->lesson_id);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':quizPassed', $this->quizPassed, PDO::PARAM_BOOL);

        // Jalankan query
        if ($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->errorInfo()[2]);
        return false;
    }

    // Metode untuk menghapus progres
    public function delete() {
        $query = 'DELETE FROM ' . $this->table . ' WHERE user_id = :user_id AND course_id = :course_id AND lesson_id = :lesson_id';

        $stmt = $this->conn->prepare($query);

        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->course_id = htmlspecialchars(strip_tags($this->course_id));
        $this->lesson_id = htmlspecialchars(strip_tags($this->lesson_id));

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':course_id', $this->course_id);
        $stmt->bindParam(':lesson_id', $this->lesson_id);

        if ($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->errorInfo()[2]);
        return false;
    }
}
?>
