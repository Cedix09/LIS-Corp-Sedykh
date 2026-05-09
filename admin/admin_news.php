<?php
require_once 'admin_check.php';
require_once '../config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_news_id'])) {
    $id = $_POST['delete_news_id'];

    if (is_numeric($id)) {
        $stmt = $pdo->prepare("DELETE FROM news WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    header("Location: admin_news.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_topic_id'])) {
    $id = $_POST['delete_topic_id'];

    if (is_numeric($id)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id FROM forum_posts WHERE topic_id = :id");
            $stmt->execute([':id' => $id]);
            $postIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if ($postIds) {
                $placeholders = implode(',', array_fill(0, count($postIds), '?'));
                $stmt = $pdo->prepare("DELETE FROM forum_votes WHERE post_id IN ($placeholders)");
                $stmt->execute($postIds);
            }

            $stmt = $pdo->prepare("DELETE FROM forum_posts WHERE topic_id = :id");
            $stmt->execute([':id' => $id]);

            $stmt = $pdo->prepare("DELETE FROM forum_topics WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $pdo->commit();
            header("Location: admin_news.php?topic_deleted=1");
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Не удалось удалить тему форума";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT news.*, news_categories.name AS category_name
    FROM news
    LEFT JOIN news_categories ON news.category_id = news_categories.id
    ORDER BY created_at DESC
");
$stmt->execute();
$news = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT forum_topics.*, forum_categories.title AS category_name,
        (SELECT COUNT(*) FROM forum_posts WHERE forum_posts.topic_id = forum_topics.id) AS replies
    FROM forum_topics
    LEFT JOIN forum_categories ON forum_topics.category_id = forum_categories.id
    ORDER BY forum_topics.created_at DESC
");
$stmt->execute();
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Админ панель</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../styles/style.css">
</head>
<body>

<?php include '../header.php'; ?>

<div class="container mt-5">

<h1 class="mb-4">Админ панель</h1>

<div class="mb-3">
<a href="admin_add.php" class="btn btn-success">Добавить новость</a>
</div>

<?php if (isset($_GET['topic_deleted'])): ?>
<div class="alert alert-success">Тема форума удалена</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
<?php foreach ($errors as $error): ?>
<div><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<h2 class="h4 mb-3">Новости</h2>

<table class="table table-bordered">

<tr>
<th>ID</th>
<th>Заголовок</th>
<th>Категория</th>
<th>Дата</th>
<th>Действия</th>
</tr>

<?php foreach ($news as $item): ?>

<tr>
<td><?= $item['id'] ?></td>
<td><?= htmlspecialchars($item['title']) ?></td>
<td><?= htmlspecialchars($item['category_name']) ?></td>
<td><?= $item['created_at'] ?></td>
<td>

<a href="../view_news.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">Открыть</a>

<form method="POST" style="display:inline;">
<input type="hidden" name="delete_news_id" value="<?= $item['id'] ?>">
<button class="btn btn-sm btn-danger">Удалить</button>
</form>

</td>
</tr>

<?php endforeach; ?>

</table>

<h2 class="h4 mt-5 mb-3">Темы форума</h2>

<table class="table table-bordered align-middle">

<tr>
<th>ID</th>
<th>Тема</th>
<th>Категория</th>
<th>Ответов</th>
<th>Дата</th>
<th>Действия</th>
</tr>

<?php foreach ($topics as $topic): ?>

<tr>
<td><?= $topic['id'] ?></td>
<td><?= htmlspecialchars($topic['title']) ?></td>
<td><?= htmlspecialchars($topic['category_name'] ?? '') ?></td>
<td><?= $topic['replies'] ?></td>
<td><?= $topic['created_at'] ?></td>
<td>
<a href="../topic_view.php?id=<?= $topic['id'] ?>" class="btn btn-sm btn-primary">Открыть</a>

<form method="POST" style="display:inline;" onsubmit="return confirm('Удалить тему и все ее сообщения?');">
<input type="hidden" name="delete_topic_id" value="<?= $topic['id'] ?>">
<button class="btn btn-sm btn-danger">Удалить</button>
</form>
</td>
</tr>

<?php endforeach; ?>

<?php if (!$topics): ?>
<tr>
<td colspan="6" class="text-center">Тем пока нет</td>
</tr>
<?php endif; ?>

</table>

</div>

</body>
</html>
