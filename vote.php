<?php
require_once 'config/database.php';
require_once 'config/moderation.php';

$database = new Database();
$pdo = $database->getConnection();

$post_id = $_POST['post_id'] ?? null;
$type = $_POST['type'] ?? null;

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!$post_id || !is_numeric($post_id) || !in_array($type, ['up','down'])) {
    die("Ошибка");
}

try {
    ensureForumPostModerationColumns($pdo);

    $stmt = $pdo->prepare("
        SELECT moderation_status FROM forum_posts
        WHERE id = :post_id
    ");
    $stmt->execute([':post_id' => $post_id]);
    $status = $stmt->fetchColumn();

    if ($status !== 'approved') {
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'forum.php'));
        exit;
    }

    // ищем существующий голос
    $stmt = $pdo->prepare("
        SELECT * FROM forum_votes
        WHERE post_id = :post_id AND voter_ip = :ip
    ");
    $stmt->execute([
        ':post_id' => $post_id,
        ':ip' => $ip
    ]);

    $vote = $stmt->fetch(PDO::FETCH_ASSOC);

    // 🔥 ЕСЛИ УЖЕ ГОЛОСОВАЛ
    if ($vote) {

        // 1. нажал ту же кнопку → убрать голос
        if ($vote['vote_type'] === $type) {

            if ($type === 'up') {
                $pdo->prepare("UPDATE forum_posts SET likes = likes - 1 WHERE id = :id")
                    ->execute([':id' => $post_id]);
            } else {
                $pdo->prepare("UPDATE forum_posts SET dislikes = dislikes - 1 WHERE id = :id")
                    ->execute([':id' => $post_id]);
            }

            $pdo->prepare("DELETE FROM forum_votes WHERE id = :id")
                ->execute([':id' => $vote['id']]);
        }

        // 2. нажал противоположную кнопку → переключить
        else {

            if ($vote['vote_type'] === 'up') {
                $pdo->prepare("UPDATE forum_posts SET likes = likes - 1 WHERE id = :id")
                    ->execute([':id' => $post_id]);

                $pdo->prepare("UPDATE forum_posts SET dislikes = dislikes + 1 WHERE id = :id")
                    ->execute([':id' => $post_id]);
            } else {
                $pdo->prepare("UPDATE forum_posts SET dislikes = dislikes - 1 WHERE id = :id")
                    ->execute([':id' => $post_id]);

                $pdo->prepare("UPDATE forum_posts SET likes = likes + 1 WHERE id = :id")
                    ->execute([':id' => $post_id]);
            }

            $pdo->prepare("
                UPDATE forum_votes SET vote_type = :type WHERE id = :id
            ")->execute([
                ':type' => $type,
                ':id' => $vote['id']
            ]);
        }

    }

    // 🔥 ЕСЛИ ВПЕРВЫЕ ГОЛОСУЕТ
    else {

        $pdo->prepare("
            INSERT INTO forum_votes (post_id, voter_ip, vote_type)
            VALUES (:post_id, :ip, :type)
        ")->execute([
            ':post_id' => $post_id,
            ':ip' => $ip,
            ':type' => $type
        ]);

        if ($type === 'up') {
            $pdo->prepare("UPDATE forum_posts SET likes = likes + 1 WHERE id = :id")
                ->execute([':id' => $post_id]);
        } else {
            $pdo->prepare("UPDATE forum_posts SET dislikes = dislikes + 1 WHERE id = :id")
                ->execute([':id' => $post_id]);
        }
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;

} catch (PDOException $e) {
    die("Ошибка сервера");
}
