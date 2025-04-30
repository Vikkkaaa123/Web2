<?php
// Включим вывод всех ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
header('Content-Type: text/html; charset=UTF-8');

// Логирование начала выполнения
error_log("Login script started");

try {
    // Подключение к БД с обработкой ошибок
    $db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    error_log("Database connection established");
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Обработка авторизации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received");
    
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['pass'] ?? '');
    
    try {
        // 1. Проверка администратора
        $stmt = $db->prepare("SELECT * FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            error_log("Admin found: " . print_r($admin, true));
            
            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin'] = true;
                $_SESSION['admin_login'] = $admin['login'];
                error_log("Admin auth successful, redirecting to admin panel");
                header('Location: admin/admin.php');
                exit();
            } else {
                error_log("Admin password verification failed");
            }
        }

        // 2. Проверка обычного пользователя
        $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            error_log("User auth successful, redirecting to index");
            header('Location: index.php');
            exit();
        }
        
        // Если дошли сюда - авторизация не удалась
        $error = 'Неверный логин или пароль';
        error_log("Authentication failed for login: $login");
        
    } catch (PDOException $e) {
        $error = 'Ошибка при проверке учетных данных';
        error_log("Database error: " . $e->getMessage());
    }
}

// Логирование перед выводом формы
error_log("Preparing to display login form");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .login-form {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h1>Вход в систему</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
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
        
        <p>Нет аккаунта? <a href="index.php">Заполните форму регистрации</a></p>
    </div>
    
    <!-- Временная отладочная информация -->
    <?php if (isset($_POST['login'])): ?>
    <div style="margin-top: 20px; padding: 10px; background: #eee; border-radius: 4px;">
        <h3>Отладочная информация:</h3>
        <p>Введенный логин: <?= htmlspecialchars($_POST['login'] ?? '') ?></p>
        <p>Введенный пароль: <?= htmlspecialchars($_POST['pass'] ?? '') ?></p>
        <p>Ошибка: <?= $error ?? 'Нет ошибок' ?></p>
    </div>
    <?php endif; ?>
</body>
</html>
