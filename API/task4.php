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

// 2. Словарь статусов
$status_names = [
    'new' => 'Новый',
    'paid' => 'Оплачен',
    'shipped' => 'Отправлен',
    'done' => 'Завершён',
    'cancelled' => 'Отменён'
];

$response = [];


                try {
                    // 3. Начинаем формировать запрос к таблице заказов
                    // Используем хитрость "WHERE 1=1", чтобы проще было добавлять условия через AND
                    $sql = "SELECT o.id as order_id, o.status, o.created_at, 
                                u.last_name, u.first_name, u.middle_name, 
                                u.city, u.street, u.house, u.flat
                            FROM orders o
                            JOIN users u ON o.user_id = u.id
                            WHERE 1=1";
                            
                    $params = []; // Сюда будем складывать значения для безопасной подстановки

                    // 4. Проверяем параметр status в URL (например, ?status=paid)
                    if (isset($_GET['status']) && $_GET['status'] !== '') {
                        $sql .= " AND o.status = ?";
                        $params[] = $_GET['status'];
                    }

                    // 5. Проверяем параметр user_id в URL (например, ?user_id=2)
                    if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
                        $sql .= " AND o.user_id = ?";
                        $params[] = $_GET['user_id'];
                    }

                    // Обязательная сортировка по возрастанию ID
                    $sql .= " ORDER BY o.id ASC";

                    // Выполняем запрос с учетом фильтров
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // 6. Если заказов нет (не подошли под фильтр), цикл не выполнится, 
                    // и $response останется пустым массивом [], как и требуется в задании.

    // 7. Проходим циклом по найденным заказам (логика из Задания 4.3)
    foreach ($orders as $order) {
        
        // Собираем ФИО
        $customer = $order['last_name'] . ' ' . $order['first_name'];
        if (!empty($order['middle_name'])) {
            $customer .= ' ' . $order['middle_name'];
        }
        
        // Собираем Адрес
        $address = "г. {$order['city']}, ул. {$order['street']}, д. {$order['house']}";
        if (!empty($order['flat'])) {
            $address .= ", кв. {$order['flat']}";
        }

        // Форматируем Дату
        $date = date('d.m.Y', strtotime($order['created_at']));

        // Достаем товары для текущего заказа
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

        // Собираем итоговый объект
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

    // 8. Отдаем JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}