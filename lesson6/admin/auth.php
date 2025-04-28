<?php
require 'db.php';

// Проверка учетных данных в БД
function checkAdminAuth() {
    global $db;
    
    if (empty($_SERVER['PHP_AUTH_USER']) {
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        header('HTTP/1.1 401 Unauthorized');
        exit('Требуется авторизация');
    }

    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        header('HTTP/1.1 401 Unauthorized');
        exit('Неверные учетные данные');
    }
}
