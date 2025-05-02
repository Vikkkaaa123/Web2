<?php
require_once 'auth.php';
checkAdminAuth();

$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Получаем все заявки
$applications = $db->query("SELECT * FROM applications ORDER BY id")->fetchAll();

// Для каждой заявки получаем дополнительные данные
foreach ($applications as &$app) {
    // Получаем логин пользователя
    $stmt = $db->prepare("
        SELECT u.login 
        FROM users u
        JOIN user_applications ua ON u.id = ua.user_id
        WHERE ua.application_id = ?
    ");
    $stmt->execute([$app['id']]);
    $app['user_login'] = $stmt->fetchColumn();
    
    // Получаем языки программирования
    $stmt = $db->prepare("
        SELECT COALESCE(GROUP_CONCAT(p.name SEPARATOR ', '), 'Не указано') 
        FROM programming_languages p
        JOIN application_languages al ON p.id = al.language_id
        WHERE al.application_id = ?
    ");
    $stmt->execute([$app['id']]);
    $app['languages'] = $stmt->fetchColumn();
}

// Статистика по языкам
$stats = $db->query("
    SELECT p.name, COUNT(DISTINCT al.application_id) as count
    FROM programming_languages p
    LEFT JOIN application_languages al ON p.id = al.language_id
    GROUP BY p.id
    ORDER BY count DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Админ-панель</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-container">
        <h1>Админ-панель</h1>
        
        <div class="stats">
            <h2>Статистика по языкам программирования</h2>
            <table class="admin-table">
                <tr><th>Язык</th><th>Количество выборов</th></tr>
                <?php foreach ($stats as $stat): ?>
                <tr>
                    <td><?= htmlspecialchars($stat['name']) ?></td>
                    <td><?= $stat['count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <h2>Все заявки пользователей (всего: <?= count($applications) ?>)</h2>
        <table class="admin-table">
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>ФИО</th>
                <th>Email</th>
                <th>Языки</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($applications as $app): ?>
            <tr>
                <td><?= $app['id'] ?></td>
                <td><?= htmlspecialchars($app['user_login'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($app['full_name']) ?></td>
                <td><?= htmlspecialchars($app['email']) ?></td>
                <td><?= htmlspecialchars($app['languages']) ?></td>
                <td>
                    <a href="edit.php?id=<?= $app['id'] ?>" class="button edit">Редактировать</a>
                    <a href="delete.php?id=<?= $app['id'] ?>" class="button delete" onclick="return confirm('Удалить эту заявку?')">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="action-buttons">
            <form action="logout.php" method="post">
                <button type="submit" class="button delete">Выйти из админ-панели</button>
            </form>
        </div>
    </div>
</body>
</html>
