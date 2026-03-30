<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Обратная связь | LIS Corp</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="feedback-section">
<div class="container">

<h1 class="text-center mb-5">Обратная связь</h1>
<?php if (isset($_GET['error'])): ?>
<?php $errors = explode('|', $_GET['error']); ?>

<div class="alert alert-danger">
    <?php foreach ($errors as $error): ?>
        <div><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>
</div>

<?php endif; ?>
<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success text-center">
    Спасибо, ваше сообщение отправлено!
</div>
<?php endif; ?>

<form action="send_feedback.php" method="POST" class="feedback-form">

<div class="mb-3">
<label>Ваше имя</label>
<input type="text" name="user_name" class="form-control" maxlength="100" required>
</div>

<div class="mb-3">
<label>Email</label>
<input type="email" name="user_email" class="form-control" maxlength="100" required>
</div>

<div class="mb-3">
<label>Сообщение</label>
<textarea name="message" id="message" maxlength="1000" class="form-control"></textarea>
<small id="charCount">0 / 1000</small>
</div>

<button type="submit" class="btn btn-dark">Отправить</button>

</form>

</div>
</section>

<?php include 'footer.php'; ?>
<script id="js1">
const textarea = document.getElementById('message');
const counter = document.getElementById('charCount');

textarea.addEventListener('input', () => {
    counter.textContent = textarea.value.length + " / 1000";
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>