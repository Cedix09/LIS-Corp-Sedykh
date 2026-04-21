<?php
// 1. Заголовки (JSON и доступ для DELETE)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// 2. Подключение БД (выходим из api/ в config/)
require_once '../config/database.php';

// 3. Только DELETE-запросы
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// 4. Читаем JSON из тела запроса
$input = file_get_contents('php://input');
$data  = json_decode($input, true);

// 5. Проверка наличия ID
if (!$data || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Поле "id" обязательно для удаления записи']);
    exit;
}

try {
    // 6. Коннект через твой класс Database
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        throw new Exception("Не удалось подключиться к базе данных LIS Corp");
    }

    // 7. Сначала проверяем, есть ли вообще такая запись
    // Замени news на свою таблицу, если она называется иначе
    $check = $pdo->prepare('SELECT id FROM news WHERE id = :id');
    $check->execute([':id' => $data['id']]);
    
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Запись с таким ID не найдена, удалять нечего']);
        exit;
    }

    // 8. Само удаление
    $stmt = $pdo->prepare('DELETE FROM news WHERE id = :id');
    $stmt->execute([':id' => $data['id']]);

    // 9. Успех
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'Запись успешно удалена из системы LIS Corp'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка БД: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}