<?php
require_once 'db.php';
require_once 'auth.php';

// Проверка авторизации администратора
checkAdminAuth();

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_id'])) {
        deleteApplication($_POST['delete_id'], $db);
        header('Location: admin.php');
        exit;
    }
}

// Получение данных
$applications = getApplications($db);
$stats = getLanguageStats($db);

// Функции
function getApplications($db) {
    $stmt = $db->query("
        SELECT a.*, GROUP_CONCAT(l.name SEPARATOR ', ') as languages 
        FROM applications a
        LEFT JOIN application_languages al ON a.id = al.application_id
        LEFT JOIN programming_languages l ON al.language_id = l.id
        GROUP BY a.id
        ORDER BY a.id DESC
    ");
    return $stmt->fetchAll();
}

function getLanguageStats($db) {
    $stmt = $db->query("
        SELECT l.name, COUNT(al.application_id) as count
        FROM programming_languages l
        LEFT JOIN application_languages al ON l.id = al.language_id
        GROUP BY l.id
        ORDER BY count DESC
    ");
    return $stmt->fetchAll();
}

function deleteApplication($id, $db) {
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $db->prepare("DELETE FROM user_applications WHERE application_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        die("Ошибка удаления: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Админ-панель</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-container">
        <h1>Панель администратора</h1>
        <p>Вы вошли как: <?php echo htmlspecialchars($_SERVER['PHP_AUTH_USER']); ?></p>
        
        <div class="stats">
            <h2>Статистика по языкам программирования</h2>
            <table class="admin-table">
                <tr>
                    <th>Язык</th>
                    <th>Количество пользователей</th>
                </tr>
                <?php foreach ($stats as $stat): ?>
                <tr>
                    <td><?= htmlspecialchars($stat['name']) ?></td>
                    <td><?= $stat['count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <h2>Все заявки</h2>
        <table class="admin-table">
            <tr>
                <th>ID</th>
                <th>ФИО</th>
                <th>Телефон</th>
                <th>Email</th>
                <th>Дата рождения</th>
                <th>Пол</th>
                <th>Языки</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($applications as $app): ?>
            <tr>
                <td><?= $app['id'] ?></td>
                <td><?= htmlspecialchars($app['full_name']) ?></td>
                <td><?= htmlspecialchars($app['phone']) ?></td>
                <td><?= htmlspecialchars($app['email']) ?></td>
                <td><?= date('d.m.Y', strtotime($app['birth_date'])) ?></td>
                <td><?= $app['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
                <td><?= htmlspecialchars($app['languages']) ?></td>
                <td>
                    <a href="edit.php?id=<?= $app['id'] ?>" class="button edit">Редактировать</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить эту заявку?')">
                        <input type="hidden" name="delete_id" value="<?= $app['id'] ?>">
                        <button type="submit" class="button delete">Удалить</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
