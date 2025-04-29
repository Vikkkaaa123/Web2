<?php
try {
    // Подключение к базе данных
    $db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => true 
    ]);
    
    error_log("[" . date('Y-m-d H:i:s') . "] DB connection successful to database: u68606");
    
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] DB connection failed: " . $e->getMessage());
    
    die("Ошибка подключения к базе данных. Пожалуйста, попробуйте позже.");
}
?>
