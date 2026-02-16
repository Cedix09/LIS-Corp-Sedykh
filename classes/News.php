<?php
class News {
    private $conn;
    private $table = 'news'; 

    public $id;
    public $category_id;
    public $title;
    public $content;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // CREATE (Создание)
    public function create() {
        $sql = 'INSERT INTO ' . $this->table . ' (category_id, title, content) VALUES (:category_id, :title, :content)';
        $stmt = $this->conn->prepare($sql);
        // Защита от XSS и очистка 
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->content = htmlspecialchars(strip_tags($this->content));
        // Привязка параметров 
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':content', $this->content);
        return $stmt->execute();
    }

    // READ ALL (Получение всех)
    public function getAll() {
        $sql = 'SELECT * FROM ' . $this->table . ' ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    // READ ONE (Получение одной новости по ID)
    public function getById($id) {
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->id = $row['id'];
            $this->title = $row['title'];
            $this->content = $row['content'];
            $this->category_id = $row['category_id'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    // UPDATE (Обновление)
    public function update() {
        $sql = 'UPDATE ' . $this->table . ' SET title = :title, content = :content WHERE id = :id';
        $stmt = $this->conn->prepare($sql);
        $this->title = htmlspecialchars(strip_tags($this->title));
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':content', $this->content);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    // DELETE (Удаление)
    public function delete($id) {
        $sql = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>