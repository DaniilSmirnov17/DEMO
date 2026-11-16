<?php
// серверная часть 
// подключение файла подключения к БД
require_once 'config.php';
// проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// запись ошибок 
$error   = '';
$success = '';

// обработка формы 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room    = trim($_POST['room']    ?? '');
    $date    = $_POST['date']    ?? '';
    $payment = $_POST['payment'] ?? '';
    $userId  = $_SESSION['user_id'];
    // валидация 
    if ($room === '' || $date === '' || $payment === '') {
        $error = 'Заполните все поля';
    } else {
        // добавление в бд заявки 
        $stmt = $conn->prepare("INSERT INTO requests (user_id, room, date, payment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $room, $date, $payment]);
        $success = 'Заявка успешно отправлена';
    }
}
?>
<!-- вывод в браузер  -->
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Оформление заявки</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">  
</head>

<body class="d-flex flex-column min-vh-100">
<header class="bg-primary text-white text-center py-3">
    <h2 class="h4 mb-0">Конференции.РФ</h2>
</header>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
        <a class="navbar-brand" href="application.php"><img style="height: 40px; weight: 40px;" src="img/logo.png" alt="logo"></a>
        <a class="navbar-brand" href="application.php">Профиль</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <div class="ms-auto d-flex flex-column flex-lg-row align-items-lg-center mx-auto gap-3">
                    <span>Привет, <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="create-app.php" class="btn btn-outline-primary btn-sm">Создать заявку</a>
                    <form action="logout.php" method="post" class="m-0 d-flex justify-content-center">
                        <button class="btn btn-outline-success btn-sm" type="submit">Выход</button>
                    </form>
                </div>
            </ul>
        </div>
    </nav>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <section class="card shadow-sm">
                <div class="card-body">
                    <h1 class="text-center mb-4">Оформление заявки</h1>
                    <!-- уведомление об ошибках -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <!-- уведомление о подтверждении  -->
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="room" class="form-label">Помещение</label>
                            <input type="text" class="form-control" id="room" name="room"
                                placeholder="Доступные помещения: аудитория, коворкинг, кинозал" required
                                data-testid="req-room">
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label">Дата начала конференции</label>
                            <input type="date" class="form-control" id="date" name="date"
                                required data-testid="req-date">
                        </div>
                        <div class="mb-3">
                            <label for="payment" class="form-label">Способ оплаты</label>
                            <select class="form-select" id="payment" name="payment"
                                    required data-testid="req-payment">
                                <option value="">Выберите способ оплаты</option>
                                <option value="Очное посещение">При очном посещении</option>
                                <option value="СБП">Переводом по системе СБП</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" data-testid="req-submit">
                            Отправить
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</main>
<footer class="bg-light text-center py-3 mt-auto">
    <p class="mb-0">2026 Конференции.РФ</p>
</footer>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
