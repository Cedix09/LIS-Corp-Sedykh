<?php
class ForumTopic {
    private $conn;
    private $table = 'forum_topics';

    public $id;
    public $category_id;
    public $title;
    public $author_ip;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $sql = 'INSERT INTO ' . $this->table . ' (category_id, title, author_ip) VALUES (:category_id, :title, :author_ip)';
        $stmt = $this->conn->prepare($sql);
        $this->title = htmlspecialchars(strip_tags($this->title)); // Защита от XSS [cite: 381, 605]
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':author_ip', $this->author_ip);
        return $stmt->execute(); // [cite: 604]
    }

    public function getAll() {
        $sql = 'SELECT * FROM ' . $this->table . ' ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    public function getById($id) {
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>