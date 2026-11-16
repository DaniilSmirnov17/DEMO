<?php
// серверная часть 
// подключение файла подключения к БД
require_once 'config.php';
// запись ошибок 
$error = '';

// обработка формы 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['login']    ?? '');
    $password = $_POST['password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $phone    = trim($_POST['tel']      ?? '');
    $email    = trim($_POST['email']    ?? '');
    // валидация формы 
    if (!$username || !$fullname || !$phone || !$email || !$password) {
        $error = 'Заполните все поля';
    } elseif (!preg_match('/^[A-Za-z0-9]{6,}$/', $username)) {
        $error = 'Логин должен содержать только латиницу и цифры, не менее 6 символов';
    } elseif (!preg_match('/^[А-Яа-яЁё\s]+$/u', $fullname)) {
        $error = 'ФИО должно содержать только кириллицу и пробелы';
    } elseif (!preg_match('/^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $phone)) {
        $error = 'Телефон должен быть в формате 8(XXX)XXX-XX-XX';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } elseif (strlen($password) < 8) {
        $error = 'Пароль должен быть не менее 8 символов';
    } else {
        // добавление пользователя в бд 
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Такой логин уже занят";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users(username, email, password_hash, fullname, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $fullname, $phone]);
            header('Location: login.php');
            exit;
        }
    }
}
?>
<!-- вывод в бразуер  -->
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
<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <section class="card shadow-sm">
                <div class="card-body">
                    <h2 class="text-center mb-3">Регистрация</h2>
                    <!-- вывод ошибок  -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="login" class="form-label">Логин</label>
                            <input type="text" class="form-control" id="login" name="login"
                                   minlength="6" pattern="[A-Za-z0-9]+" required
                                   data-testid="reg-login">
                            <div class="form-text">Минимум 6 символов, только латиница и цифры</div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   minlength="8" required data-testid="reg-password">
                        </div>
                        <div class="mb-3">
                            <label for="fullname" class="form-label">ФИО</label>
                            <input type="text" class="form-control" id="fullname" name="fullname"
                                   pattern="[А-Яа-яЁё\s]+" required data-testid="reg-fullname">
                        </div>
                        <div class="mb-3">
                            <label for="tel" class="form-label">Телефон</label>
                            <input type="tel" class="form-control" id="tel" name="tel"
                                   pattern="8\(\d{3}\)\d{3}-\d{2}-\d{2}"
                                   placeholder="8(999)123-45-67" required data-testid="reg-phone">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Почта</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   required data-testid="reg-email">
                        </div>
                        <button type="submit" class="btn btn-primary w-100" data-testid="reg-submit">
                            Создать пользователя
                        </button>
                        <p class="text-center mt-2">
                            <a href="login.php">Уже зарегистрированы? Войти</a>
                        </p>
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
