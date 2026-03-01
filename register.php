<?php
require_once 'config/database.php';
require_once 'models/User.php';

$database = new Database();
$pdo = $database->getConnection();
$userModel = new User($pdo);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    // Валидация
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Заполните все поля!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email!';
    } elseif (mb_strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов!';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают!';
    } elseif ($userModel->emailExists($email)) {
        $error = 'Пользователь с таким email уже зарегистрирован!';
    } else {
        // Всё ОК — регистрируем
        $result = $userModel->register($username, $email, $password);
        if ($result) {
            $message = 'Регистрация успешна! Теперь вы можете войти.';
        } else {
            $error = 'Ошибка при регистрации. Попробуйте ещё раз.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Регистрация</h2>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                            <form method="POST">
                                <div class="form-floating mb-3">
                                     <input type="text" class="form-control" id="username"
                                        name="username" placeholder="Имя" required>
                                <label for="username">👤 Имя пользователя</label>
                                </div>
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email"
                                    name="email" placeholder="Email" required>
                                <label for="email">📧 Email адрес</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password"
                                    name="password" placeholder="Пароль" required minlength="6">
                                <label for="password">🔒 Пароль</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="confirm"
                                name="confirm" placeholder="Повтор" required>
                                <label for="confirm">🔒 Повторите пароль</label>
                                 </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Зарегистрироваться
                        </button>
                    </form>

                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1">
                        <span class="mx-2 text-muted">или</span>
                        <hr class="flex-grow-1">
                    </div>
                    <a href="login.php" class="btn btn-outline-secondary w-100">Войти в существующий аккаунт</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
