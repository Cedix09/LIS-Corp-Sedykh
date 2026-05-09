<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$news_id = $_POST['news_id'] ?? null;
$parent_id = $_POST['parent_id'] ?? null;
$name = $_SESSION['username'] ?? '';
$text = trim($_POST['comment_text'] ?? '');

$errors = [];

function ensureNewsCommentColumns(PDO $pdo): void
{
    $columns = $pdo->query("SHOW COLUMNS FROM news_comments")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('parent_id', $columns, true)) {
        $pdo->exec("ALTER TABLE news_comments ADD parent_id INT NULL AFTER news_id");
    }
}

if (!$news_id || !is_numeric($news_id)) {
    $errors[] = "Ошибка новости";
}

if ($parent_id !== null && $parent_id !== '' && !is_numeric($parent_id)) {
    $errors[] = "Некорректный комментарий для ответа";
}

if (empty($text)) {
    $errors[] = "Введите комментарий";
}

if (mb_strlen($text) > 1000) {
    $errors[] = "Комментарий слишком длинный";
}

try {
    ensureNewsCommentColumns($pdo);

    if (empty($errors) && $parent_id) {
        $stmt = $pdo->prepare("
            SELECT id FROM news_comments
            WHERE id = :parent_id AND news_id = :news_id AND parent_id IS NULL
        ");
        $stmt->execute([
            ':parent_id' => $parent_id,
            ':news_id' => $news_id
        ]);

        if (!$stmt->fetchColumn()) {
            $errors[] = "Комментарий для ответа не найден";
        }
    }
} catch (PDOException $e) {
    $errors[] = "Ошибка сервера";
}

if (count($errors) > 0) {
    $error = urlencode(implode('|', $errors));
    header("Location: view_news.php?id=$news_id&error=$error");
    exit;
}

try {

    $stmt = $pdo->prepare("
        INSERT INTO news_comments (news_id, parent_id, author_name, comment_text)
        VALUES (:news_id, :parent_id, :name, :text)
    ");

    $stmt->execute([
        ':news_id' => $news_id,
        ':parent_id' => $parent_id ?: null,
        ':name' => $name,
        ':text' => $text
    ]);

    header("Location: view_news.php?id=$news_id&success=1");
    exit;

} catch (PDOException $e) {

    header("Location: view_news.php?id=$news_id&error=" . urlencode("Ошибка сервера"));
    exit;

}
