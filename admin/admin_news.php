<?php
require_once 'admin_check.php';
require_once '../config/database.php';

$database = new Database();
$pdo = $database->getConnection();

// Удаление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];

    if (is_numeric($id)) {
        $stmt = $pdo->prepare("DELETE FROM news WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    header("Location: admin_news.php");
    exit;
}

// Получаем новости
$stmt = $pdo->prepare("
    SELECT news.*, news_categories.name AS category_name
    FROM news
    LEFT JOIN news_categories ON news.category_id = news_categories.id
    ORDER BY created_at DESC
");
$stmt->execute();
$news = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Админ панель | Новости</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../styles/style.css">
</head>
<body>

<?php include '../header.php'; ?>

<div class="container mt-5">

<h1 class="mb-4">Админ панель новостей</h1>

<a href="admin_add.php" class="btn btn-success mb-3">Добавить новость</a>

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
<input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
<button class="btn btn-sm btn-danger">Удалить</button>
</form>

</td>
</tr>

<?php endforeach; ?>

</table>

</div>

</body>
</html>