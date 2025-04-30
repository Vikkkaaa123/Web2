<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Подключение к БД
$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['pass']);
    
    // Проверка администратора
    $stmt = $db->prepare("SELECT * FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    
    if ($admin = $stmt->fetch()) {
        if (password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin'] = true;
            header('Location: admin/admin.php');
            exit();
        }
    }
    
    // Проверка обычного пользователя
    $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    
    if ($user = $stmt->fetch() && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = true;
        header('Location: index.php');
        exit();
    }
    
    $error = 'Неверный логин или пароль';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Вход</title>
    <style>
        body { font-family: Arial; max-width: 400px; margin: 0 auto; padding: 20px; }
        .error { color: red; }
        input { width: 100%; padding: 8px; margin: 5px 0; }
        button { padding: 10px; background: #4CAF50; color: white; border: none; }
    </style>
</head>
<body>
    <h1>Вход в систему</h1>
    
    <?php if (!empty($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div>
            <label>Логин:</label>
            <input type="text" name="login" required>
        </div>
        <div>
            <label>Пароль:</label>
            <input type="password" name="pass" required>
        </div>
        <button type="submit">Войти</button>
    </form>
    
    <p>Нет аккаунта? <a href="index.php">Зарегистрироваться</a></p>
    
    <!-- Отладочная информация -->
    <div style="margin-top: 20px; padding: 10px; background: #f0f0f0;">
        <h3>Отладка:</h3>
        <p>Попробуйте эти данные:</p>
        <p><strong>Админ:</strong> admin / 123</p>
        <p>Хеш в базе: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi</p>
    </div>
</body>
</html>
