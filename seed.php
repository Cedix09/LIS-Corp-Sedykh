<?php
require 'config/database.php'; 
use Faker\Factory;

// Создаем генератор данных на русском языке
$faker = Factory::create('ru_RU');
echo "🚀 Начинаем наполнение базы данных lis_corp...\n";

// --- ЭТАП 1: ПОЛЬЗОВАТЕЛИ ---
echo " 👤 Создаем пользователей...";
$userIds = [];
for ($i = 0; $i < 10; $i++) {
    $name = $faker->userName;
    $password = password_hash('123456', PASSWORD_DEFAULT);
    
    // Убрали email из запроса и из execute
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$name, $password]);

    $userIds[] = $pdo->lastInsertId();
}
echo " Готово!\n";

// --- ЭТАП 2: НОВОСТИ ---
echo "📦 Создаем записи в таблицу news...";
for ($i = 0; $i < 50; $i++) {
    $title = $faker->realText(40);
    $content = $faker->realText(600);
    $category_id = $faker->numberBetween(1, 3); 

    $sql = "INSERT INTO news (category_id, title, content, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$category_id, $title, $content]);
}
echo " Готово! (50 новостей создано)\n";