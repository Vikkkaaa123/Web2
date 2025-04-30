<?php
session_start();
require 'db_connect.php'; // Подключение к БД

$error = '';

// Если уже авторизован - перенаправляем
if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}
if (!empty($_SESSION['admin'])) {
    header('Location: admin/admin.php');
    exit();
}

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_type'])) {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['pass'] ?? '');

    try {
        // Проверка администратора
        if ($_POST['login_type'] === 'admin') {
            $stmt = $db->prepare("SELECT * FROM admins WHERE login = ?");
            $stmt->execute([$login]);
            
            if ($admin = $stmt->fetch()) {
                if (password_verify($password, $admin['password_hash'])) {
                    $_SESSION['admin'] = true;
                    $_SESSION['admin_login'] = $login;
                    header('Location: admin/admin.php');
                    exit();
                } else {
                    $error = 'Неверный пароль администратора';
                }
            } else {
                $error = 'Администратор с таким логином не найден';
            }
        }
        // Проверка обычного пользователя
        else {
            $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
            $stmt->execute([$login]);
            
            if ($user = $stmt->fetch()) {
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['login'] = $user['login'];
                    $_SESSION['uid'] = $user['id'];
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Неверный пароль пользователя';
                }
            } else {
                $error = 'Пользователь не найден';
            }
        }
    } catch (PDOException $e) {
        $error = 'Ошибка базы данных: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Вход в систему</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 20px auto; padding: 20px; }
        .login-form, .admin-register { background: #f9f9f9; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #4CAF50; color: white; border: none; padding: 10px; width: 100%; cursor: pointer; }
        button:hover { background: #45a049; }
        .error { color: red; margin: 10px 0; }
        .toggle-form { text-align: center; margin-top: 15px; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <h1>Вход в систему</h1>
    
    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- Форма входа для пользователей -->
    <div class="login-form" id="userLoginForm">
        <h2>Вход для пользователей</h2>
        <form method="POST">
            <input type="hidden" name="login_type" value="user">
            <div class="form-group">
                <label>Логин:</label>
                <input type="text" name="login" required>
            </div>
            <div class="form-group">
                <label>Пароль:</label>
                <input type="password" name="pass" required>
            </div>
            <button type="submit">Войти</button>
        </form>
        <div class="toggle-form">
            <a href="#" onclick="showAdminForm()">Вход для администратора</a>
        </div>
    </div>
    
    <!-- Форма входа для администратора -->
    <div class="login-form hidden" id="adminLoginForm">
        <h2>Вход для администратора</h2>
        <form method="POST">
            <input type="hidden" name="login_type" value="admin">
            <div class="form-group">
                <label>Логин администратора:</label>
                <input type="text" name="login" required>
            </div>
            <div class="form-group">
                <label>Пароль:</label>
                <input type="password" name="pass" required>
            </div>
            <button type="submit">Войти</button>
        </form>
        <div class="toggle-form">
            <a href="#" onclick="showUserForm()">Вход для пользователей</a>
        </div>
    </div>
    
    <!-- Блок регистрации администратора -->
    <div class="admin-register">
        <h3>Нет доступа администратора?</h3>
        <a href="register_admin.php" class="button">Зарегистрировать нового администратора</a>
    </div>

    <script>
        function showAdminForm() {
            document.getElementById('userLoginForm').classList.add('hidden');
            document.getElementById('adminLoginForm').classList.remove('hidden');
            return false;
        }
        
        function showUserForm() {
            document.getElementById('adminLoginForm').classList.add('hidden');
            document.getElementById('userLoginForm').classList.remove('hidden');
            return false;
        }
    </script>
</body>
</html>
