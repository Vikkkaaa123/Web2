<?php
session_start();

function checkAdminAuth() {
    // Проверяем сессию
    if (!empty($_SESSION['admin'])) {
        return true;
    }

    // Проверяем HTTP Basic Auth
    if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
        $db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178');
        
        $stmt = $db->prepare("SELECT * FROM admins WHERE login = ?");
        $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
            $_SESSION['admin'] = true;
            return true;
        }
    }

    // Если не авторизован
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    die('Требуется авторизация');
}
?>
