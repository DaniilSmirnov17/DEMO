<?php
// серверная часть 
// подключение файла подключения к БД
require_once 'config.php';
// проверка на авторизацию 
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// запись айди пользователя для дальнейшей работы 
$userId = (int) $_SESSION['user_id'];
// обработка формы 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $review    = trim($_POST['review'] ?? '');
    // обновление комментария 
    if ($requestId > 0 && $review !== '') {
        $stmt = $conn->prepare("UPDATE requests SET review = ? WHERE id = ? AND user_id = ? AND status = 'Завершено'");
        $stmt->execute([$review, $requestId, $userId]);
    }
    // редирект для обновления данных 
    header('Location: application.php');
    exit;
}

$stmt = $conn->prepare("SELECT id, room, date, payment, status, review FROM requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$requests = $stmt->fetchAll();

?>
<!-- вывод в браузер  -->
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Личный кабинет</title>
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

<main class="container mt-5 flex-grow-1">
    <section>
        <div class="d-flex justify-content-center">
            <img src="img/hero.jpg" class="img-fluid" alt="Главная картинка">
        </div>
        <h1 class="text-center my-4">Мои заявки</h1>

        <div class="table-responsive">
            <table class="table table-bordered table-striped mt-3" data-testid="req-list">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Помещение</th>
                        <th>Дата</th>
                        <th>Оплата</th>
                        <th>Статус</th>
                        <th>Отзыв</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- вывод данных  -->
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Заявок пока нет</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td><?= (int) $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['room']) ?></td>
                            <td><?= date('d.m.Y', strtotime($r['date'])) ?></td>
                            <td><?= htmlspecialchars($r['payment']) ?></td>
                            <td><?= htmlspecialchars($r['status']) ?></td>
                            <td>
                                <?php if ($r['status'] === 'Завершено'): ?>
                                    <?php if (empty($r['review'])): ?>
                                        <form action="" method="post" class="d-flex flex-column gap-2">
                                            <input type="hidden" name="request_id" value="<?= (int) $r['id'] ?>">
                                            <textarea class="form-control form-control-sm" name="review" rows="2" placeholder="Ваш отзыв..." 
                                            required data-testid="req-review"></textarea>
                                            <button type="submit" class="btn btn-primary btn-sm">Сохранить</button>
                                        </form>
                                    <?php else: ?>
                                        <p class="mb-1 small text-muted"><?= htmlspecialchars($r['review']) ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Недоступно</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<footer class="bg-light text-center py-3 mt-auto">
    <p class="mb-0">2026 Конференции.РФ</p>
</footer>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
