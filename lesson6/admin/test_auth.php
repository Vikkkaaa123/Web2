<?php
require 'db.php';
require 'auth.php';

// Принудительная авторизация для теста
$_SERVER['PHP_AUTH_USER'] = 'admin';
$_SERVER['PHP_AUTH_PW'] = '123';

checkAdminAuth();

echo "Доступ разрешен!";
?>
