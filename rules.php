<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$vacancies = [];

try {
    $stmt = $pdo->query("SELECT * FROM vacancies WHERE is_active = 1");
    $vacancies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $vacancies = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Карьера | LIS Corp</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="career-section">
<div class="container">

<h1 class="text-center mb-5">Карьера в LIS Corp</h1>

<?php if (count($vacancies) > 0): ?>

<div class="vacancies-grid">

<?php foreach ($vacancies as $vacancy): ?>

<div class="vacancy-card">

<h3><?= htmlspecialchars($vacancy['title']) ?></h3>

<p class="vacancy-desc">
<?= nl2br(htmlspecialchars($vacancy['description'])) ?>
</p>

<?php if (!empty($vacancy['salary'])): ?>
<p class="vacancy-salary">
Зарплата: <?= htmlspecialchars($vacancy['salary']) ?> ₸
</p>
<?php endif; ?>

<button class="btn btn-dark">Откликнуться</button>

</div>

<?php endforeach; ?>

</div>

<?php else: ?>

<p class="text-center">На данный момент открытых вакансий нет.</p>

<?php endif; ?>

</div>
</section>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>