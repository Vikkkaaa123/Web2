<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Подключение к БД
$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Если уже авторизован - перенаправляем
if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}
if (!empty($_SESSION['admin'])) {
    header('Location: admin/admin.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['pass'] ?? '');

    try {
        // Сначала проверяем администратора
        $stmt = $db->prepare("SELECT * FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        $admin = $stmt->fetch();

        if ($admin) {
            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin'] = true;
                $_SESSION['admin_login'] = $admin['login'];
                header('Location: admin/admin.php');
                exit();
            }
            // Если хеш не совпал, продолжаем проверять как обычного пользователя
        }

        // Проверяем обычного пользователя
        $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php');
            exit();
        }

        $error = 'Неверный логин или пароль';

    } catch (PDOException $e) {
        $error = 'Ошибка базы данных';
        error_log("Login error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>
<!-- Форма входа остается без изменений -->
