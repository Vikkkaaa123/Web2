<?php
function checkAdminAuth() {
    if (empty($_SERVER['PHP_AUTH_USER']) || 
        empty($_SERVER['PHP_AUTH_PW']) ||
        !verifyAdminCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
        
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        die('<h1>401 Требуется авторизация</h1>');
    }
}

function verifyAdminCredentials($login, $password) {
    global $db;
    
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();
    
    return $admin && password_verify($password, $admin['password_hash']);
}
?>
