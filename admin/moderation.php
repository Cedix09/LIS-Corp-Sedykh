<?php
require_once 'moder_check.php';
require_once '../config/database.php';
require_once '../config/moderation.php';

$database = new Database();
$pdo = $database->getConnection();

$errors = [];

try {
    ensureForumPostModerationColumns($pdo);
    ensureNewsCommentModerationColumns($pdo);
} catch (PDOException $e) {
    die("Ошибка сервера");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $id = $_POST['id'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!is_numeric($id) || !in_array($type, ['forum', 'news'], true) || !in_array($action, ['approve', 'reject', 'delete'], true)) {
        $errors[] = "Некорректное действие";
    } else {
        try {
            if ($type === 'forum') {
                if ($action === 'delete') {
                    $stmt = $pdo->prepare("DELETE FROM forum_votes WHERE post_id = :id");
                    $stmt->execute([':id' => $id]);

                    $stmt = $pdo->prepare("DELETE FROM forum_posts WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                } else {
                    $status = $action === 'approve' ? 'approved' : 'rejected';
                    $stmt = $pdo->prepare("UPDATE forum_posts SET moderation_status = :status WHERE id = :id");
                    $stmt->execute([
                        ':status' => $status,
                        ':id' => $id
                    ]);
                }
            }

            if ($type === 'news') {
                if ($action === 'delete') {
                    $stmt = $pdo->prepare("DELETE FROM news_comments WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                } else {
                    $status = $action === 'approve' ? 'approved' : 'rejected';
                    $stmt = $pdo->prepare("UPDATE news_comments SET moderation_status = :status WHERE id = :id");
                    $stmt->execute([
                        ':status' => $status,
                        ':id' => $id
                    ]);
                }
            }

            header("Location: moderation.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Не удалось выполнить действие";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT forum_posts.id, forum_posts.message AS text, forum_posts.created_at,
        forum_posts.moderation_status, users.username AS author_name,
        forum_topics.id AS source_id, forum_topics.title AS source_title
    FROM forum_posts
    INNER JOIN users ON forum_posts.user_id = users.id
    INNER JOIN forum_topics ON forum_posts.topic_id = forum_topics.id
    WHERE forum_posts.moderation_status IN ('pending', 'rejected')
    ORDER BY forum_posts.created_at ASC
");
$stmt->execute();
$forumComments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT news_comments.id, news_comments.comment_text AS text, news_comments.created_at,
        news_comments.moderation_status, news_comments.author_name,
        news.id AS source_id, news.title AS source_title
    FROM news_comments
    INNER JOIN news ON news_comments.news_id = news.id
    WHERE news_comments.moderation_status IN ('pending', 'rejected')
    ORDER BY news_comments.created_at ASC
");
$stmt->execute();
$newsComments = $stmt->fetchAll(PDO::FETCH_ASSOC);

function renderModerationActions(string $type, int $id): void
{
    ?>
    <form method="POST" class="moderation-action">
        <input type="hidden" name="type" value="<?= $type ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="action" value="approve">
        <button class="btn btn-sm btn-success">Одобрить</button>
    </form>

    <form method="POST" class="moderation-action">
        <input type="hidden" name="type" value="<?= $type ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="action" value="reject">
        <button class="btn btn-sm btn-warning">Отклонить</button>
    </form>

    <form method="POST" class="moderation-action" onsubmit="return confirm('Удалить комментарий?');">
        <input type="hidden" name="type" value="<?= $type ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="action" value="delete">
        <button class="btn btn-sm btn-danger">Удалить</button>
    </form>
    <?php
}

function renderModerationRows(array $comments, string $type): void
{
    foreach ($comments as $comment):
        $sourceUrl = $type === 'forum'
            ? '../topic_view.php?id=' . $comment['source_id']
            : '../view_news.php?id=' . $comment['source_id'];
        ?>
        <tr class="<?= moderationStatusClass($comment['moderation_status']) ?>">
            <td><?= htmlspecialchars($comment['author_name']) ?></td>
            <td>
                <a href="<?= $sourceUrl ?>"><?= htmlspecialchars($comment['source_title']) ?></a>
            </td>
            <td><?= nl2br(htmlspecialchars($comment['text'])) ?></td>
            <td><?= moderationStatusLabel($comment['moderation_status']) ?></td>
            <td><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></td>
            <td class="moderation-actions">
                <?php renderModerationActions($type, (int) $comment['id']); ?>
            </td>
        </tr>
        <?php
    endforeach;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Модерация контента</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../styles/style.css">
</head>
<body>

<?php include '../header.php'; ?>

<div class="container mt-5">

<h1 class="mb-4">Модерация контента</h1>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
<?php foreach ($errors as $error): ?>
<div><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<h2 class="h4 mb-3">Форум</h2>

<table class="table table-bordered align-middle moderation-table">
<tr>
<th>Автор</th>
<th>Тема</th>
<th>Комментарий</th>
<th>Статус</th>
<th>Дата</th>
<th>Действия</th>
</tr>
<?php renderModerationRows($forumComments, 'forum'); ?>
<?php if (!$forumComments): ?>
<tr><td colspan="6" class="text-center">Нет комментариев на проверке</td></tr>
<?php endif; ?>
</table>

<h2 class="h4 mt-5 mb-3">Новости</h2>

<table class="table table-bordered align-middle moderation-table">
<tr>
<th>Автор</th>
<th>Новость</th>
<th>Комментарий</th>
<th>Статус</th>
<th>Дата</th>
<th>Действия</th>
</tr>
<?php renderModerationRows($newsComments, 'news'); ?>
<?php if (!$newsComments): ?>
<tr><td colspan="6" class="text-center">Нет комментариев на проверке</td></tr>
<?php endif; ?>
</table>

</div>

</body>
</html>
