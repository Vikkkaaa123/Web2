<?php
// Проверка авторизации администратора
$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178');

if (empty($_SERVER['PHP_AUTH_USER']) || 
    empty($_SERVER['PHP_AUTH_PW']) ||
    !checkAdminCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $db)) {
    
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    die('<h1>401 Требуется авторизация</h1>');
}

function checkAdminCredentials($login, $password, $db) {
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();
    return $admin && password_verify($password, $admin['password_hash']);
}
?>