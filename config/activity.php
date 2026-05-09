<?php

function ensureUserAdminColumns(PDO $pdo): void
{
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('role', $columns, true)) {
        $pdo->exec("ALTER TABLE users ADD role VARCHAR(20) NOT NULL DEFAULT 'user'");
    }

    if (!in_array('banned_at', $columns, true)) {
        $pdo->exec("ALTER TABLE users ADD banned_at DATETIME NULL");
    }
}

function ensureActivityLogsTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT NULL,
            description VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
}

function ensureAdminTools(PDO $pdo): void
{
    ensureUserAdminColumns($pdo);
    ensureActivityLogsTable($pdo);
}

function logUserActivity(PDO $pdo, int $userId, string $action, string $entityType, ?int $entityId, string $description): void
{
    ensureActivityLogsTable($pdo);

    $stmt = $pdo->prepare("
        INSERT INTO user_activity_logs (user_id, action, entity_type, entity_id, description)
        VALUES (:user_id, :action, :entity_type, :entity_id, :description)
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':action' => $action,
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':description' => mb_substr($description, 0, 255)
    ]);
}
