<?php

function ensureForumPostModerationColumns(PDO $pdo): void
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

    if (!in_array('moderation_status', $columns, true)) {
        $pdo->exec("ALTER TABLE forum_posts ADD moderation_status VARCHAR(20) NOT NULL DEFAULT 'approved' AFTER deleted_at");
    }
}

function ensureNewsCommentModerationColumns(PDO $pdo): void
{
    $columns = $pdo->query("SHOW COLUMNS FROM news_comments")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('parent_id', $columns, true)) {
        $pdo->exec("ALTER TABLE news_comments ADD parent_id INT NULL AFTER news_id");
    }

    if (!in_array('user_id', $columns, true)) {
        $pdo->exec("ALTER TABLE news_comments ADD user_id INT NULL AFTER parent_id");
    }

    if (!in_array('moderation_status', $columns, true)) {
        $pdo->exec("ALTER TABLE news_comments ADD moderation_status VARCHAR(20) NOT NULL DEFAULT 'approved' AFTER comment_text");
    }
}

function moderationStatusLabel(string $status): string
{
    if ($status === 'pending') {
        return 'На проверке';
    }

    if ($status === 'rejected') {
        return 'Не прошло проверку';
    }

    return 'Проверено';
}

function moderationStatusClass(string $status): string
{
    if ($status === 'pending') {
        return 'comment-pending';
    }

    if ($status === 'rejected') {
        return 'comment-rejected';
    }

    return '';
}
