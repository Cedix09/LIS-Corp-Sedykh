<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$stats = [];
$gallery = [];

try {

    $stmt = $pdo->query("SELECT * FROM company_stats ORDER BY sort_order ASC");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM gallery LIMIT 10");
    $gallery = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $stats = [];
    $gallery = [];

}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LIS Corp</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
<div class="container">
<a class="navbar-brand fw-bold" href="index.php">
LIS
</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="mainNavbar">
<ul class="navbar-nav me-auto">
<li class="nav-item">
<a class="nav-link active" href="index.php">Главная</a>
</li>
<li class="nav-item">
<a class="nav-link" href="about.php">О компании</a>
</li>
<li class="nav-item">
<a class="nav-link" href="news.php">Новости</a>
</li>
<li class="nav-item">
<a class="nav-link" href="forum.php">Форум</a>
</li>
<li class="nav-item">
<a class="nav-link" href="contacts.php">Контакты</a>
</li>
</ul>
<div class="d-flex align-items-center text-white">
<span class="me-3">
<?= htmlspecialchars($_SESSION['username']) ?>
</span>
<a href="logout.php" class="btn btn-outline-light btn-sm">
Выйти
</a>
</div>
</div>
</div>
</nav>
<header class="hero-banner">
<div class="hero-overlay">
<div class="hero-content">
<h1>LIS Corp</h1>
<p>
Глобальная промышленная корпорация, специализирующаяся на добыче меди и развитии инфраструктуры ядерной энергетики.
</p>
</div>
</div>
</header>
<div class="container mt-5">
<h2 class="text-center mb-4">О компании</h2>
<p class="text-center">
LIS Corp — международная промышленная корпорация,
специализирующаяся на добыче меди и развитии инфраструктуры
атомной энергетики.
</p>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>