<?php
function checkAdminAuth() {
    if (empty($_SESSION['admin'])) {
        if (empty($_SERVER['PHP_AUTH_USER']) || 
            empty($_SERVER['PHP_AUTH_PW'])) {
            
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="Admin Panel"');
            die('<h1>401 Требуется авторизация</h1>');
        }
        
        // Проверка учетных данных
        global $db;
        $login = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];
        
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        $admin = $stmt->fetch();
        
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="Admin Panel"');
            die('<h1>401 Неверные учетные данные</h1>');
        }
        
        // Успешная авторизация
        $_SESSION['admin'] = true;
    }
}
?>
