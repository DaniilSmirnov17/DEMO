<?php
// серверная часть 
require_once 'config.php';
//проверка на постороннего пользователя
if (($_SESSION['username'] ?? '') !== 'Conf2027') {
    header('Location: login.php');
    exit;
}
// работа с данными из формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $requestId = (int) $_POST['request_id'];

    if ($requestId > 0) {
        // Если нажата кнопка Удалить
        if (isset($_POST['delete_btn'])) {
            $stmt = $conn->prepare("DELETE FROM requests WHERE id = ?");
            $stmt->execute([$requestId]);
        } 
        // Если нажата кнопка Сохранить
        elseif (isset($_POST['status'])) {
            $allowed   = ['Новая', 'Мероприятие назначено', 'Завершено'];
            $newStatus = $_POST['status'];

            if (in_array($newStatus, $allowed, true)) {
                $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $requestId]);
            }
        }
    }
    // редирект для обновления данных 
    header('Location: admin.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}
// вывод данных из бд с условием из GET-запроса
$status = $_GET['status'] ?? '';
$sql    = "SELECT r.id, r.room, r.date, r.payment, r.status, u.username FROM requests r JOIN users u ON u.id = r.user_id";
$params = [];
if ($status !== '') {
    $sql .= " WHERE r.status = ?";
    $params[] = $status;
}
$sql .= " ORDER BY r.date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- вывод в браузер  -->
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Панель администратора</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">  
</head>
<body class="d-flex flex-column min-vh-100">

<header class="bg-primary text-white text-center py-3">
    <h2 class="h4 mb-0">Конференции.РФ</h2>
</header>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
        <a class="navbar-brand" href="#"><img style="height: 40px; weight: 40px;" src="img/logo.png" alt="logo"></a>
        <a class="navbar-brand" href="#">Администратор</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" 
        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <div class="ms-auto d-flex flex-column flex-lg-row align-items-lg-center mx-auto gap-3">
                    <span>Привет, <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <form action="logout.php" method="post" class="m-0 d-flex justify-content-center">
                        <button class="btn btn-outline-success btn-sm" type="submit">Выход</button>
                    </form>
                </div>
            </ul>
        </div>
    </nav>

<main class="container mt-4 flex-grow-1">
    <h1 class="text-center mb-4">Панель администратор</h1>

    <section class="card mb-4 shadow-sm">
        <div class="card-body">
            <h3 class="card-title">Фильтр заявок</h3>
            <form action="" method="get" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label for="status" class="form-label">Статус заявки</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Все статусы</option>
                        <option value="Новая" <?= $status === 'Новая' ? 'selected' : '' ?>>Новая</option>
                        <option value="Мероприятие назначено" <?= $status === 'Мероприятие назначено' ? 'selected' : '' ?>>Мероприятие назначено</option>
                        <option value="Завершено" <?= $status === 'Завершено' ? 'selected' : '' ?>>Завершено</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary">Применить</button>
                    <a href="admin.php" class="btn btn-secondary">Сбросить</a>
                </div>
            </form>
        </div>
    </section>

    <article class="table-responsive">
        <table class="table table-bordered table-striped shadow-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>№</th>
                    <th>Пользователь</th>
                    <th>Помещение</th>
                    <th>Дата</th>
                    <th>Оплата</th>
                    <th>Статус</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
            <!-- вывод данных -->
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">Ничего не найдено</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr data-testid="admin-row">
                            <td><?= (int) $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['room']) ?></td>
                            <td><?= date('d.m.Y', strtotime($row['date'])) ?></td>
                            <td><?= htmlspecialchars($row['payment']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td>
                                <form action="" method="post" class="d-flex gap-2">
                                    <input type="hidden" name="request_id" value="<?= (int) $row['id'] ?>">
                                    <!-- смена сатуса  -->
                                    <select class="form-select form-select-sm" name="status" data-testid="admin-status" style="min-width: 120px;">
                                        <option value="Новая" <?= $row['status'] === 'Новая' ? 'selected' : '' ?>>Новая</option>
                                        <option value="Мероприятие назначено" <?= $row['status'] === 'Мероприятие назначено' ? 'selected' : '' ?>>Мероприятие назначено</option>
                                        <option value="Завершено" <?= $row['status'] === 'Завершено' ? 'selected' : '' ?>>Завершено</option>
                                    </select>
                                    <!-- Кнопка сохранения статуса -->
                                    <button type="submit" name="update_btn" class="btn btn-primary btn-sm">Сохранить</button>
                                    <!-- Кнопка удаления с подтверждением -->
                                    <button type="submit" name="delete_btn" class="btn btn-danger btn-sm" onclick="return 
                                    confirm('Вы уверены, что хотите удалить эту заявку?')">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </article>
</main>

<footer class="bg-light text-center py-3 mt-5">
    <p class="mb-0">2026 Конференции.РФ</p>
</footer>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
