<?php
class Feedback {
    private $conn;
    private $table = 'feedback';

    public $id;
    public $user_name;
    public $user_email;
    public $message;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $sql = 'INSERT INTO ' . $this->table . ' (user_name, user_email, message) VALUES (:user_name, :user_email, :message)';
        $stmt = $this->conn->prepare($sql);
        $this->user_name = htmlspecialchars(strip_tags($this->user_name));
        $this->message = htmlspecialchars(strip_tags($this->message));
        $stmt->bindParam(':user_name', $this->user_name);
        $stmt->bindParam(':user_email', $this->user_email);
        $stmt->bindParam(':message', $this->message);
        return $stmt->execute();
    }

    public function getAll() {
        $sql = 'SELECT * FROM ' . $this->table . ' ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }
}
?>