<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$errors = [];
$success = '';

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// категории
$stmt = $pdo->prepare("SELECT * FROM forum_categories");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';

    // 🔴 ВАЛИДАЦИЯ
    if ($title === '') {
        $errors[] = "Введите название темы";
    }

    if (mb_strlen($title) > 255) {
        $errors[] = "Название слишком длинное";
    }

    if (!is_numeric($category)) {
        $errors[] = "Некорректная категория";
    }

    // 🔥 АНТИСПАМ (5 тем в день)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM forum_topics
        WHERE author_ip = :ip
        AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute([':ip' => $ip]);
    $count = $stmt->fetchColumn();

    if ($count >= 5) {
        $errors[] = "Вы достигли лимита (5 тем в день)";
    }

    // ✔ если всё ок
    if (empty($errors)) {

        $stmt = $pdo->prepare("
            INSERT INTO forum_topics (title, category_id, author_ip)
            VALUES (:title, :category, :ip)
        ");

        $stmt->execute([
            ':title' => $title,
            ':category' => $category,
            ':ip' => $ip
        ]);

        header("Location: forum.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Создать тему</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="create-topic-section">
<div class="container">

<h1 class="mb-4">Создать тему</h1>

<!-- ошибки -->
<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
<?php foreach ($errors as $e): ?>
<div><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="mb-3">
<input type="text" name="title" class="form-control"
placeholder="Название темы"
value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
</div>

<div class="mb-3">
<select name="category" class="form-control">

<?php foreach ($categories as $cat): ?>
<option value="<?= $cat['id'] ?>"
<?= (($_POST['category'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
<?= htmlspecialchars($cat['title']) ?>
</option>
<?php endforeach; ?>

</select>
</div>

<button class="btn btn-dark">Создать</button>

</form>

</div>
</section>

<?php include 'footer.php'; ?>

</body>
</html>