<?php
// Включение всех ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

// Жестко прописанные тестовые данные для админа
define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASS_HASH', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

// Подключение к БД с обработкой ошибок
try {
    $db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Ошибка подключения к базе: " . $e->getMessage());
}

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['pass'] ?? '');

    // Проверка администратора
    if ($login === ADMIN_LOGIN) {
        if (password_verify($password, ADMIN_PASS_HASH)) {
            $_SESSION['admin'] = true;
            $_SESSION['admin_login'] = ADMIN_LOGIN;
            header('Location: admin/admin.php');
            exit();
        } else {
            $error = 'Неверный пароль администратора';
            
            // Отладочная информация
            error_log("Admin login failed: " . print_r([
                'input_login' => $login,
                'input_pass' => $password,
                'expected_hash' => ADMIN_PASS_HASH,
                'verify_result' => password_verify($password, ADMIN_PASS_HASH)
            ], true));
        }
    } else {
        // Проверка обычного пользователя
        try {
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
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных';
            error_log("DB error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Вход в систему</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 20px auto; padding: 20px; }
        .error { color: red; margin: 10px 0; }
        input { width: 100%; padding: 8px; margin: 5px 0 15px; box-sizing: border-box; }
        button { background: #4CAF50; color: white; border: none; padding: 10px; width: 100%; }
    </style>
</head>
<body>
    <h1>Вход в систему</h1>
    
    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div>
            <label>Логин:</label>
            <input type="text" name="login" required>
        </div>
        <div>
            <label>Пароль:</label>
            <input type="password" name="pass" required>
        </div>
        <button type="submit">Войти</button>
    </form>

    <div style="margin-top: 30px; padding: 15px; background: #f5f5f5;">
        <h3>Тестовые данные:</h3>
        <p><strong>Администратор:</strong> <?= ADMIN_LOGIN ?> / 123</p>
        <p><strong>Хеш пароля:</strong> <?= ADMIN_PASS_HASH ?></p>
        <p><strong>Проверка хеша:</strong> 
            <?= password_verify('123', ADMIN_PASS_HASH) ? 'OK' : 'Ошибка' ?>
        </p>
    </div>
</body>
</html>
