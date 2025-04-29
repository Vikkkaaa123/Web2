<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['pass'] ?? '');

    try {
        // Сначала проверяем в таблице admins
        $stmt = $db->prepare("SELECT login, password_hash FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin'] = true;
            $_SESSION['admin_login'] = $login;
            header('Location: admin/admin.php');
            exit();
        }

        // Если не админ, проверяем как обычного пользователя
        $stmt = $db->prepare("SELECT id, login, password_hash FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php');
            exit();
        }

        // Если дошли сюда - авторизация не удалась
        $messages[] = 'Неверный логин или пароль';

    } catch (PDOException $e) {
        $messages[] = 'Ошибка при входе в систему';
        error_log('Login error: ' . $e->getMessage());
    }
}

// Если уже авторизован - перенаправляем
if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}
if (!empty($_SESSION['admin'])) {
    header('Location: admin/admin.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Вход в систему</title>
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
                <input type="text" id="login" name="login" required value="admin">
            </div>
            
            <div class="form-group">
                <label for="pass">Пароль:</label>
                <input type="password" id="pass" name="pass" required value="123">
            </div>
            
            <div class="form-actions">
                <button type="submit">Войти</button>
            </div>
        </form>
    </div>
</body>
</html>
