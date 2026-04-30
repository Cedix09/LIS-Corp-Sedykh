<?php
require_once 'admin_check.php';
require_once '../config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$errors = [];

// Получаем категории
$stmt = $pdo->prepare("SELECT * FROM news_categories");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = $_POST['category'] ?? '';
    $image = trim($_POST['image'] ?? '');

    // Пустые поля
    if ($title === '') {
        $errors[] = "Введите заголовок";
    }

    if ($content === '') {
        $errors[] = "Введите текст новости";
    }

    // Длина
    if (mb_strlen($title) > 255) {
        $errors[] = "Заголовок слишком длинный (макс. 255)";
    }

    if (mb_strlen($content) > 5000) {
        $errors[] = "Текст слишком длинный";
    }

    if ($image && mb_strlen($image) > 255) {
        $errors[] = "Имя файла слишком длинное";
    }

    // Категория
    if (!is_numeric($category)) {
        $errors[] = "Некорректная категория";
    }

    // Если ошибок нет → сохраняем
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

        <!-- 🔴 ВЫВОД ОШИБОК -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): ?>
                    <div><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

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
                <input type="text" name="image" class="form-control" placeholder="example.jpg"
                    value="<?= htmlspecialchars($_POST['image'] ?? '') ?>">
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