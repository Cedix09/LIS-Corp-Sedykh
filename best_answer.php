<?php
require_once 'config/database.php';
require_once 'auth_check.php';

if ($_SESSION['role'] !== 'admin') {
    die("Нет доступа");
}

$database = new Database();
$pdo = $database->getConnection();

$post_id = $_GET['post_id'] ?? null;
$topic_id = $_GET['topic_id'] ?? null;

if (!$post_id || !$topic_id) {
    die("Ошибка");
}

try {

    // сброс всех best в теме
    $stmt = $pdo->prepare("
        UPDATE forum_posts
        SET is_best = 0
        WHERE topic_id = :topic
    ");
    $stmt->execute([':topic' => $topic_id]);

    // ставим новый best
    $stmt = $pdo->prepare("
        UPDATE forum_posts
        SET is_best = 1
        WHERE id = :id
    ");
    $stmt->execute([':id' => $post_id]);

    header("Location: topic_view.php?id=$topic_id");
    exit;

} catch (PDOException $e) {
    die("Ошибка сервера");
}