<?php
require_once 'admin_check.php';
require_once '../config/database.php';

$database = new Database();
$pdo = $database->getConnection();
$errors = [];

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Новость не найдена");
}

$stmt = $pdo->prepare("SELECT * FROM news WHERE id = :id");
$stmt->execute([':id' => $id]);
$news = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$news) {
    die("Новость не найдена");
}

$stmt = $pdo->prepare("SELECT * FROM news_categories ORDER BY name ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = $_POST['category'] ?? '';
    $image = $news['preview_img'];

    if ($title === '') {
        $errors[] = "Введите заголовок";
    }

    if ($content === '') {
        $errors[] = "Введите текст новости";
    }

    if (mb_strlen($title) > 255) {
        $errors[] = "Заголовок слишком длинный";
    }

    if (mb_strlen($content) > 5000) {
        $errors[] = "Текст слишком длинный";
    }

    if (!is_numeric($category)) {
        $errors[] = "Некорректная категория";
    }

    if (!empty($_FILES['image']['name'])) {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxFileSize = 5 * 1024 * 1024;
        $tmpName = $_FILES['image']['tmp_name'];
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Ошибка загрузки изображения";
        } elseif (!in_array($extension, $allowedExtensions, true)) {
            $errors[] = "Недопустимый формат изображения";
        } elseif ($_FILES['image']['size'] > $maxFileSize) {
            $errors[] = "Файл слишком большой";
        } elseif (!getimagesize($tmpName)) {
            $errors[] = "Файл не является изображением";
        } else {
            $uploadDir = __DIR__ . '/../images/news/';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                $errors[] = "Не удалось создать папку для изображений";
            } else {
                $image = uniqid('news_', true) . '.' . $extension;
                if (!move_uploaded_file($tmpName, $uploadDir . $image)) {
                    $errors[] = "Не удалось сохранить изображение";
                    $image = $news['preview_img'];
                }
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE news
            SET title = :title, content = :content, category_id = :category, preview_img = :image
            WHERE id = :id
        ");
        $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':category' => $category,
            ':image' => $image,
            ':id' => $id
        ]);

        header("Location: admin_news.php?news_updated=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Редактировать новость</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../styles/style.css">
</head>
<body>

<?php include '../header.php'; ?>

<div class="container mt-5">
<h1 class="mb-4">Редактировать новость</h1>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
<?php foreach ($errors as $error): ?>
<div><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<div class="mb-3">
<input type="text" name="title" class="form-control" placeholder="Заголовок" value="<?= htmlspecialchars($_POST['title'] ?? $news['title']) ?>">
</div>

<div class="mb-3">
<select name="category" class="form-control">
<?php foreach ($categories as $cat): ?>
<option value="<?= $cat['id'] ?>" <?= (($_POST['category'] ?? $news['category_id']) == $cat['id']) ? 'selected' : '' ?>>
<?= htmlspecialchars($cat['name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<?php if (!empty($news['preview_img'])): ?>
<div class="mb-3">
<div class="form-text mb-2">Текущее изображение</div>
<img src="../images/news/<?= htmlspecialchars($news['preview_img']) ?>" class="admin-news-preview" alt="">
</div>
<?php endif; ?>

<div class="mb-3">
<input type="file" name="image" class="form-control" accept="image/*">
<div class="form-text">Оставьте пустым, если изображение менять не нужно</div>
</div>

<div class="mb-3">
<textarea name="content" class="form-control" rows="8" placeholder="Текст новости"><?= htmlspecialchars($_POST['content'] ?? $news['content']) ?></textarea>
</div>

<button class="btn btn-dark">Сохранить</button>
<a href="admin_news.php" class="btn btn-outline-secondary">Назад</a>
</form>
</div>

</body>
</html>
