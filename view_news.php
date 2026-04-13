<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Ошибка 404");
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
        ORDER BY created_at DESC
    ");
    $stmt->execute([':id' => $id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ошибка сервера");
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
<div class="alert alert-success text-center">Комментарий добавлен</div>
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

<img src="images/news/<?= htmlspecialchars($news['preview_img']) ?>" class="news-image mb-4">

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

<button class="btn btn-dark">Отправить</button>

</form>

<?php else: ?>

<p>Чтобы оставить комментарий, войдите в систему.</p>

<?php endif; ?>

<div class="comments-list mt-4">

<?php foreach ($comments as $comment): ?>

<div class="comment-item">

<div class="comment-author">
<?= htmlspecialchars($comment['author_name']) ?>
</div>

<div class="comment-date">
<?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?>
</div>

<div class="comment-text">
<?= nl2br(htmlspecialchars($comment['comment_text'])) ?>
</div>

</div>

<?php endforeach; ?>

</div>

</div>
</section>

<?php include 'footer.php'; ?>

</body>
</html>