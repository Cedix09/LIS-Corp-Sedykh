<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/activity.php';

$database = new Database();
$pdo = $database->getConnection();
ensureAdminTools($pdo);

$stmt = $pdo->prepare("SELECT banned_at, role FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || !empty($currentUser['banned_at'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['role'] = $currentUser['role'];
?>
