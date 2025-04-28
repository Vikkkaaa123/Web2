<?php
function checkAdminAuth() {
    global $db;
    
    // Проверяем, что авторизация Basic отправлена
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        header('HTTP/1.1 401 Unauthorized');
        exit('<h1>Требуется авторизация</h1>');
    }

    // Получаем введенные данные
    $input_login = $_SERVER['PHP_AUTH_USER'];
    $input_password = $_SERVER['PHP_AUTH_PW'] ?? '';

    // Экстренный лог (удалите после отладки)
    file_put_contents('admin_auth_debug.log', 
        date('Y-m-d H:i:s')." - Attempt: $input_login\n", 
        FILE_APPEND);

    // Запрос к базе с обработкой ошибок
    try {
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ? LIMIT 1");
        $stmt->execute([$input_login]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            throw new Exception("Admin not found");
        }

        // Валидация пароля
        if (!password_verify($input_password, $admin['password_hash'])) {
            throw new Exception("Password mismatch");
        }

        // Успешная авторизация
        return true;

    } catch (Exception $e) {
        // Подробное логирование ошибки
        $error_msg = sprintf(
            "FAILED LOGIN: %s | IP: %s | Error: %s\n",
            $input_login,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $e->getMessage()
        );
        file_put_contents('admin_auth_errors.log', $error_msg, FILE_APPEND);
        
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        header('HTTP/1.1 401 Unauthorized');
        exit('<h1>Неверные учетные данные</h1>');
    }
}
?>
