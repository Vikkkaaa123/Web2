<?php
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);
    
    if (!empty($login) && !empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $db->prepare("INSERT INTO admins (login, password_hash) VALUES (?, ?)");
            $stmt->execute([$login, $hash]);
            echo "Администратор $login успешно зарегистрирован!";
        } catch (PDOException $e) {
            echo "Ошибка: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<body>
    <form method="POST">
        <input type="text" name="login" placeholder="Логин" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Зарегистрировать</button>
    </form>
</body>
</html>
