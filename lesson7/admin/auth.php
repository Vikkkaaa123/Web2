<?php
// Настройки безопасности сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

header('Content-Type: text/html; charset=UTF-8');
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

require_once __DIR__ . '/../db.php';

function checkAdminAuth() {
    if (!empty($_SESSION['admin']) {
        return true;
    }

    if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
        $db = connectDB();
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        try {
            $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
            $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
                $_SESSION['admin'] = true;
                $_SESSION['login'] = htmlspecialchars($_SERVER['PHP_AUTH_USER'], ENT_QUOTES, 'UTF-8');
                return true;
            }
        } catch (PDOException $e) {
            error_log("Admin auth error: " . $e->getMessage());
        }
    }

    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.1 401 Unauthorized');
    exit('Требуется авторизация');
}
?>
