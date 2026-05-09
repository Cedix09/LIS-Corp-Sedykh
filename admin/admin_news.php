<?php
require_once 'admin_check.php';
require_once '../config/database.php';
require_once '../config/activity.php';
require_once '../config/moderation.php';

$database = new Database();
$pdo = $database->getConnection();
$errors = [];

ensureAdminTools($pdo);
ensureForumPostModerationColumns($pdo);
ensureNewsCommentModerationColumns($pdo);

function redirectAdmin(string $query = ''): void
{
    header('Location: admin_news.php' . $query);
    exit;
}

function updateCategory(PDO $pdo, string $table, string $field, string $redirectKey): void
{
    $id = $_POST['category_id'] ?? null;
    $name = trim($_POST['category_name'] ?? '');

    if ($id && is_numeric($id) && $name !== '' && mb_strlen($name) <= 100) {
        $stmt = $pdo->prepare("UPDATE $table SET $field = :name WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':id' => $id
        ]);
    }

    redirectAdmin("?$redirectKey=1");
}

function addCategory(PDO $pdo, string $table, string $field, string $redirectKey): void
{
    $name = trim($_POST['category_name'] ?? '');

    if ($name !== '' && mb_strlen($name) <= 100) {
        $stmt = $pdo->prepare("INSERT INTO $table ($field) VALUES (:name)");
        $stmt->execute([':name' => $name]);
    }

    redirectAdmin("?$redirectKey=1");
}

function deleteCategory(PDO $pdo, string $table, int $id): void
{
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (isset($_POST['delete_news_id']) && is_numeric($_POST['delete_news_id'])) {
        $stmt = $pdo->prepare("DELETE FROM news WHERE id = :id");
        $stmt->execute([':id' => $_POST['delete_news_id']]);
        redirectAdmin('?news_deleted=1');
    }

    if (isset($_POST['delete_topic_id']) && is_numeric($_POST['delete_topic_id'])) {
        $id = $_POST['delete_topic_id'];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id FROM forum_posts WHERE topic_id = :id");
            $stmt->execute([':id' => $id]);
            $postIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if ($postIds) {
                $placeholders = implode(',', array_fill(0, count($postIds), '?'));
                $stmt = $pdo->prepare("DELETE FROM forum_votes WHERE post_id IN ($placeholders)");
                $stmt->execute($postIds);
            }

            $stmt = $pdo->prepare("DELETE FROM forum_posts WHERE topic_id = :id");
            $stmt->execute([':id' => $id]);

            $stmt = $pdo->prepare("DELETE FROM forum_topics WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $pdo->commit();
            redirectAdmin('?topic_deleted=1');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Не удалось удалить тему форума";
        }
    }

    if ($action === 'add_news_category') {
        addCategory($pdo, 'news_categories', 'name', 'category_updated');
    }

    if ($action === 'update_news_category') {
        updateCategory($pdo, 'news_categories', 'name', 'category_updated');
    }

    if ($action === 'delete_news_category' && is_numeric($_POST['category_id'] ?? null)) {
        try {
            deleteCategory($pdo, 'news_categories', (int) $_POST['category_id']);
            redirectAdmin('?category_updated=1');
        } catch (PDOException $e) {
            $errors[] = "Категорию новостей нельзя удалить, пока в ней есть новости";
        }
    }

    if ($action === 'add_forum_category') {
        addCategory($pdo, 'forum_categories', 'title', 'category_updated');
    }

    if ($action === 'update_forum_category') {
        updateCategory($pdo, 'forum_categories', 'title', 'category_updated');
    }

    if ($action === 'delete_forum_category' && is_numeric($_POST['category_id'] ?? null)) {
        try {
            deleteCategory($pdo, 'forum_categories', (int) $_POST['category_id']);
            redirectAdmin('?category_updated=1');
        } catch (PDOException $e) {
            $errors[] = "Категорию форума нельзя удалить, пока в ней есть темы";
        }
    }

    if (isset($_POST['user_action'], $_POST['user_id'])) {
        $targetUserId = (int) $_POST['user_id'];
        $userAction = $_POST['user_action'];

        if ($targetUserId === (int) $_SESSION['user_id']) {
            $errors[] = "Нельзя менять роль или банить свой аккаунт";
        } elseif ($userAction === 'change_role') {
            $role = $_POST['role'] ?? 'user';

            if (in_array($role, ['user', 'moder', 'admin'], true)) {
                $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
                $stmt->execute([
                    ':role' => $role,
                    ':id' => $targetUserId
                ]);

                redirectAdmin('?user_updated=1');
            }

            $errors[] = "Некорректная роль";
        } elseif ($userAction === 'ban') {
            $stmt = $pdo->prepare("UPDATE users SET banned_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $targetUserId]);
            redirectAdmin('?user_updated=1');
        } elseif ($userAction === 'unban') {
            $stmt = $pdo->prepare("UPDATE users SET banned_at = NULL WHERE id = :id");
            $stmt->execute([':id' => $targetUserId]);
            redirectAdmin('?user_updated=1');
        }
    }
}

$dashboard = [
    'news' => (int) $pdo->query("SELECT COUNT(*) FROM news")->fetchColumn(),
    'topics' => (int) $pdo->query("SELECT COUNT(*) FROM forum_topics")->fetchColumn(),
    'users' => (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'pending' => (int) $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM forum_posts WHERE moderation_status = 'pending') +
            (SELECT COUNT(*) FROM news_comments WHERE moderation_status = 'pending')
    ")->fetchColumn(),
];

$stmt = $pdo->prepare("
    SELECT news.*, news_categories.name AS category_name
    FROM news
    LEFT JOIN news_categories ON news.category_id = news_categories.id
    ORDER BY created_at DESC
");
$stmt->execute();
$news = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT forum_topics.*, forum_categories.title AS category_name,
        (SELECT COUNT(*) FROM forum_posts WHERE forum_posts.topic_id = forum_topics.id) AS replies
    FROM forum_topics
    LEFT JOIN forum_categories ON forum_topics.category_id = forum_categories.id
    ORDER BY forum_topics.created_at DESC
");
$stmt->execute();
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

$newsCategories = $pdo->query("SELECT * FROM news_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$forumCategories = $pdo->query("SELECT * FROM forum_categories ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, username, email, role, banned_at FROM users ORDER BY id ASC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM user_activity_logs ORDER BY created_at DESC");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$logsByUser = [];
foreach ($logs as $log) {
    $logsByUser[$log['user_id']][] = $log;
}

$recentLogs = array_slice($logs, 0, 8);

function roleLabel(string $role): string
{
    return $role === 'admin' ? 'Админ' : ($role === 'moder' ? 'Модератор' : 'Пользователь');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Админ панель</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../styles/style.css">
</head>
<body>

<?php include '../header.php'; ?>

<div class="container mt-5">

<h1 class="mb-4">Админ панель</h1>

<div class="mb-3">
<a href="admin_add.php" class="btn btn-success">Добавить новость</a>
</div>

<?php foreach (['news_deleted' => 'Новость удалена', 'news_updated' => 'Новость обновлена', 'topic_deleted' => 'Тема форума удалена', 'user_updated' => 'Пользователь обновлен', 'category_updated' => 'Категории обновлены'] as $key => $message): ?>
<?php if (isset($_GET[$key])): ?>
<div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>
<?php endforeach; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
<?php foreach ($errors as $error): ?>
<div><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<section class="admin-dashboard">
<div class="admin-stat"><span><?= $dashboard['news'] ?></span><p>Новостей</p></div>
<div class="admin-stat"><span><?= $dashboard['topics'] ?></span><p>Тем форума</p></div>
<div class="admin-stat"><span><?= $dashboard['users'] ?></span><p>Пользователей</p></div>
<div class="admin-stat"><span><?= $dashboard['pending'] ?></span><p>На модерации</p></div>
</section>

<h2 class="h4 mt-5 mb-3">Последние действия</h2>
<table class="table table-bordered table-sm align-middle">
<tr><th>Дата</th><th>Пользователь</th><th>Действие</th><th>Описание</th></tr>
<?php foreach ($recentLogs as $log): ?>
<tr>
<td><?= date('d.m.Y H:i', strtotime($log['created_at'])) ?></td>
<td>#<?= htmlspecialchars((string) $log['user_id']) ?></td>
<td><?= htmlspecialchars($log['action']) ?></td>
<td><?= htmlspecialchars($log['description']) ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$recentLogs): ?>
<tr><td colspan="4" class="text-center">Действий пока нет</td></tr>
<?php endif; ?>
</table>

<h2 class="h4 mt-5 mb-3">Новости</h2>
<table class="table table-bordered align-middle">
<tr><th>ID</th><th>Заголовок</th><th>Категория</th><th>Дата</th><th>Действия</th></tr>
<?php foreach ($news as $item): ?>
<tr>
<td><?= $item['id'] ?></td>
<td><?= htmlspecialchars($item['title']) ?></td>
<td><?= htmlspecialchars($item['category_name'] ?? '') ?></td>
<td><?= $item['created_at'] ?></td>
<td>
<a href="../view_news.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">Открыть</a>
<a href="admin_edit.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-secondary">Редактировать</a>
<form method="POST" style="display:inline;" onsubmit="return confirm('Удалить новость?');">
<input type="hidden" name="delete_news_id" value="<?= $item['id'] ?>">
<button class="btn btn-sm btn-danger">Удалить</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>

<h2 class="h4 mt-5 mb-3">Темы форума</h2>
<table class="table table-bordered align-middle">
<tr><th>ID</th><th>Тема</th><th>Категория</th><th>Ответов</th><th>Дата</th><th>Действия</th></tr>
<?php foreach ($topics as $topic): ?>
<tr>
<td><?= $topic['id'] ?></td>
<td><?= htmlspecialchars($topic['title']) ?></td>
<td><?= htmlspecialchars($topic['category_name'] ?? '') ?></td>
<td><?= $topic['replies'] ?></td>
<td><?= $topic['created_at'] ?></td>
<td>
<a href="../topic_view.php?id=<?= $topic['id'] ?>" class="btn btn-sm btn-primary">Открыть</a>
<form method="POST" style="display:inline;" onsubmit="return confirm('Удалить тему и все ее сообщения?');">
<input type="hidden" name="delete_topic_id" value="<?= $topic['id'] ?>">
<button class="btn btn-sm btn-danger">Удалить</button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php if (!$topics): ?>
<tr><td colspan="6" class="text-center">Тем пока нет</td></tr>
<?php endif; ?>
</table>

<h2 class="h4 mt-5 mb-3">Категории</h2>
<div class="row g-4">
<div class="col-md-6">
<h3 class="h5">Категории новостей</h3>
<form method="POST" class="admin-inline-form mb-3">
<input type="hidden" name="action" value="add_news_category">
<input type="text" name="category_name" class="form-control" placeholder="Новая категория" required>
<button class="btn btn-sm btn-success">Добавить</button>
</form>
<?php foreach ($newsCategories as $category): ?>
<form method="POST" class="admin-inline-form mb-2">
<input type="hidden" name="category_id" value="<?= $category['id'] ?>">
<input type="text" name="category_name" class="form-control" value="<?= htmlspecialchars($category['name']) ?>" required>
<button name="action" value="update_news_category" class="btn btn-sm btn-outline-secondary">Сохранить</button>
<button name="action" value="delete_news_category" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить категорию?');">Удалить</button>
</form>
<?php endforeach; ?>
</div>
<div class="col-md-6">
<h3 class="h5">Категории форума</h3>
<form method="POST" class="admin-inline-form mb-3">
<input type="hidden" name="action" value="add_forum_category">
<input type="text" name="category_name" class="form-control" placeholder="Новая категория" required>
<button class="btn btn-sm btn-success">Добавить</button>
</form>
<?php foreach ($forumCategories as $category): ?>
<form method="POST" class="admin-inline-form mb-2">
<input type="hidden" name="category_id" value="<?= $category['id'] ?>">
<input type="text" name="category_name" class="form-control" value="<?= htmlspecialchars($category['title']) ?>" required>
<button name="action" value="update_forum_category" class="btn btn-sm btn-outline-secondary">Сохранить</button>
<button name="action" value="delete_forum_category" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить категорию?');">Удалить</button>
</form>
<?php endforeach; ?>
</div>
</div>

<h2 class="h4 mt-5 mb-3">Пользователи</h2>
<table class="table table-bordered align-middle admin-users-table">
<tr><th>ID</th><th>Пользователь</th><th>Email</th><th>Роль</th><th>Статус</th><th>Действия</th></tr>
<?php foreach ($users as $user): ?>
<?php $logsForUser = $logsByUser[$user['id']] ?? []; ?>
<tr>
<td><?= $user['id'] ?></td>
<td><?= htmlspecialchars($user['username']) ?></td>
<td><?= htmlspecialchars($user['email'] ?? '') ?></td>
<td><?= roleLabel($user['role'] ?? 'user') ?></td>
<td><?= empty($user['banned_at']) ? 'Активен' : 'Забанен' ?></td>
<td>
<button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#user-logs-<?= $user['id'] ?>">Логи</button>
<form method="POST" class="admin-user-action">
<input type="hidden" name="user_action" value="change_role">
<input type="hidden" name="user_id" value="<?= $user['id'] ?>">
<select name="role" class="form-select form-select-sm">
<option value="user" <?= ($user['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>Пользователь</option>
<option value="moder" <?= ($user['role'] ?? '') === 'moder' ? 'selected' : '' ?>>Модератор</option>
<option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Админ</option>
</select>
<button class="btn btn-sm btn-outline-secondary">Сменить роль</button>
</form>
<?php if (empty($user['banned_at'])): ?>
<form method="POST" class="admin-user-action" onsubmit="return confirm('Забанить пользователя?');">
<input type="hidden" name="user_action" value="ban">
<input type="hidden" name="user_id" value="<?= $user['id'] ?>">
<button class="btn btn-sm btn-outline-danger">Забанить</button>
</form>
<?php else: ?>
<form method="POST" class="admin-user-action">
<input type="hidden" name="user_action" value="unban">
<input type="hidden" name="user_id" value="<?= $user['id'] ?>">
<button class="btn btn-sm btn-outline-success">Разбанить</button>
</form>
<?php endif; ?>
</td>
</tr>
<tr class="collapse" id="user-logs-<?= $user['id'] ?>">
<td colspan="6">
<?php if ($logsForUser): ?>
<table class="table table-sm mb-0">
<tr><th>Дата</th><th>Действие</th><th>Объект</th><th>Описание</th></tr>
<?php foreach ($logsForUser as $log): ?>
<tr>
<td><?= date('d.m.Y H:i', strtotime($log['created_at'])) ?></td>
<td><?= htmlspecialchars($log['action']) ?></td>
<td><?= htmlspecialchars($log['entity_type']) ?> #<?= htmlspecialchars((string) $log['entity_id']) ?></td>
<td><?= htmlspecialchars($log['description']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<div class="text-muted">Действий пока нет</div>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
