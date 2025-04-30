<?php
session_start();

// Подключение к базе данных
$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['pass']);
    
    // Проверка администратора (жестко прописаны тестовые данные)
    if ($login === 'admin') {
        $correct_hash = '$2y$10$LTUiG8mY0j7Dd3YOrxZWwujQrbBm/NgJ8OvAn762Sp5fmUEgcKzXi';
        
        if ($password === '123' || password_verify($password, $correct_hash)) {
            $_SESSION['admin'] = true;
            $_SESSION['login'] = 'admin';
            header('Location: admin/admin.php');
            exit();
        } else {
            $error = 'Неверный пароль администратора';
        }
    } 
    // Проверка обычного пользователя
    else {
        $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        
        if ($user = $stmt->fetch()) {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = true;
                $_SESSION['login'] = $user['login'];
                header('Location: index.php');
                exit();
            } else {
                $error = 'Неверный пароль';
            }
        } else {
            $error = 'Пользователь не найден';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Вход в систему</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #4CAF50; color: white; border: none; padding: 10px; width: 100%; }
        .error { color: red; margin: 10px 0; }
        .admin-test { margin-top: 20px; padding: 10px; background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Вход в систему</h1>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
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
    
    <div class="admin-test">
        <h3>Тестовые данные:</h3>
        <p><strong>Администратор:</strong> admin / 123</p>
        <p><strong>Обычный пользователь:</strong> ваш_логин / ваш_пароль</p>
    </div>
</body>
</html>
