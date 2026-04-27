<?php
// 1. Заголовки (JSON и доступ)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 2. Подключение БД (выходим из api/ в config/)
require_once '../config/database.php';

// 3. Проверка метода
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// 4. Получение данных
$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty body or invalid JSON']);
    exit;
}

// 5. ПРОВЕРКА ТВОИХ ПОЛЕЙ (Category, Title, Content)
// Теперь код не будет ругаться на отсутствие "name"
if (empty($data['category_id']) || empty($data['title']) || empty($data['content'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Поля category_id, title и content обязательны']);
    exit;
}

try {
    // 6. Коннект к базе через твой класс
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        throw new Exception("Ошибка подключения к базе LIS Corp");
    }

    // 7. SQL запрос под твою структуру
    // Замени news на имя своей таблицы, если оно другое
    $stmt = $pdo->prepare(
        'INSERT INTO news (category_id, title, content, preview_img) 
         VALUES (:cat_id, :title, :content, :img)'
    );

    // 8. Выполнение с твоими данными из Postman
    $stmt->execute([
        ':cat_id'  => $data['category_id'],
        ':title'   => $data['title'],
        ':content' => $data['content'],
        ':img'     => $data['preview_img'] ?? null // Если картинки нет, запишем NULL
    ]);

    $newId = $pdo->lastInsertId();

    // 9. Успех
    http_response_code(201); 
    echo json_encode([
        'success' => true,
        'id'      => $newId,
        'message' => 'Новость успешно добавлена в систему LIS Corp',
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'БД Ошибка: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}