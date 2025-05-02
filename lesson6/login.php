<?php
session_start();

$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['pass']);
    
    try {
        // Сначала проверяем администратора
        $stmt = $db->prepare("SELECT * FROM admins WHERE login = ?");
        $stmt->execute([$login]);
        
        if ($admin = $stmt->fetch()) {
            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin'] = true;
                $_SESSION['login'] = $admin['login'];
                header('Location: admin/admin.php');
                exit();
            } else {
                $error = 'Неверный пароль администратора';
            }
        }
        // Если не админ, проверяем обычного пользователя
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
                    $error = 'Неверный пароль пользователя';
                }
            } else {
                $error = 'Пользователь не найден';
            }
        }
    } catch (PDOException $e) {
        $error = 'Ошибка базы данных';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Вход в систему</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">  
    <div class="form-container">
        
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
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
            <div class="form-actions">
                <input type="submit" value="Войти">
            </div>
        </form>
    </div>
</body>
</html>
