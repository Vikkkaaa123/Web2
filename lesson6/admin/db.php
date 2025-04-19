<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

$user = 'u68606';
$pass = '9347178';

try {
    $db = new PDO('mysql:host=localhost;dbname=u68606', $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}
?>
