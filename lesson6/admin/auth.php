<?php
require '../db.php'; 

// Включим логирование
file_put_contents('admin_auth.log', date('Y-m-d H:i:s')." - Auth started\n", FILE_APPEND);

// 1. Проверка сессии 
session_start();
if (!empty($_SESSION['admin_logged'])) {
    file_put_contents('admin_auth.log', "Session auth OK\n", FILE_APPEND);
    return;
}

// 2. Проверка HTTP-авторизации
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (empty($auth) && isset($_SERVER['PHP_AUTH_USER'])) {
    $login = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'] ?? '';
} elseif (strpos($auth, 'Basic ') === 0) {
    $decoded = base64_decode(substr($auth, 6));
    list($login, $password) = explode(':', $decoded, 2);
} else {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.1 401 Unauthorized');
    exit('Требуется авторизация');
}

// 3. Проверка учетных данных
try {
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        throw new Exception("Invalid credentials");
    }

    // Сохраняем в сессию для единой авторизации
    $_SESSION['admin_logged'] = true;
    file_put_contents('admin_auth.log', "Auth success for $login\n", FILE_APPEND);

} catch (Exception $e) {
    file_put_contents('admin_auth.log', "Auth failed: ".$e->getMessage()."\n", FILE_APPEND);
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.1 401 Unauthorized');
    exit('Неверные учетные данные');
}
?>
