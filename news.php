<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$search = $_GET['search'] ?? '';
$category = $_GET['cat'] ?? '';

try {

    // Получаем категории
    $stmt = $pdo->prepare("SELECT * FROM news_categories");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Базовый SQL
    $sql = "
        SELECT news.*, news_categories.name AS category_name
        FROM news
        LEFT JOIN news_categories ON news.category_id = news_categories.id
        WHERE 1
    ";

    $params = [];

    // Поиск
    if (!empty($search)) {
        $sql .= " AND news.title LIKE :search";
        $params[':search'] = "%$search%";
    }

    // Фильтр по категории
    if (!empty($category) && is_numeric($category)) {
        $sql .= " AND news.category_id = :cat";
        $params[':cat'] = $category;
    }

    $sql .= " ORDER BY news.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $news = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Новости | LIS Corp</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="news-section">
<div class="container">

<h1 class="mb-5 text-center">Новости</h1>

<!-- 🔍 ПОИСК -->
<form method="GET" class="mb-4 search-form">
<input type="text" name="search" class="form-control" placeholder="Поиск..." value="<?= htmlspecialchars($search) ?>">
</form>

<div class="row">

<!-- 🔹 SIDEBAR -->
<div class="col-md-3">

<h5>Категории</h5>

<ul class="list-group mb-4">

<li class="list-group-item">
<a href="news.php">Все</a>
</li>

<?php foreach ($categories as $cat): ?>

<li class="list-group-item">
<a href="news.php?cat=<?= $cat['id'] ?>">
<?= htmlspecialchars($cat['name']) ?>
</a>
</li>

<?php endforeach; ?>

</ul>

</div>

<!-- 🔹 НОВОСТИ -->
<div class="col-md-9">

<?php foreach ($news as $item): ?>

<div class="news-card">

<img src="images/news/<?= htmlspecialchars($item['preview_img']) ?>" alt="">

<div class="news-content">

<div class="news-meta">
<?= htmlspecialchars($item['category_name']) ?> • 
<?= date('d.m.Y', strtotime($item['created_at'])) ?>
</div>

<h3>
<a href="view_news.php?id=<?= $item['id'] ?>">
<?= htmlspecialchars($item['title']) ?>
</a>
</h3>

</div>

</div>

<?php endforeach; ?>

<?php if (count($news) === 0): ?>
<p>Ничего не найдено</p>
<?php endif; ?>

</div>

</div>

</div>
</section>

<?php include 'footer.php'; ?>

</body>
</html>