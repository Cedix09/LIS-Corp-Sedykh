<?php
require_once 'auth_check.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$faq = [];

try {
    $stmt = $pdo->query("SELECT * FROM faq ORDER BY sort_order ASC");
    $faq = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $faq = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FAQ | LIS Corp</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="faq-section">
<div class="container">

<h1 class="text-center mb-5">Часто задаваемые вопросы</h1>

<?php if (count($faq) > 0): ?>

<div class="accordion" id="faqAccordion">

<?php foreach ($faq as $index => $item): ?>

<div class="accordion-item">

<h2 class="accordion-header">
<button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" 
        type="button" 
        data-bs-toggle="collapse" 
        data-bs-target="#faq<?= $item['id'] ?>">
        
<?= htmlspecialchars($item['question']) ?>

</button>
</h2>

<div id="faq<?= $item['id'] ?>" 
     class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
     data-bs-parent="#faqAccordion">

<div class="accordion-body">
<?= nl2br(htmlspecialchars($item['answer'])) ?>
</div>

</div>

</div>

<?php endforeach; ?>

</div>

<?php else: ?>

<p class="text-center">Вопросов пока нет.</p>

<?php endif; ?>

    <div class="faq-contact text-center">
        <h3 class="mt-5">Не нашли ответ?</h3>

        <p>
        Если у вас остались вопросы — свяжитесь с нами через форму обратной связи.
        </p>

        <a href="feedback.php" class="btn btn-dark">
        Написать нам
        </a>
</div>
</section>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>