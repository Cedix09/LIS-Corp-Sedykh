<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Контакты | LIS Corp</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="contacts-section">
<div class="container">

<h1 class="mb-5 text-center">Контакты</h1>

<div class="contacts-grid">

<div class="contacts-info">

<h3>Главный офис</h3>

<p>Esentai Tower</p>
<p>77/7 Al-Farabi Avenue</p>
<p>Almaty, Kazakhstan</p>

<h3 class="mt-4">Связь</h3>

<p>Телефон: <a href="tel:+77000000000">+7 (777) 000-77-77</a></p>
<p>Email: <a href="mailto:contact@liscorp.com">contact@liscorp.com</a></p>

<h3 class="mt-4">Режим работы</h3>

<p>Пн–Пт: 09:00 – 18:00</p>
<p>Сб–Вс: выходной</p>

<h3 class="mt-4">Социальные сети</h3>

<div class="socials">
<a href="#">LinkedIn</a>
<a href="https://x.com/LisCorporation">Twitter</a>
<a href="https://www.youtube.com/channel/UCFFW5wfa670IvHiu16rLeZg">YouTube</a>
</div>

</div>


<div class="contacts-map">

<iframe
src="https://maps.google.com/maps?q=43.2183,76.9286&z=17&output=embed"
loading="lazy">
</iframe>

</div>

</div>

</div>
</section>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>