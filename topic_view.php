<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Тема не найдена");
}

$id = (int) $id;
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$errors = [];

function ensureForumPostColumns(PDO $pdo): void
{
    $columns = $pdo->query("SHOW COLUMNS FROM forum_posts")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('parent_id', $columns, true)) {
        $pdo->exec("ALTER TABLE forum_posts ADD parent_id INT NULL AFTER topic_id");
    }

    if (!in_array('edited_at', $columns, true)) {
        $pdo->exec("ALTER TABLE forum_posts ADD edited_at DATETIME NULL AFTER created_at");
    }

    if (!in_array('deleted_at', $columns, true)) {
        $pdo->exec("ALTER TABLE forum_posts ADD deleted_at DATETIME NULL AFTER edited_at");
    }
}

function redirectToTopic(int $topicId): void
{
    header("Location: topic_view.php?id=$topicId");
    exit;
}

try {
    ensureForumPostColumns($pdo);

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
} catch (PDOException $e) {
    die("Ошибка");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $userId = (int) $_SESSION['user_id'];

    if ($action === 'delete') {
        $postId = (int) ($_POST['post_id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT id, parent_id FROM forum_posts
            WHERE id = :post_id AND topic_id = :topic_id AND user_id = :user_id
        ");
        $stmt->execute([
            ':post_id' => $postId,
            ':topic_id' => $id,
            ':user_id' => $userId
        ]);
        $postForDelete = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($postForDelete) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM forum_posts WHERE parent_id = :post_id");
            $stmt->execute([':post_id' => $postId]);
            $hasReplies = (int) $stmt->fetchColumn() > 0;

            if ($hasReplies) {
                $stmt = $pdo->prepare("
                    UPDATE forum_posts
                    SET message = '', deleted_at = NOW()
                    WHERE id = :post_id
                ");
                $stmt->execute([':post_id' => $postId]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM forum_votes WHERE post_id = :post_id");
                $stmt->execute([':post_id' => $postId]);

                $stmt = $pdo->prepare("DELETE FROM forum_posts WHERE id = :post_id");
                $stmt->execute([':post_id' => $postId]);
            }
        }

        redirectToTopic($id);
    }

    if ($action === 'edit') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if ($message === '') {
            $errors[] = "Введите сообщение";
        }

        if (mb_strlen($message) > 1000) {
            $errors[] = "Сообщение слишком длинное";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                UPDATE forum_posts
                SET message = :message, edited_at = NOW()
                WHERE id = :id AND topic_id = :topic_id AND user_id = :user_id
            ");
            $stmt->execute([
                ':message' => $message,
                ':id' => $postId,
                ':topic_id' => $id,
                ':user_id' => $userId
            ]);

            redirectToTopic($id);
        }
    }

    if ($action === 'create' || $action === 'reply') {
        $message = trim($_POST['message'] ?? '');
        $parentId = $action === 'reply' ? (int) ($_POST['parent_id'] ?? 0) : null;

        if ($message === '') {
            $errors[] = "Введите сообщение";
        }

        if (mb_strlen($message) > 1000) {
            $errors[] = "Сообщение слишком длинное";
        }

        if ($parentId) {
            $stmt = $pdo->prepare("
                SELECT id FROM forum_posts
                WHERE id = :id AND topic_id = :topic_id AND parent_id IS NULL
            ");
            $stmt->execute([
                ':id' => $parentId,
                ':topic_id' => $id
            ]);

            if (!$stmt->fetchColumn()) {
                $errors[] = "Комментарий для ответа не найден";
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO forum_posts (topic_id, parent_id, message, author_ip, user_id)
                VALUES (:topic_id, :parent_id, :message, :ip, :user_id)
            ");

            $stmt->execute([
                ':topic_id' => $id,
                ':parent_id' => $parentId,
                ':message' => $message,
                ':ip' => $ip,
                ':user_id' => $userId
            ]);

            redirectToTopic($id);
        }
    }
}

try {
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

$postsByParent = [];
foreach ($posts as $post) {
    $parentKey = $post['parent_id'] ?? 0;
    $postsByParent[$parentKey][] = $post;
}

function renderForumPost(array $post, array $postsByParent, PDO $pdo, string $ip, int $topicId, int $userId, bool $isReply = false): void
{
    $stmt = $pdo->prepare("
        SELECT vote_type FROM forum_votes
        WHERE post_id = :post AND voter_ip = :ip
    ");
    $stmt->execute([
        ':post' => $post['id'],
        ':ip' => $ip
    ]);
    $userVote = $stmt->fetchColumn();
    $isDeleted = !empty($post['deleted_at']);
    $canManage = !$isDeleted && (int) $post['user_id'] === $userId;
    ?>

    <div class="forum-card <?= $post['is_best'] ? 'best-post' : '' ?> <?= $isReply ? 'forum-reply' : '' ?>">

        <div class="forum-meta mb-2">
            <?= $isDeleted ? 'Комментарий удален' : htmlspecialchars($post['username']) ?> •
            <?= date('d.m.Y H:i', strtotime($post['created_at'])) ?>
            <?php if (!$isDeleted && !empty($post['edited_at'])): ?>
                <span class="comment-edited">изменено <?= date('d.m.Y H:i', strtotime($post['edited_at'])) ?></span>
            <?php endif; ?>
        </div>

        <div class="forum-text">
            <?= $isDeleted ? '<em>Текст комментария удален автором.</em>' : nl2br(htmlspecialchars($post['message'])) ?>
        </div>

        <?php if (!$isDeleted): ?>
        <div class="comment-actions">
            <?php if (!$isReply): ?>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#reply-<?= $post['id'] ?>">
                    Ответить
                </button>
            <?php endif; ?>

            <?php if ($canManage): ?>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-<?= $post['id'] ?>">
                    Редактировать
                </button>

                <form method="POST" onsubmit="return confirm('Удалить комментарий?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Удалить</button>
                </form>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'admin' && !$isReply): ?>
                <a href="best_answer.php?post_id=<?= $post['id'] ?>&topic_id=<?= $topicId ?>" class="btn btn-sm btn-outline-success">
                    Сделать лучшим
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!$isDeleted && !$isReply): ?>
            <div class="collapse mt-3" id="reply-<?= $post['id'] ?>">
                <form method="POST">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="parent_id" value="<?= $post['id'] ?>">
                    <textarea name="message" class="form-control mb-2" rows="3" maxlength="1000" placeholder="Ваш ответ" required></textarea>
                    <button class="btn btn-dark btn-sm">Отправить</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!$isDeleted && $canManage): ?>
            <div class="collapse mt-3" id="edit-<?= $post['id'] ?>">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <textarea name="message" class="form-control mb-2" rows="3" maxlength="1000" required><?= htmlspecialchars($post['message']) ?></textarea>
                    <button class="btn btn-dark btn-sm">Сохранить</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!$isDeleted): ?>
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
        <?php endif; ?>

    </div>

    <?php foreach ($postsByParent[$post['id']] ?? [] as $reply): ?>
        <?php renderForumPost($reply, $postsByParent, $pdo, $ip, $topicId, $userId, true); ?>
    <?php endforeach; ?>
    <?php
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

<?php foreach ($postsByParent[0] ?? [] as $post): ?>
    <?php renderForumPost($post, $postsByParent, $pdo, $ip, $id, (int) $_SESSION['user_id']); ?>
<?php endforeach; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mt-4">
<?php foreach ($errors as $e): ?>
<div><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" class="mt-4">
<input type="hidden" name="action" value="create">

<div class="mb-3">
<textarea name="message" class="form-control" rows="4" maxlength="1000"
placeholder="Ваш ответ"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
</div>

<button class="btn btn-dark">Ответить</button>

</form>

</div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
