<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">

        <a class="navbar-brand fw-bold" href="index.php">LIS</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">

            <ul class="navbar-nav me-auto">

                <li class="nav-item">
                    <a class="nav-link" href="/index.php">Главная</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/about.php">О компании</a>
                </li>

                <li class="nat-item">
                    <a class="nav-link" href="/news.php">Новости</a>
                </li>

                <li class="nat-item">
                    <a class="nav-link" href="#">Форум</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/career.php">Карьера</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/contacts.php">Контакты</a>
                </li>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                    <a class="nav-link" href="/admin/admin_news.php">Админ панель</a>
                    </li>
<?php endif; ?>
                
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