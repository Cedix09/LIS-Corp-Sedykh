<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$news = [];

try {

    $stmt = $pdo->prepare("
        SELECT news.*, news_categories.name AS category_name
        FROM news
        LEFT JOIN news_categories ON news.category_id = news_categories.id
        ORDER BY created_at DESC
    ");

    $stmt->execute();
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

</div>
</section>

<?php include 'footer.php'; ?>

</body>
</html>