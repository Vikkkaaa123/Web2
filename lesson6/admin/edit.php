<?php
require_once 'auth.php';

// Редактирование данных
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $db->prepare("UPDATE applications SET full_name = ?, phone = ? WHERE id = ?");
    $stmt->execute([$_POST['full_name'], $_POST['phone'], $_POST['id']]);
    header('Location: index.php');
}

// Получение данных для формы
$app = $db->query("SELECT * FROM applications WHERE id = " . (int)$_GET['id'])->fetch();
?>

<form method="POST">
    <input type="hidden" name="id" value="<?= $app['id'] ?>">
    <input type="text" name="full_name" value="<?= htmlspecialchars($app['full_name']) ?>">
    <input type="tel" name="phone" value="<?= htmlspecialchars($app['phone']) ?>">
    <button>Сохранить</button>
</form>