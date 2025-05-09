<?php
require_once __DIR__ . '/db.php';
session_start();
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Настройка времени жизни сессии
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');

// CSRF защита
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

 $db = connectDB();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Ошибка безопасности. Пожалуйста, обновите страницу.';
    } else {
        $login = trim($_POST['login'] ?? '');
        $password = trim($_POST['pass'] ?? '');
        
        if (empty($login) || empty($password)) {
            $error = 'Логин и пароль обязательны для заполнения';
        } else {
            try {
                // Проверка администратора
                $stmt = $db->prepare("SELECT * FROM admins WHERE login = ? LIMIT 1");
                $stmt->execute([$login]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin) {
                    if (password_verify($password, $admin['password_hash'])) {
                        session_regenerate_id(true);
                        
                        $_SESSION['admin'] = true;
                        $_SESSION['login'] = $admin['login'];
                        $_SESSION['last_activity'] = time();
                        
                        header('Location: admin/admin.php');
                        exit();
                    } else {
                        $error = 'Неверный пароль администратора';
                    }
                } else {
                    // Проверка обычного пользователя
                    $stmt = $db->prepare("SELECT * FROM users WHERE login = ? LIMIT 1");
                    $stmt->execute([$login]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        if (password_verify($password, $user['password_hash'])) {
                            session_regenerate_id(true);
                            
                            $_SESSION['user'] = true;
                            $_SESSION['login'] = $user['login'];
                            $_SESSION['last_activity'] = time();
                            
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
                error_log("Database error during login: " . $e->getMessage());
                $error = 'Ошибка базы данных';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Вход в систему</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">  
    <div class="form-container">
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group">
                <label>Логин:</label>
                <input type="text" name="login" required maxlength="50" pattern="[a-zA-Z0-9_]+">
            </div>
            <div class="form-group">
                <label>Пароль:</label>
                <input type="password" name="pass" required minlength="8" maxlength="64">
            </div>
            <div class="form-actions">
                <input type="submit" value="Войти">
            </div>
        </form>
    </div>
</body>
</html>
