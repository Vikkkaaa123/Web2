<?php
require 'auth.php';

echo "<h2>Данные авторизации:</h2>";
echo "<pre>";
print_r([
    'Полученные данные' => [
        'Логин' => $login ?? 'Не получен',
        'Пароль' => $password ? '******' : 'Не получен'
    ],
    'Заголовки сервера' => [
        'PHP_AUTH_USER' => $_SERVER['PHP_AUTH_USER'] ?? 'Отсутствует',
        'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'Отсутствует',
        'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'Отсутствует'
    ],
    'Версия PHP' => phpversion(),
    'Статус БД' => 'Подключено'
]);
echo "</pre>";

echo "<h1 style='color:green'>Вы успешно авторизованы как: " . htmlspecialchars($login) . "</h1>";
