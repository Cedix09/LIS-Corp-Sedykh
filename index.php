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
<?php include 'header.php'; ?>
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
<section class="about-section">
<div class="container">
<h2 class="text-center mb-4">О корпорации LIS</h2>
<p class="lead text-center mb-4">
LIS Corp — международная промышленная корпорация,
специализирующаяся на добыче меди и развитии инфраструктуры
атомной энергетики. Компания объединяет современные технологии,
инженерные решения и глобальные производственные мощности для
развития энергетической и промышленной инфраструктуры.
</p>
<p class="text-center">
Корпорация реализует масштабные проекты в области добычи природных
ресурсов, металлургии и строительства энергетических объектов.
Основная цель компании — создание устойчивой промышленной
экосистемы, способной обеспечивать энергией и стратегическими
ресурсами целые регионы мира.
</p>
</div>
</section>
<section class="industries-section">
<div class="container">
<h2 class="text-center mb-5">Направления деятельности</h2>
<div class="industries-grid">
<div class="industry-card">
<img src="images/metallurgy.png" alt="Металлургия">
<h3>Металлургия</h3>
<p>
LIS Corp управляет современными горнодобывающими и
металлургическими предприятиями, специализирующимися на
добыче и переработке меди. Производственные комплексы
компании обеспечивают полный цикл — от разработки
месторождений до производства высококачественных
металлических материалов.
</p>
</div>
<div class="industry-card">
<img src="images/nuclear.png" alt="Атомная энергетика">
<h3>Атомная энергетика</h3>
<p>
Компания участвует в разработке и строительстве объектов
атомной энергетики, включая реакторные комплексы,
инфраструктуру энергоснабжения и системы безопасности.
Проекты LIS Corp направлены на развитие устойчивой
энергетики и повышение энергетической независимости
регионов.
</p>
</div>
</div>
</div>
</section>
<section class="stats-section">
<div class="container">
<h2 class="text-center mb-5">Ключевые показатели</h2>
<div class="stats-grid">
<?php foreach ($stats as $stat): ?>
<div class="stat-card">
<div class="stat-value">
<?= htmlspecialchars($stat['value_text']) ?>
</div>
<div class="stat-label">
<?= htmlspecialchars($stat['label']) ?>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
</section>
<section class="gallery-section">
<div class="container">
<h2 class="text-center mb-5">Галерея объектов</h2>
<div class="gallery-grid">
<?php foreach ($gallery as $image): ?>
<div class="gallery-item">
<img src="<?= htmlspecialchars($image['img_path']) ?>" alt="LIS object">
</div>
<?php endforeach; ?>
</div>
</div>
</section>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>