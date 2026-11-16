<?php
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

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера'], JSON_UNESCAPED_UNICODE);
}

header('Content-Type: application/json; charset=utf-8');

$status_names = [
    'new' => 'Новый',
    'paid' => 'Оплачен',
    'shipped' => 'Отправлен',
    'done' => 'Завершён',
    'cancelled' => 'Отменён'
];

$response = [];

try {

    // Проверяем статус
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $status = $_GET['status'];
        
        // Если такого статуса нет в нашем словаре - это ошибка 400!
        if (!array_key_exists($status, $status_names)) {
            http_response_code(400); // Код ошибки
            echo json_encode(['error' => 'Недопустимое значение параметра status'], JSON_UNESCAPED_UNICODE);
            exit; // Останавливаем работу, чтобы не отдавать пустой массив
        }
    }

    // Проверяем user_id
    if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
        $user_id = $_GET['user_id'];
        
        // Если это не целое число (например, буквы или 2.5) - ошибка 400!
        // filter_var - это встроенная функция PHP для проверки типов
        if (filter_var($user_id, FILTER_VALIDATE_INT) === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Недопустимое значение параметра user_id'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $sql = "SELECT o.id as order_id, o.status, o.created_at, 
                   u.last_name, u.first_name, u.middle_name, 
                   u.city, u.street, u.house, u.flat
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE 1=1";
            
    $params = [];

    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $sql .= " AND o.status = ?";
        $params[] = $_GET['status'];
    }

    if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
        $sql .= " AND o.user_id = ?";
        $params[] = $_GET['user_id'];
    }

    $sql .= " ORDER BY o.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $order) {
        $customer = $order['last_name'] . ' ' . $order['first_name'];
        if (!empty($order['middle_name'])) {
            $customer .= ' ' . $order['middle_name'];
        }
        
        $address = "г. {$order['city']}, ул. {$order['street']}, д. {$order['house']}";
        if (!empty($order['flat'])) {
            $address .= ", кв. {$order['flat']}";
        }

        $date = date('d.m.Y', strtotime($order['created_at']));

        $items_sql = "SELECT p.title, oi.quantity, p.price 
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = ?";
        $stmt_items = $pdo->prepare($items_sql);
        $stmt_items->execute([$order['order_id']]);
        $items_db = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        $items = [];       
        $total_sum = 0;    
        $items_count = 0;  

        foreach ($items_db as $item) {
            $price = (float)$item['price'];
            $quantity = (int)$item['quantity'];
            
            $total_sum += $price * $quantity;
            $items_count++;

            $items[] = [
                'title' => $item['title'],
                'quantity' => $quantity,
                'price' => sprintf('%.2f', $price) 
            ];
        }

        $response[] = [
            'order_id' => (int)$order['order_id'],
            'customer' => $customer,
            'status' => $status_names[$order['status']],
            'created_at' => $date,
            'address' => $address,
            'items_count' => $items_count,
            'total' => sprintf('%.2f', $total_sum),
            'items' => $items
        ];
    }

    // Если всё хорошо - отдаем 200 ОК и данные
    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Сюда мы попадем, если база данных недоступна или в SQL запросе опечатка
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера'], JSON_UNESCAPED_UNICODE);
}