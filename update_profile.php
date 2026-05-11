<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE id = :id
");

$stmt->execute([
    ':id' => $userId
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Пользователь не найден");
}

//
// ОБНОВЛЕНИЕ ИМЕНИ И ПОЧТЫ
//

if (isset($_POST['update_info'])) {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';

    if ($username === '' || $email === '') {

        header("Location: profile.php?error=Заполните все поля");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        header("Location: profile.php?error=Некорректный email");
        exit;
    }

    if (!password_verify($currentPassword, $user['password'])) {

        header("Location: profile.php?error=Неверный пароль");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET username = :username,
            email = :email
        WHERE id = :id
    ");

    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':id' => $userId
    ]);

    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;

    header("Location: profile.php?success=1");
    exit;
}

//
// СМЕНА ПАРОЛЯ
//

if (isset($_POST['change_password'])) {

    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (
        $oldPassword === '' ||
        $newPassword === '' ||
        $confirmPassword === ''
    ) {

        header("Location: profile.php?error=Заполните все поля");
        exit;
    }

    if (!password_verify($oldPassword, $user['password'])) {

        header("Location: profile.php?error=Старый пароль неверный");
        exit;
    }

    if (mb_strlen($newPassword) < 6) {

        header("Location: profile.php?error=Пароль слишком короткий");
        exit;
    }

    if ($newPassword !== $confirmPassword) {

        header("Location: profile.php?error=Пароли не совпадают");
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        UPDATE users
        SET password = :password
        WHERE id = :id
    ");

    $stmt->execute([
        ':password' => $newHash,
        ':id' => $userId
    ]);

    header("Location: profile.php?success=1");
    exit;
}

header("Location: profile.php");
exit;