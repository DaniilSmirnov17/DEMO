<?php

require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === 'Conf2027' && $password === 'Demo77') {
        $_SESSION['username'] = $username;
        header('Location: admin.php');
        exit;
    }


    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: application.php');
        exit;
    }

    $error = 'Неверный логин или пароль';
}
?>
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
                    <h1 class="text-center mb-3">Авторизация</h1>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="login" class="form-label">Логин</label>
                            <input type="text" class="form-control" id="login" name="login"
                                required data-testid="login-login">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" class="form-control" id="password" name="password"
                                required data-testid="login-password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100" data-testid="login-submit">
                            Войти
                        </button>
                        <p class="text-center mt-2">
                            <a href="index.php">Ещё не зарегистрированы? Регистрация</a>
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
