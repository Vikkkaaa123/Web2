<?php
require 'auth.php';

echo "<pre>";
var_dump([
    'PHP_AUTH_USER' => $_SERVER['PHP_AUTH_USER'] ?? 'NOT SET',
    'PHP_AUTH_PW' => $_SERVER['PHP_AUTH_PW'] ?? 'NOT SET',
    'DB Status' => 'Connected',
    'PHP Version' => phpversion()
]);
echo "</pre>";

checkAdminAuth();

echo "<h1>Вы успешно авторизованы!</h1>";
