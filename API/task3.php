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

// 2. Словарь для перевода статусов
$status_names = [
    'new' => 'Новый',
    'paid' => 'Оплачен',
    'shipped' => 'Отправлен',
    'done' => 'Завершён',
    'cancelled' => 'Отменён'
];

// Массив, куда мы соберем финальный ответ
$response = [];

                try {
                    // 3. Достаем все заказы и данные пользователей (сортируем по ID заказа)
                    $sql = "SELECT o.id as order_id, o.status, o.created_at, 
                                u.last_name, u.first_name, u.middle_name, 
                                u.city, u.street, u.house, u.flat
                            FROM orders o
                            JOIN users u ON o.user_id = u.id
                            ORDER BY o.id ASC";
                            
                    $orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // 4. Проходим циклом по каждому заказу
    foreach ($orders as $order) {
        
        // --- Собираем ФИО (customer) ---
        $customer = $order['last_name'] . ' ' . $order['first_name'];
        if (!empty($order['middle_name'])) {
            $customer .= ' ' . $order['middle_name']; // добавляем отчество, если оно есть
        }
        
        // --- Собираем Адрес (address) ---
        $address = "г. {$order['city']}, ул. {$order['street']}, д. {$order['house']}";
        if (!empty($order['flat'])) {
            $address .= ", кв. {$order['flat']}"; // добавляем квартиру, если она есть
        }

        // --- Форматируем Дату (created_at) ---
        // Из '2026-02-14 10:15:00' делаем '14.02.2026'
        $date = date('d.m.Y', strtotime($order['created_at']));

        // 5. Достаем товары для ТЕКУЩЕГО заказа
        $items_sql = "SELECT p.title, oi.quantity, p.price 
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = ?";
        $stmt = $pdo->prepare($items_sql);
        $stmt->execute([$order['order_id']]);
        $items_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];       // сюда сложим товары
        $total_sum = 0;    // сюда посчитаем общую сумму
        $items_count = 0;  // сюда посчитаем кол-во позиций

        // 6. Проходим циклом по товарам текущего заказа
        foreach ($items_db as $item) {
            $price = (float)$item['price'];
            $quantity = (int)$item['quantity'];
            
            // Считаем сумму
            $total_sum += $price * $quantity;
            $items_count++;

            // Добавляем товар в массив (цену делаем строкой с 2 знаками)
            $items[] = [
                'title' => $item['title'],
                'quantity' => $quantity,
                'price' => sprintf('%.2f', $price) 
            ];
        }

        // 7. Собираем финальный объект заказа
        $response[] = [
            'order_id' => (int)$order['order_id'],
            'customer' => $customer,
            'status' => $status_names[$order['status']], // переводим статус
            'created_at' => $date,
            'address' => $address,
            'items_count' => $items_count,
            'total' => sprintf('%.2f', $total_sum), // сумму тоже делаем строкой
            'items' => $items
        ];
    }

    // 8. Отдаем результат в формате JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Если что-то сломалось (например, нет базы), отдаем 500 ошибку (это понадобится для задания 4.5)
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}