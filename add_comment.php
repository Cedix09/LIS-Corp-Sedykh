<?php
require_once 'auth_check.php';
require_once 'config/database.php';
require_once 'config/moderation.php';
require_once 'config/activity.php';

$database = new Database();
$pdo = $database->getConnection();

$news_id = $_POST['news_id'] ?? null;
$parent_id = $_POST['parent_id'] ?? null;
$name = $_SESSION['username'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;
$text = trim($_POST['comment_text'] ?? '');

$errors = [];

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
    ensureNewsCommentModerationColumns($pdo);

    if (empty($errors) && $parent_id) {
        $stmt = $pdo->prepare("
            SELECT id FROM news_comments
            WHERE id = :parent_id AND news_id = :news_id AND parent_id IS NULL AND moderation_status = 'approved'
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
        INSERT INTO news_comments (news_id, parent_id, user_id, author_name, comment_text, moderation_status)
        VALUES (:news_id, :parent_id, :user_id, :name, :text, 'pending')
    ");

    $stmt->execute([
        ':news_id' => $news_id,
        ':parent_id' => $parent_id ?: null,
        ':user_id' => $user_id,
        ':name' => $name,
        ':text' => $text
    ]);

    logUserActivity($pdo, (int) $user_id, 'create_comment', 'news_comment', (int) $pdo->lastInsertId(), 'Написал комментарий к новости #' . $news_id);

    header("Location: view_news.php?id=$news_id&success=moderation");
    exit;

} catch (PDOException $e) {

    header("Location: view_news.php?id=$news_id&error=" . urlencode("Ошибка сервера"));
    exit;

}
