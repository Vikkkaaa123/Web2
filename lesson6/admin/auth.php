<?php
require 'db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

function checkAdminAuth() {
    global $db;
    
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        header('HTTP/1.1 401 Unauthorized');
        exit('Требуется авторизация');
    }

    $input_login = $_SERVER['PHP_AUTH_USER'];
    $input_pass = $_SERVER['PHP_AUTH_PW'] ?? '';

    error_log("Login attempt: ".$input_login);

    try {
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ? LIMIT 1");
        $stmt->execute([$input_login]);
        $admin = $stmt->fetch();

        if (!$admin) {
            error_log("Admin not found: ".$input_login);
            throw new Exception("Admin not found");
        }

        if (!password_verify($input_pass, $admin['password_hash'])) {
            error_log("Password mismatch for: ".$input_login);
            throw new Exception("Password mismatch");
        }

        // Успешная авторизация
        return true;

    } catch (Exception $e) {
        error_log("Auth error: ".$e->getMessage());
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        header('HTTP/1.1 401 Unauthorized');
        exit('Неверные учетные данные');
    }
}
