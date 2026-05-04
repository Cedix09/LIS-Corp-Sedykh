<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$search = trim($_GET['search'] ?? '');
$category = $_GET['cat'] ?? '';
$sort = $_GET['sort'] ?? 'new';

try {

    $stmt = $pdo->prepare("SELECT * FROM forum_categories");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "
        SELECT forum_topics.*, forum_categories.title AS category_name,
        (SELECT COUNT(*) FROM forum_posts WHERE topic_id = forum_topics.id) AS replies
        FROM forum_topics
        LEFT JOIN forum_categories ON forum_topics.category_id = forum_categories.id
        WHERE 1
    ";

    $params = [];

    if ($search !== '') {
        $sql .= " AND forum_topics.title LIKE :search";
        $params[':search'] = "%$search%";
    }

    if ($category && is_numeric($category)) {
        $sql .= " AND forum_topics.category_id = :cat";
        $params[':cat'] = $category;
    }

    if ($sort === 'old') {
        $sql .= " ORDER BY forum_topics.created_at ASC";
    } else {
        $sql .= " ORDER BY forum_topics.created_at DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $topics = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Форум | LIS Corp</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="forum-section">
<div class="container">

<h1 class="mb-5 text-center">Форум</h1>

<!-- 🔍 ПОИСК -->
<form method="GET" class="forum-search mb-4">
<input type="text" name="search" class="form-control"
placeholder="Поиск тем..."
value="<?= htmlspecialchars($search) ?>">
</form>

<div class="row">

<!-- 🔹 SIDEBAR -->
<div class="col-md-3 forum-sidebar">

<h5 class="mb-3">Категории</h5>

<ul class="list-group">

<li class="list-group-item">
<a href="forum.php">Все темы</a>
</li>

<?php foreach ($categories as $cat): ?>
<li class="list-group-item">
<a href="forum.php?cat=<?= $cat['id'] ?>">
<?= htmlspecialchars($cat['title']) ?>
</a>
</li>
<?php endforeach; ?>

</ul>

</div>

<!-- 🔹 ТЕМЫ -->
<div class="col-md-9">

<div class="forum-actions">
<a href="create_topic.php" class="btn btn-dark">Создать тему</a>

<a href="forum.php?sort=new" class="btn btn-outline-secondary">Новые</a>
<a href="forum.php?sort=old" class="btn btn-outline-secondary">Старые</a>
</div>

<?php foreach ($topics as $topic): ?>

<div class="forum-card">

<div class="forum-title">
<a href="topic_view.php?id=<?= $topic['id'] ?>">
<?= htmlspecialchars($topic['title']) ?>
</a>
</div>

<div class="forum-meta">
<?= htmlspecialchars($topic['category_name']) ?> • 
<?= date('d.m.Y H:i', strtotime($topic['created_at'])) ?> • 
Ответов: <?= $topic['replies'] ?>
</div>

</div>

<?php endforeach; ?>

<?php if (count($topics) === 0): ?>
<p>Тем не найдено</p>
<?php endif; ?>

</div>

</div>

</div>
</section>

<?php include 'footer.php'; ?>

</body>
</html>