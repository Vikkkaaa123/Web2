<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Жестко прописанные учетные данные администратора
const ADMIN_LOGIN = 'admin';
const ADMIN_PASSWORD = '123'; // Пароль в открытом виде для теста

// Если уже авторизован - перенаправляем
if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}
if (!empty($_SESSION['admin'])) {
    header('Location: admin/admin.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['pass'] ?? '');

    // Проверка администратора (жестко прописано в коде)
    if ($login === ADMIN_LOGIN && $password === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        $_SESSION['admin_login'] = ADMIN_LOGIN;
        header('Location: admin/admin.php');
        exit();
    }

    // Проверка обычного пользователя (из базы данных)
    try {
        $db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php');
            exit();
        }

        $error = 'Неверный логин или пароль';

    } catch (PDOException $e) {
        $error = 'Ошибка базы данных: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px; }
        .error { color: red; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; box-sizing: border-box; }
        input[type="submit"] { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Вход в систему</h1>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="login">Логин:</label>
            <input type="text" id="login" name="login" required>
        </div>
        
        <div class="form-group">
            <label for="pass">Пароль:</label>
            <input type="password" id="pass" name="pass" required>
        </div>
        
        <div class="form-group">
            <input type="submit" value="Войти">
        </div>
    </form>
    
    <p>Нет аккаунта? <a href="index.php">Заполните форму</a></p>
    
    <!-- Отладочная информация -->
    <div style="margin-top: 20px; color: #666; font-size: 0.9em;">
        <p>Тестовые данные:</p>
        <p>Админ: <?= ADMIN_LOGIN ?> / <?= ADMIN_PASSWORD ?></p>
    </div>
</body>
</html>
