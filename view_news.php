<?php
require_once 'auth_check.php';
require_once 'config/database.php';
require_once 'config/moderation.php';
require_once 'config/activity.php';

$database = new Database();
$pdo = $database->getConnection();

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Ошибка 404");
}

$id = (int) $id;
$userId = (int) $_SESSION['user_id'];

function redirectToNews(int $newsId): void
{
    header("Location: view_news.php?id=$newsId");
    exit;
}

try {
    ensureNewsCommentModerationColumns($pdo);
} catch (PDOException $e) {
    die("Ошибка сервера");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $commentId = (int) ($_POST['comment_id'] ?? 0);

    if ($action === 'delete_comment') {
        $stmt = $pdo->prepare("
            DELETE FROM news_comments
            WHERE id = :id AND news_id = :news_id AND user_id = :user_id
        ");
        $stmt->execute([
            ':id' => $commentId,
            ':news_id' => $id,
            ':user_id' => $userId
        ]);

        logUserActivity($pdo, $userId, 'delete_comment', 'news_comment', $commentId, 'Удалил комментарий к новости');

        redirectToNews($id);
    }

    if ($action === 'resubmit_comment') {
        $stmt = $pdo->prepare("
            UPDATE news_comments
            SET moderation_status = 'pending'
            WHERE id = :id AND news_id = :news_id AND user_id = :user_id
        ");
        $stmt->execute([
            ':id' => $commentId,
            ':news_id' => $id,
            ':user_id' => $userId
        ]);

        logUserActivity($pdo, $userId, 'resubmit_comment', 'news_comment', $commentId, 'Отправил комментарий новости на повторную проверку');

        redirectToNews($id);
    }
}

function renderNewsComment(array $comment, array $commentsByParent, array $news, int $userId, bool $isReply = false): void
{
    $status = $comment['moderation_status'] ?? 'approved';
    $isApproved = $status === 'approved';
    $canManage = (int) ($comment['user_id'] ?? 0) === $userId;
    ?>
    <div class="comment-item <?= $isReply ? 'comment-reply' : '' ?> <?= moderationStatusClass($status) ?>">

        <div class="comment-author">
            <?= htmlspecialchars($comment['author_name']) ?>
            <?php if (!$isApproved): ?>
                <span class="moderation-badge"><?= moderationStatusLabel($status) ?></span>
            <?php endif; ?>
        </div>

        <div class="comment-date">
            <?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?>
        </div>

        <div class="comment-text">
            <?= nl2br(htmlspecialchars($comment['comment_text'])) ?>
        </div>

        <div class="comment-actions">
            <?php if ($isApproved && !$isReply && isset($_SESSION['username'])): ?>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#news-reply-<?= $comment['id'] ?>">
                    Ответить
                </button>
            <?php endif; ?>

            <?php if ($canManage): ?>
                <form method="POST" onsubmit="return confirm('Удалить комментарий?');">
                    <input type="hidden" name="action" value="delete_comment">
                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Удалить</button>
                </form>

                <?php if ($status === 'rejected'): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="resubmit_comment">
                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                        <button class="btn btn-sm btn-outline-warning">На повторную проверку</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($isApproved && !$isReply && isset($_SESSION['username'])): ?>
            <div class="collapse mt-3" id="news-reply-<?= $comment['id'] ?>">
                <form action="add_comment.php" method="POST" class="comment-form">
                    <input type="hidden" name="news_id" value="<?= $news['id'] ?>">
                    <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">

                    <div class="mb-2">
                        <textarea name="comment_text" class="form-control" rows="3" maxlength="1000" placeholder="Ваш ответ" required></textarea>
                    </div>

                    <button class="btn btn-dark btn-sm">Отправить</button>
                </form>
            </div>
        <?php endif; ?>

    </div>

    <?php foreach ($commentsByParent[$comment['id']] ?? [] as $reply): ?>
        <?php renderNewsComment($reply, $commentsByParent, $news, $userId, true); ?>
    <?php endforeach; ?>
    <?php
}

try {
    $stmt = $pdo->prepare("
        SELECT news.*, news_categories.name AS category_name
        FROM news
        LEFT JOIN news_categories ON news.category_id = news_categories.id
        WHERE news.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $news = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$news) {
        die("Новость не найдена");
    }

    $stmt = $pdo->prepare("UPDATE news SET views = views + 1 WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $stmt = $pdo->prepare("
        SELECT * FROM news_comments
        WHERE news_id = :id
            AND (moderation_status = 'approved' OR user_id = :user_id)
        ORDER BY created_at ASC
    ");
    $stmt->execute([
        ':id' => $id,
        ':user_id' => $userId
    ]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ошибка сервера");
}

$commentsByParent = [];
foreach ($comments as $comment) {
    $parentKey = $comment['parent_id'] ?? 0;
    $commentsByParent[$parentKey][] = $comment;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($news['title']) ?> | LIS Corp</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="news-view">
<div class="container">

<h1 class="mb-3"><?= htmlspecialchars($news['title']) ?></h1>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success text-center">Комментарий отправлен на проверку</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<?php $errors = explode('|', $_GET['error']); ?>
<div class="alert alert-danger">
<?php foreach ($errors as $error): ?>
<div><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="news-meta mb-4">
<?= htmlspecialchars($news['category_name']) ?> •
<?= date('d.m.Y', strtotime($news['created_at'])) ?> •
Просмотры: <?= $news['views'] ?>
</div>

<?php if (!empty($news['preview_img'])): ?>
<img src="images/news/<?= htmlspecialchars($news['preview_img']) ?>" class="news-image mb-4">
<?php endif; ?>

<div class="news-text">
<?= nl2br(htmlspecialchars($news['content'])) ?>
</div>

<hr class="my-5">

<h3>Комментарии</h3>

<?php if (isset($_SESSION['username'])): ?>

<p><strong>Вы как:</strong> <?= htmlspecialchars($_SESSION['username']) ?></p>

<form action="add_comment.php" method="POST" class="comment-form">

<input type="hidden" name="news_id" value="<?= $news['id'] ?>">

<div class="mb-3">
<textarea name="comment_text" class="form-control" rows="4" maxlength="1000" placeholder="Ваш комментарий" required></textarea>
</div>

<button class="btn btn-dark">Отправить на проверку</button>

</form>

<?php else: ?>

<p>Чтобы оставить комментарий, войдите в систему.</p>

<?php endif; ?>

<div class="comments-list mt-4">

<?php foreach ($commentsByParent[0] ?? [] as $comment): ?>
    <?php renderNewsComment($comment, $commentsByParent, $news, $userId); ?>
<?php endforeach; ?>

</div>

</div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
