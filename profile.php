<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT username, email
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
?>

<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Личный кабинет</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles/style.css">

</head>

<body>

<?php include 'header.php'; ?>

<section class="profile-section">

<div class="container">

<h1 class="mb-4">Личный кабинет</h1>

<?php if (isset($_GET['success'])): ?>

<div class="alert alert-success">
Данные успешно обновлены
</div>

<?php endif; ?>

<?php if (isset($_GET['error'])): ?>

<div class="alert alert-danger">
<?= htmlspecialchars($_GET['error']) ?>
</div>

<?php endif; ?>

<form action="update_profile.php" method="POST">

<div class="card shadow-sm mb-4">

<div class="card-body">

<h4 class="mb-3">Основная информация</h4>

<div class="mb-3">

<label class="form-label">Имя пользователя</label>

<input
type="text"
name="username"
class="form-control"
maxlength="50"
value="<?= htmlspecialchars($user['username']) ?>">

</div>

<div class="mb-3">

<label class="form-label">Email</label>

<input
type="email"
name="email"
class="form-control"
maxlength="100"
value="<?= htmlspecialchars($user['email']) ?>">

</div>

<div class="mb-3">

<label class="form-label">
Введите текущий пароль для подтверждения
</label>

<input
type="password"
name="current_password"
class="form-control">

</div>

<button class="btn btn-dark" name="update_info">
Сохранить изменения
</button>

</div>

</div>

</form>

<form action="update_profile.php" method="POST">

<div class="card shadow-sm">

<div class="card-body">

<h4 class="mb-3">Смена пароля</h4>

<div class="mb-3">

<label class="form-label">Текущий пароль</label>

<input
type="password"
name="old_password"
class="form-control">

</div>

<div class="mb-3">

<label class="form-label">Новый пароль</label>

<input
type="password"
name="new_password"
class="form-control">

</div>

<div class="mb-3">

<label class="form-label">Повторите новый пароль</label>

<input
type="password"
name="confirm_password"
class="form-control">

</div>

<button class="btn btn-danger" name="change_password">
Сменить пароль
</button>

</div>

</div>

</form>

</div>

</section>

<?php include 'footer.php'; ?>

</body>
</html>