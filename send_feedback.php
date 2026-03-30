<?php
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$name = trim($_POST['user_name'] ?? '');
$email = trim($_POST['user_email'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];

if (empty($name)) {
    $errors[] = "Введите имя";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Некорректный email";
}

if (empty($message)) {
    $errors[] = "Введите сообщение";
}

if (count($errors) > 0) {
    $error_string = urlencode(implode('|', $errors));
    header("Location: feedback.php?error=$error_string");
    exit;
}

if (mb_strlen($name) > 100) {
    $errors[] = "Имя слишком длинное (макс. 100 символов)";
}

if (mb_strlen($email) > 100) {
    $errors[] = "Email слишком длинный";
}

if (mb_strlen($message) > 1000) {
    $errors[] = "Сообщение слишком длинное (макс. 1000 символов)";
}

try {

    $stmt = $pdo->prepare("
        INSERT INTO feedback (user_name, user_email, message)
        VALUES (:name, :email, :message)
    ");

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':message' => $message
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());
    header("Location: feedback.php?error=" . urlencode("Внутренняя ошибка сервера"));
    exit;
}
header("Location: feedback.php?success=1");
exit;