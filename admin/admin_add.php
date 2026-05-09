<?php
require_once 'admin_check.php';
require_once '../config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$errors = [];

$stmt = $pdo->prepare("SELECT * FROM news_categories");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = $_POST['category'] ?? '';
    $image = '';

    if ($title === '') {
        $errors[] = "Введите заголовок";
    }

    if ($content === '') {
        $errors[] = "Введите текст новости";
    }

    if (mb_strlen($title) > 255) {
        $errors[] = "Заголовок слишком длинный (макс. 255)";
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
        $fileError = $_FILES['image']['error'];
        $fileSize = $_FILES['image']['size'];
        $tmpName = $_FILES['image']['tmp_name'];
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "Ошибка загрузки изображения";
        } elseif (!in_array($extension, $allowedExtensions, true)) {
            $errors[] = "Недопустимый формат изображения";
        } elseif ($fileSize > $maxFileSize) {
            $errors[] = "Файл слишком большой";
        } elseif (!getimagesize($tmpName)) {
            $errors[] = "Файл не является изображением";
        } else {
            $uploadDir = __DIR__ . '/../images/news/';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                $errors[] = "Не удалось создать папку для изображений";
            } else {
                $image = uniqid('news_', true) . '.' . $extension;
                $targetPath = $uploadDir . $image;

                if (!move_uploaded_file($tmpName, $targetPath)) {
                    $errors[] = "Не удалось сохранить изображение";
                    $image = '';
                }
            }
        }
    }

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            INSERT INTO news (title, content, category_id, preview_img)
            VALUES (:title, :content, :category, :image)
        ");

        $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':category' => $category,
            ':image' => $image
        ]);

        header("Location: admin_news.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Добавить новость</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/style.css">
</head>

<body>

    <?php include '../header.php'; ?>

    <div class="container mt-5">

        <h1 class="mb-4">Добавить новость</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): ?>
                    <div><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <input type="text" name="title" class="form-control" placeholder="Заголовок"
                    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <select name="category" class="form-control">

                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (($_POST['category'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="mb-3">
                <input type="file" name="image" class="form-control" accept="image/*">
                <div class="form-text">JPG, PNG, GIF или WebP до 5 МБ</div>
            </div>

            <div class="mb-3">
                <textarea name="content" class="form-control" rows="6"
                    placeholder="Текст новости"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
            </div>

            <button class="btn btn-dark">Добавить</button>

        </form>

    </div>

</body>

</html>
