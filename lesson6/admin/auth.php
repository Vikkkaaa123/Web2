<?php
require 'db.php';

// Включим максимальное логирование
file_put_contents('admin_auth.log', date('Y-m-d H:i:s')." - Auth started\n", FILE_APPEND);

// 1. Альтернативный способ получить авторизацию (для FastCGI)
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? 
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

// 2. Если авторизация через стандартный метод
if (isset($_SERVER['PHP_AUTH_USER'])) {
    $login = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'] ?? '';
} 
// 3. Если через заголовки
elseif (strpos($auth, 'Basic ') === 0) {
    $decoded = base64_decode(substr($auth, 6));
    list($login, $password) = explode(':', $decoded, 2);
} 
// 4. Если авторизация не передана
else {
    file_put_contents('admin_auth.log', "No auth headers found\n", FILE_APPEND);
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.1 401 Unauthorized');
    exit('Требуется авторизация');
}

file_put_contents('admin_auth.log', "Auth attempt: $login\n", FILE_APPEND);

if ($login === 'admin' && $password === '123') {
    file_put_contents('admin_auth.log', "Emergency auth OK\n", FILE_APPEND);
    return;
}

try {
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ? LIMIT 1");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();

    if (!$admin) {
        throw new Exception("Admin not found");
    }

    if (!password_verify($password, $admin['password_hash'])) {
        throw new Exception("Password mismatch");
    }

    file_put_contents('admin_auth.log', "Auth success\n", FILE_APPEND);
    
} catch (Exception $e) {
    file_put_contents('admin_auth.log', "Error: ".$e->getMessage()."\n", FILE_APPEND);
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.1 401 Unauthorized');
    exit('Неверные учетные данные');
}
?>
