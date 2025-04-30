<?php
session_start();

// Подключение к базе данных
$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['pass']);
    
    try {
        // Сначала проверяем администратора
        $stmt = $db->prepare("SELECT * FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        
        if ($admin = $stmt->fetch()) {
            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin'] = true;
                $_SESSION['login'] = $admin['login'];
                header('Location: admin/admin.php');
                exit();
            } else {
                $error = 'Неверный пароль администратора';
            }
        }
        // Если не админ, проверяем обычного пользователя
        else {
            $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
            $stmt->execute([$login]);
            
            if ($user = $stmt->fetch()) {
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user'] = true;
                    $_SESSION['login'] = $user['login'];
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Неверный пароль пользователя';
                }
            } else {
                $error = 'Пользователь не найден';
            }
        }
    } catch (PDOException $e) {
        $error = 'Ошибка базы данных';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Вход в систему</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #4CAF50; color: white; border: none; padding: 10px; width: 100%; }
        .error { color: red; margin: 10px 0; }
        .debug-info { margin-top: 20px; padding: 10px; background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Вход в систему</h1>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Логин:</label>
            <input type="text" name="login" required>
        </div>
        <div class="form-group">
            <label>Пароль:</label>
            <input type="password" name="pass" required>
        </div>
        <button type="submit">Войти</button>
    </form>

    <div class="debug-info">
        <h3>Для тестирования:</h3>
        <p>1. Убедитесь что в таблице `admins` есть запись:</p>
        <pre>INSERT INTO admins (login, password_hash) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');</pre>
        <p>2. Используйте логин: <strong>admin</strong>, пароль: <strong>123</strong></p>
    </div>
</body>
</html>
