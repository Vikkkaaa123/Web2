<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

header('Content-Type: text/html; charset=UTF-8');
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/db.php';
$db = connectDB();
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Неверный CSRF-токен");
    }

    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['pass'] ?? '');
    
    try {
        // Проверка администратора
        $stmt = $db->prepare("SELECT * FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        
        if ($admin = $stmt->fetch()) {
            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin'] = true;
                $_SESSION['login'] = htmlspecialchars($admin['login'], ENT_QUOTES, 'UTF-8');
                header('Location: admin/admin.php');
                exit();
            } else {
                $error = 'Неверный пароль администратора';
            }
        }
        // Проверка обычного пользователя
        else {
            $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
            $stmt->execute([$login]);
            
            if ($user = $stmt->fetch()) {
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user'] = true;
                    $_SESSION['login'] = htmlspecialchars($user['login'], ENT_QUOTES, 'UTF-8');
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
        error_log("Login error: " . $e->getMessage());
        $error = 'Ошибка авторизации';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Вход в систему</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">  
    <div class="form-container">
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label>Логин:</label>
                <input type="text" name="login" required value="<?= isset($_POST['login']) ? htmlspecialchars($_POST['login'], ENT_QUOTES, 'UTF-8') : '' ?>">
            </div>
            <div class="form-group">
                <label>Пароль:</label>
                <input type="password" name="pass" required>
            </div>
            <div class="form-actions">
                <input type="submit" value="Войти">
            </div>
        </form>
    </div>
</body>
</html>
