<?php
require_once __DIR__ . '/auth.php'; 
require_once __DIR__ . '/../db.php';

checkAdminAuth();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

try {
    $db = connectDB(); 

    // Получаем все заявки с защитой от SQL-инъекций
    $stmt = $db->prepare("SELECT * FROM applications ORDER BY id");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $processedApplications = [];

    foreach ($applications as $app) {
        // Получаем логин пользователя
        $stmt = $db->prepare("SELECT u.login FROM users u 
                             JOIN user_applications ua ON u.id = ua.user_id 
                             WHERE ua.application_id = ?");
        $stmt->execute([$app['id']]);
        $app['user_login'] = $stmt->fetchColumn();
        
        // Получаем языки программирования
        $stmt = $db->prepare("SELECT GROUP_CONCAT(p.name SEPARATOR ', ') 
                             FROM programming_languages p 
                             JOIN application_languages al ON p.id = al.language_id 
                             WHERE al.application_id = ?");
        $stmt->execute([$app['id']]);
        $app['languages'] = $stmt->fetchColumn() ?: 'Не указано';
        
        // Преобразуем пол
        $app['gender_short'] = ($app['gender'] == 'male') ? 'м' : 'ж';
        
        // Экранируем все данные перед выводом
        $app['full_name'] = htmlspecialchars($app['full_name'], ENT_QUOTES, 'UTF-8');
        $app['email'] = htmlspecialchars($app['email'], ENT_QUOTES, 'UTF-8');
        $app['phone'] = htmlspecialchars($app['phone'], ENT_QUOTES, 'UTF-8');
        $app['biography'] = htmlspecialchars($app['biography'], ENT_QUOTES, 'UTF-8');
        $app['user_login'] = htmlspecialchars($app['user_login'], ENT_QUOTES, 'UTF-8');
        $app['languages'] = htmlspecialchars($app['languages'], ENT_QUOTES, 'UTF-8');
        
        $processedApplications[] = $app;
    }

    // Статистика по языкам
    $stmt = $db->prepare("SELECT p.name, COUNT(DISTINCT al.application_id) as count 
                         FROM programming_languages p 
                         LEFT JOIN application_languages al ON p.id = al.language_id 
                         GROUP BY p.id 
                         ORDER BY count DESC");
    $stmt->execute();
    $stats = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Admin panel database error: " . $e->getMessage());
    die("Ошибка загрузки данных. Пожалуйста, попробуйте позже.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Админ-панель</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:">
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
                    <td><?php echo htmlspecialchars($stat['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int)$stat['count']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <h2>Все заявки пользователей (всего: <?php echo count($processedApplications); ?>)</h2>
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
                <td><?php echo (int)$app['id']; ?></td>
                <td><?php echo $app['user_login']; ?></td>
                <td><?php echo $app['full_name']; ?></td>
                <td><?php echo $app['email']; ?></td>
                <td><?php echo $app['phone']; ?></td>
                <td><?php echo htmlspecialchars($app['birth_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo $app['gender_short']; ?></td>
                <td><?php echo $app['languages']; ?></td>
                <td><?php echo mb_substr($app['biography'], 0, 50, 'UTF-8') . (mb_strlen($app['biography'], 'UTF-8') > 50 ? '...' : ''); ?></td>
                <td><?php echo $app['agreement'] ? 'Да' : 'Нет'; ?></td>
                <td>
                    <a href="edit.php?id=<?php echo (int)$app['id']; ?>" class="button">Редактировать</a>
                    <a href="delete.php?id=<?php echo (int)$app['id']; ?>" class="button" onclick="return confirm('Вы уверены, что хотите удалить эту заявку?')">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
