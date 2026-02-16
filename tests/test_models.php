<?php
require_once '../config/database.php';
require_once '../classes/News.php';
require_once '../classes/Feedback.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== ТЕСТ: NEWS ===\n";
$news = new News($conn);
$news->category_id = 1;
$news->title = "Test LIS Corp";
$news->content = "Content test";

if ($news->create()) {
    echo "CREATE: OK\n";
    if ($news->getById($news->id)) echo "READ: OK\n";
    if ($news->delete($news->id)) echo "DELETE: OK\n";
}

echo "\n=== ТЕСТ: FEEDBACK ===\n";
$feed = new Feedback($conn);
$feed->user_name = "Admin";
$feed->user_email = "admin@lis.corp";
$feed->message = "System test message";

if ($feed->create()) {
    echo "CREATE FEEDBACK: OK\n";
}
echo "=== ТЕСТЫ ЗАВЕРШЕНЫ ===\n";