<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Тема не найдена");
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$errors = [];

try {

    // тема
    $stmt = $pdo->prepare("
        SELECT forum_topics.*, forum_categories.title AS category_name
        FROM forum_topics
        LEFT JOIN forum_categories ON forum_topics.category_id = forum_categories.id
        WHERE forum_topics.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$topic) {
        die("Тема не найдена");
    }

    // посты
    $stmt = $pdo->prepare("
        SELECT forum_posts.*, users.username
        FROM forum_posts
        INNER JOIN users ON forum_posts.user_id = users.id
        WHERE topic_id = :id
        ORDER BY is_best DESC, created_at ASC
    ");
    $stmt->execute([':id' => $id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ошибка");
}

// ДОБАВЛЕНИЕ ОТВЕТА
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $message = trim($_POST['message'] ?? '');

    if ($message === '') {
        $errors[] = "Введите сообщение";
    }

    if (mb_strlen($message) > 1000) {
        $errors[] = "Сообщение слишком длинное";
    }

    if (empty($errors)) {

        $userId = $_SESSION['user_id'];

        $stmt = $pdo->prepare("
            INSERT INTO forum_posts (topic_id, message, author_ip, user_id)
            VALUES (:topic_id, :message, :ip, :user_id)
        ");

        $stmt->execute([
            ':topic_id' => $id,
            ':message' => $message,
            ':ip' => $ip,
            ':user_id' => $userId
        ]);

        header("Location: topic_view.php?id=$id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($topic['title']) ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="forum-section">
<div class="container">

<h2 class="mb-4"><?= htmlspecialchars($topic['title']) ?></h2>

<div class="forum-meta mb-4">
Категория: <?= htmlspecialchars($topic['category_name']) ?> • 
<?= date('d.m.Y H:i', strtotime($topic['created_at'])) ?>
</div>

<?php foreach ($posts as $post): ?>

<?php
$stmt = $pdo->prepare("
    SELECT vote_type FROM forum_votes
    WHERE post_id = :post AND voter_ip = :ip
");
$stmt->execute([
    ':post' => $post['id'],
    ':ip' => $ip
]);
$userVote = $stmt->fetchColumn();
?>

<div class="forum-card <?= $post['is_best'] ? 'best-post' : '' ?>">

<div class="forum-meta mb-2">

<?= htmlspecialchars($post['username']) ?> • 
<?= date('d.m.Y H:i', strtotime($post['created_at'])) ?>

</div>

<div class="forum-text">
<?= nl2br(htmlspecialchars($post['message'])) ?>
</div>

<?php if ($_SESSION['role'] === 'admin'): ?>

<a href="best_answer.php?post_id=<?= $post['id'] ?>&topic_id=<?= $id ?>"
class="btn btn-sm btn-outline-success mt-2">
Сделать лучшим
</a>

<?php endif; ?>

<div class="vote-box">

<form action="vote.php" method="POST">
<input type="hidden" name="post_id" value="<?= $post['id'] ?>">
<input type="hidden" name="type" value="up">
<button class="vote-btn up <?= $userVote === 'up' ? 'active' : '' ?>">

<svg width="18" height="18" viewBox="0 0 24 24" fill="none">
<path d="M14 9V5a3 3 0 0 0-6 0v4H5v11h11l3-7v-4h-5z" stroke="currentColor" stroke-width="2"/>
</svg>

</button>
</form>

<span class="vote-count"><?= $post['likes'] ?></span>

<form action="vote.php" method="POST">
<input type="hidden" name="post_id" value="<?= $post['id'] ?>">
<input type="hidden" name="type" value="down">
<button class="vote-btn down <?= $userVote === 'down' ? 'active' : '' ?>">

<svg width="18" height="18" viewBox="0 0 24 24" fill="none">
<path d="M10 15v4a3 3 0 0 0 6 0v-4h3V4H8L5 11v4h5z" stroke="currentColor" stroke-width="2"/>
</svg>

</button>
</form>

<span class="vote-count"><?= $post['dislikes'] ?></span>

</div>

</div>

<?php endforeach; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mt-4">
<?php foreach ($errors as $e): ?>
<div><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" class="mt-4">

<div class="mb-3">
<textarea name="message" class="form-control" rows="4"
placeholder="Ваш ответ"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
</div>

<button class="btn btn-dark">Ответить</button>

</form>

</div>
</section>

<?php include 'footer.php'; ?>

</body>
</html>