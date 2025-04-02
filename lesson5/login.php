<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

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

$messages = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['pass'] ?? '');

    try {
        $stmt = $db->prepare("SELECT id, login, password_hash FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php');
            exit();
        } else {
            $messages[] = '<div class="error">Неверный логин или пароль</div>';
        }
    } catch (PDOException $e) {
        $messages[] = '<div class="error">Ошибка при входе в систему</div>';
    }
}

$generated_login = $_COOKIE['login'] ?? '';
$generated_password = $_COOKIE['password'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <style>
        .error { color: red; }
        .form-group { margin: 10px 0; }
        label { display: inline-block; width: 100px; }
    </style>
</head>
<body>
    <h1>Вход в систему</h1>
    
    <?php foreach ($messages as $message): ?>
        <?php echo $message; ?>
    <?php endforeach; ?>
    
    <form method="post">
        <div class="form-group">
            <label for="login">Логин:</label>
            <input type="text" id="login" name="login" required value="<?= htmlspecialchars($generated_login) ?>">
        </div>
        
        <div class="form-group">
            <label for="pass">Пароль:</label>
            <input type="password" id="pass" name="pass" required value="<?= htmlspecialchars($generated_password) ?>">
        </div>
        
        <div class="form-group">
            <input type="submit" value="Войти">
        </div>
    </form>
    
    <p>Нет аккаунта? <a href="index.php">Зарегистрируйтесь</a></p>
</body>
</html>
