<?php
require_once 'auth.php';
checkAdminAuth();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Проверка CSRF токена для POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Недействительный CSRF токен');
    }
}

try {
    $db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    $appId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($appId === false || $appId === null) {
        die('Неверный ID заявки');
    }

    $db->beginTransaction();

    // Удаление связей с языками
    $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
    $stmt->execute([$appId]);

    // Удаление связи с пользователем
    $stmt = $db->prepare("DELETE FROM user_applications WHERE application_id = ?");
    $stmt->execute([$appId]);

    // Удаление самой заявки
    $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->execute([$appId]);

    $db->commit();

    header('Location: admin.php');
    exit();

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Delete application error: " . $e->getMessage());
    die('Ошибка при удалении заявки. Пожалуйста, попробуйте позже.');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Удаление заявки</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-container">
        <h1>Удаление заявки</h1>
        <p>Вы уверены, что хотите удалить эту заявку?</p>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-actions">
                <button type="submit" class="button">Да, удалить</button>
                <a href="admin.php" class="button">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html>
