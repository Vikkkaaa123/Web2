<?php
require_once 'auth.php';
checkAdminAuth();

$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178');
$userId = $_GET['id'];

// Логика сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE users SET login = ?, email = ? WHERE id = ?");
    $stmt->execute([$_POST['login'], $_POST['email'], $userId]);
    header('Location: admin.php');
    exit();
}

// Получение данных пользователя
$user = $db->query("SELECT * FROM users WHERE id = $userId")->fetch();
?>
<!DOCTYPE html>
<html>
<body>
    <h1>Редактирование пользователя</h1>
    <form method="POST">
        <input type="text" name="login" value="<?= htmlspecialchars($user['login']) ?>">
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
        <button type="submit">Сохранить</button>
    </form>
</body>
</html>
