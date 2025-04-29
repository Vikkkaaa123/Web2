<?php
require 'db.php';

// Функция для извлечения HTTP-авторизации при FastCGI
function getAuthCredentials() {
    $auth = null;
    
    // Попробуем получить из стандартного места
    if (isset($_SERVER['PHP_AUTH_USER'])) {
        return [$_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ?? ''];
    }
    
    // Попробуем из заголовков (для FastCGI)
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    
    if ($auth && strpos($auth, 'Basic ') === 0) {
        $decoded = base64_decode(substr($auth, 6));
        return explode(':', $decoded, 2);
    }
    
    return [null, null];
}

// Получаем логин и пароль
[$login, $password] = getAuthCredentials();

// Проверяем авторизацию
if (!$login || !$password) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.1 401 Unauthorized');
    exit('Требуется авторизация');
}

// Проверка в БД
try {
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();
    
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        throw new Exception('Неверные учетные данные');
    }
    
} catch (Exception $e) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.1 401 Unauthorized');
    exit($e->getMessage());
}
?>
