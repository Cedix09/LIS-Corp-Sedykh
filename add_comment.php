<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$news_id = $_POST['news_id'] ?? null;
$name = $_SESSION['username'] ?? '';
$text = trim($_POST['comment_text'] ?? '');

$errors = [];

if (!$news_id || !is_numeric($news_id)) {
    $errors[] = "Ошибка новости";
}

if (empty($text)) {
    $errors[] = "Введите комментарий";
}

if (mb_strlen($text) > 1000) {
    $errors[] = "Комментарий слишком длинный";
}

if (count($errors) > 0) {
    $error = urlencode(implode('|', $errors));
    header("Location: view_news.php?id=$news_id&error=$error");
    exit;
}

try {

    $stmt = $pdo->prepare("
        INSERT INTO news_comments (news_id, author_name, comment_text)
        VALUES (:news_id, :name, :text)
    ");

    $stmt->execute([
        ':news_id' => $news_id,
        ':name' => $name,
        ':text' => $text
    ]);

    header("Location: view_news.php?id=$news_id&success=1");
    exit;

} catch (PDOException $e) {

    header("Location: view_news.php?id=$news_id&error=" . urlencode("Ошибка сервера"));
    exit;

}