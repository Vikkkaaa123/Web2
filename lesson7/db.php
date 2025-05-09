<?php
function connectDB() {
    static $db = null; 

    if ($db === null) {
        $user = 'u68606'; 
        $pass = '9347178';
        
        try {
            $db = new PDO('mysql:host=localhost;dbname=u68606', $user, $pass, [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            die("Ошибка подключения к БД. Пожалуйста, попробуйте позже.");
        }
    }

    return $db;
}
?>
