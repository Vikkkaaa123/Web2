<?php
require 'db.php';

try {
    // Тест подключения
    $db->query("SELECT 1");
    echo "DB connection: OK<br>";
    
    // Тест запроса админа
    $stmt = $db->prepare("SELECT login, password_hash FROM admins WHERE login = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    echo "Admin data: <pre>".print_r($admin, true)."</pre>";
    echo "Password verify: ".password_verify('123', $admin['password_hash']) ? 'OK' : 'FAIL';
    
} catch (PDOException $e) {
    die("DB ERROR: ".$e->getMessage());
}
