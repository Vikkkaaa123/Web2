<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

// Подключение к БД
$user = 'u68606';
$pass = '9347178';

try {
    $db = new PDO('mysql:host=localhost;dbname=u68606', $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}

$messages = [];

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['pass'] ?? '');

    try {
        // Проверяем обычного пользователя
        $stmt = $db->prepare("SELECT id, login, password_hash FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            
            $admin_stmt = $db->prepare("SELECT 1 FROM admins WHERE login = ?");
            $admin_stmt->execute([$login]);
            
            if ($admin_stmt->fetch()) {
                $_SESSION['admin'] = true;
                header('Location: admin/admin.php'); 
            } else {
                header('Location: index.php'); 
            }
            exit();
        }
        
        $admin_stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
        $admin_stmt->execute([$login]);
        $admin = $admin_stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin'] = true;
            $_SESSION['admin_login'] = $login;
            header('Location: admin/admin.php');
            exit();
        }

        $messages[] = 'Неверный логин или пароль';

    } catch (PDOException $e) {
        $messages[] = 'Ошибка при входе в систему';
        error_log('Login error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Вход в систему</title>
    <style>
        .login-form {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .form-actions {
            margin-top: 20px;
        }
        .error-message {
            color: #d32f2f;
            margin-bottom: 15px;
        }
        .register-link {
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h1>Вход в систему</h1>
        
        <?php if (!empty($messages)): ?>
            <div class="error-message">
                <?php foreach ($messages as $message): ?>
                    <p><?= htmlspecialchars($message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" required>
            </div>
            
            <div class="form-group">
                <label for="pass">Пароль:</label>
                <input type="password" id="pass" name="pass" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Войти</button>
            </div>
        </form>
        
        <p class="register-link">Нет аккаунта? <a href="index.php">Заполните форму</a></p>
        
        <p class="admin-notice" style="margin-top: 20px; font-size: 0.9em; color: #666;">
        </p>
    </div>
</body>
</html>
