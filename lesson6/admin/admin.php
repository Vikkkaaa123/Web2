<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

checkAdminAuth();
$db = connectDB();

// Получаем все заявки 
$applications = $db->query("SELECT * FROM applications ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$processedApplications = [];

foreach ($applications as $app) {
    
    // Логин пользователя
    $stmt = $db->prepare("SELECT u.login FROM users u 
                         JOIN user_applications ua ON u.id = ua.user_id 
                         WHERE ua.application_id = ?");
    $stmt->execute([$app['id']]);
    $app['user_login'] = $stmt->fetchColumn();
    
    // Языки программирования
    $stmt = $db->prepare("SELECT GROUP_CONCAT(p.name SEPARATOR ', ') 
                         FROM programming_languages p 
                         JOIN application_languages al ON p.id = al.language_id 
                         WHERE al.application_id = ?");
    $stmt->execute([$app['id']]);
    $app['languages'] = $stmt->fetchColumn() ?: 'Не указано';
    
    // Преобразуем пол в сокращенный формат
    $app['gender_short'] = ($app['gender'] == 'male') ? 'м' : 'ж';
    
    // Добавляем в новый массив
    $processedApplications[] = $app;
}

// Статистика по языкам
$stats = $db->query("SELECT p.name, COUNT(DISTINCT al.application_id) as count 
                    FROM programming_languages p 
                    LEFT JOIN application_languages al ON p.id = al.language_id 
                    GROUP BY p.id 
                    ORDER BY count DESC")->fetchAll();
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
        <a href="logout.php" class="button admin-logout">Выйти</a>
        <div class="stats">
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
        </div>

        <h2>Все заявки пользователей (всего: <?= count($processedApplications) ?>)</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>ФИО</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Дата рождения</th>
                <th>Пол</th>
                <th>Языки</th>
                <th>Биография</th>
                <th>Согласие</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($processedApplications as $app): ?>
            <tr>
                <td><?= $app['id'] ?></td>
                <td><?= htmlspecialchars($app['user_login'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($app['full_name']) ?></td>
                <td><?= htmlspecialchars($app['email']) ?></td>
                <td><?= htmlspecialchars($app['phone']) ?></td>
                <td><?= htmlspecialchars($app['birth_date']) ?></td>
                <td><?= $app['gender_short'] ?></td>
                <td><?= htmlspecialchars($app['languages']) ?></td>
                <td><?= htmlspecialchars(substr($app['biography'], 0, 50)) . (strlen($app['biography']) > 50 ? '...' : '') ?></td>
                <td><?= $app['agreement'] ? 'Да' : 'Нет' ?></td>
                <td>
                    <a href="edit.php?id=<?= $app['id'] ?>" class="button">Редактировать</a>
                    <a href="delete.php?id=<?= $app['id'] ?>" class="button" onclick="return confirm('Удалить эту заявку?')">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
