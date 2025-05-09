<?php
require_once __DIR__ . '/../db.php';
$db = connectDB();

session_start();
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Настройка безопасности сессии
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');

function checkAdminAuth() {
    // Проверка аутентификации в сессии
    if (!empty($_SESSION['admin'])) {
        // Проверка времени бездействия (30 минут)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            header('Location: ../login.php');
            exit();
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    // Проверка HTTP Basic аутентификации
    if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
        try {
            $db = connectDB();
            
            $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ? LIMIT 1");
            $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin'] = true;
                $_SESSION['login'] = $_SERVER['PHP_AUTH_USER'];
                $_SESSION['last_activity'] = time();
                return true;
            }
        } catch (PDOException $e) {
            error_log("Admin auth error: " . $e->getMessage());
        }
    }

    // Запрос аутентификации
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.1 401 Unauthorized');
    die('Требуется авторизация администратора');
}
?>
