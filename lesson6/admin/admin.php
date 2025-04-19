<?php
// 1. Включение максимального вывода ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

// 2. Явная проверка получения данных авторизации
echo "<pre>";
echo "Данные сервера:\n";
var_dump([
    'PHP_AUTH_USER' => $_SERVER['PHP_AUTH_USER'] ?? 'NOT SET',
    'PHP_AUTH_PW' => $_SERVER['PHP_AUTH_PW'] ?? 'NOT SET',
    'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT SET'
]);
echo "</pre>";

// 3. Если данные не получены - запрашиваем авторизацию
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.1 401 Unauthorized');
    die('<h1>Введите логин и пароль</h1>');
}

// 4. Подключение к БД с явной проверкой
try {
    $db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // 5. Проверка существования таблицы admins
    $tableExists = $db->query("SHOW TABLES LIKE 'admins'")->rowCount() > 0;
    if (!$tableExists) {
        die("Таблица admins не существует в БД");
    }
    
    // 6. Проверка наличия пользователя admin
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        die("Пользователь '{$_SERVER['PHP_AUTH_USER']}' не найден в БД");
    }
    
    // 7. Визуализация проверки пароля
    echo "<pre>";
    echo "Проверка пароля:\n";
    echo "Введённый пароль: {$_SERVER['PHP_AUTH_PW']}\n";
    echo "Хеш из БД: {$admin['password_hash']}\n";
    echo "Результат password_verify(): " . (password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash']) ? 'TRUE' : 'FALSE');
    echo "</pre>";
    
    // 8. Финальная проверка
    if (!password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
        header('HTTP/1.1 403 Forbidden');
        die('<h1>Неверный логин или пароль (финальная проверка)</h1>');
    }
    
    // 9. Если дошли сюда - авторизация успешна
    echo "<h1>Добро пожаловать, {$_SERVER['PHP_AUTH_USER']}!</h1>";
    echo "<p>Вы успешно авторизовались в системе.</p>";
    
} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}
?>
