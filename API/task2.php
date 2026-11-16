<?php
header('Content-Type: application/json; charset=utf-8'); //232 страница учебника

$host = '127.0.0.1';
$db   = 'shop_db'; 
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user, $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $stmt = $pdo->query("   
        SELECT id, status, created_at, user_id
        FROM orders
        ORDER BY id ASC
    ");
    $orders = $stmt->fetchAll(); 
    echo json_encode($orders, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера'], JSON_UNESCAPED_UNICODE);
}