<?php
require_once 'auth.php';
checkAdminAuth();

$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// 1. Сначала получаем ВСЕ заявки
$applications = $db->query("SELECT * FROM applications ORDER BY id")->fetchAll();

// 2. Для каждой заявки получаем дополнительные данные
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
        SELECT GROUP_CONCAT(p.name SEPARATOR ', ') 
        FROM programming_languages p
        JOIN application_languages al ON p.id = al.language_id
        WHERE al.application_id = ?
    ");
    $stmt->execute([$app['id']]);
    $app['languages'] = $stmt->fetchColumn() ?: 'Не указано';
}

// 3. Статистика по языкам
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
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Админ-панель</h1>
    
    <h2>Статистика по языкам программирования</h2>
    <table>
        <tr><th>Язык</th><th>Количество выборов</th></tr>
        <?php foreach ($stats as $stat): ?>
        <tr>
            <td><?= htmlspecialchars($stat['name']) ?></td>
            <td><?= $stat['count'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Все заявки пользователей (всего: <?= count($applications) ?>)</h2>
    <table>
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
                <a href="edit.php?id=<?= $app['id'] ?>">Редактировать</a> |
                <a href="delete.php?id=<?= $app['id'] ?>" onclick="return confirm('Удалить эту заявку?')">Удалить</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
    <div style="text-align: right; margin-bottom: 20px;">
    <form action="logout.php" method="post">
        <button type="submit" style="background: #f44336; color: white; padding: 8px 15px; border: none; cursor: pointer;">
            Выйти из админ-панели
        </button>
    </form>
</div>
</html>
