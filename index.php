<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Главная</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand">Мой проект</span>
        <span class="text-white">
            Привет, <?= htmlspecialchars($_SESSION['username']) ?>!
            <a href="logout.php" class="btn btn-outline-light btn-sm ms-2">Выйти</a>
        </span>
    </div>
</nav>
<div class="container mt-4">
    <h1>Добро пожаловать, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
    <p>Вы успешно вошли в систему.</p>
</div>
</body></html>
