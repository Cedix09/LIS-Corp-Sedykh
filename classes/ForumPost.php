<?php
class ForumPost {
    private $conn;
    private $table = 'forum_posts';

    public $id;
    public $topic_id;
    public $message;
    public $author_ip;
    public $rating;
    public $is_best;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $sql = 'INSERT INTO ' . $this->table . ' (topic_id, message, author_ip) VALUES (:topic_id, :message, :author_ip)';
        $stmt = $this->conn->prepare($sql);
        $this->message = htmlspecialchars(strip_tags($this->message)); // Защита от XSS 
        $stmt->bindParam(':topic_id', $this->topic_id);
        $stmt->bindParam(':message', $this->message);
        $stmt->bindParam(':author_ip', $this->author_ip);
        return $stmt->execute();
    }

    // Бонусный метод: получить все сообщения одной темы 
    public function getByTopicId($topic_id) {
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE topic_id = :topic_id ORDER BY created_at ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':topic_id', $topic_id);
        $stmt->execute();
        return $stmt;
    }

    public function delete($id) {
        $sql = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>