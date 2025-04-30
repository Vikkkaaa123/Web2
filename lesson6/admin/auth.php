<?php
function checkAdminAuth() {
    session_start();
    
    if (!empty($_SESSION['admin'])) {
        return; // Уже авторизован
    }

    // Если нет сессии, пробуем HTTP Basic Auth
    if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
        global $db;
        $login = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

        $stmt = $db->prepare("SELECT id FROM admins WHERE login = ? AND password_hash = ?");
        $stmt->execute([$login, password_hash($password, PASSWORD_DEFAULT)]);
        
        if ($stmt->fetch()) {
            $_SESSION['admin'] = true;
            $_SESSION['admin_login'] = $login;
            return;
        }
    }

    // Если дошли сюда - не авторизован
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    die('<h1>401 Требуется авторизация</h1>');
}
?>
