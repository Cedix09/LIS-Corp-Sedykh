<?php
// 1. Заголовки (JSON и доступ для PUT)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Content-Type');

// 2. Подключение БД (из api/ в config/)
require_once '../config/database.php';

// 3. Только PUT-запросы
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// 4. Читаем JSON
$input = file_get_contents('php://input');
$data  = json_decode($input, true);

// 5. Жесткая проверка всех полей
if (empty($data['id']) || empty($data['category_id']) || empty($data['title']) || empty($data['content'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Поля id, category_id, title и content обязательны для обновления']);
    exit;
}

try {
    // 6. Коннект через класс Database
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        throw new Exception("Не удалось подключиться к базе данных LIS Corp");
    }

    // 7. Проверяем наличие записи перед обновлением
    $check = $pdo->prepare('SELECT id FROM news WHERE id = :id');
    $check->execute([':id' => $data['id']]);
    
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Запись с таким ID не найдена']);
        exit;
    }

    // 8. Обновление по твоим полям
    $stmt = $pdo->prepare(
        'UPDATE news 
         SET category_id = :cat_id, 
             title = :title, 
             content = :content, 
             preview_img = :img 
         WHERE id = :id'
    );

    $stmt->execute([
        ':cat_id'  => $data['category_id'],
        ':title'   => $data['title'],
        ':content' => $data['content'],
        ':img'     => $data['preview_img'] ?? null,
        ':id'      => $data['id']
    ]);

    // 9. Финал
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'Запись в LIS Corp успешно обновлена'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка БД: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}