<?php
// Подключение к БД
$db = new PDO('mysql:host=localhost;dbname=u68606', 'u68606', '9347178', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// HTTP-авторизация
if (empty($_SERVER['PHP_AUTH_USER']) || 
    empty($_SERVER['PHP_AUTH_PW']) ||
    !checkAdminCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $db)) {
    
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    die('<h1>401 Требуется авторизация</h1>');
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_id'])) {
        deleteApplication($_POST['delete_id'], $db);
    } elseif (isset($_POST['edit_id'])) {
        header("Location: edit.php?id=".$_POST['edit_id']);
        exit;
    }
    header('Location: admin.php');
    exit;
}

// Получение данных
$applications = getApplications($db);
$stats = getLanguageStats($db);

// Функции
function checkAdminCredentials($login, $password, $db) {
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();
    return $admin && password_verify($password, $admin['password_hash']);
}

function getApplications($db) {
    return $db->query("
        SELECT a.*, GROUP_CONCAT(l.name SEPARATOR ', ') as languages 
        FROM applications a
        LEFT JOIN application_languages al ON a.id = al.application_id
        LEFT JOIN programming_languages l ON al.language_id = l.id
        GROUP BY a.id
        ORDER BY a.id DESC
    ")->fetchAll();
}

function getLanguageStats($db) {
    return $db->query("
        SELECT l.name, COUNT(al.application_id) as count
        FROM programming_languages l
        LEFT JOIN application_languages al ON l.id = al.language_id
        GROUP BY l.id
        ORDER BY count DESC
    ")->fetchAll();
}

function deleteApplication($id, $db) {
    $db->beginTransaction();
    try {
        $db->exec("DELETE FROM application_languages WHERE application_id = $id");
        $db->exec("DELETE FROM user_applications WHERE application_id = $id");
        $db->exec("DELETE FROM applications WHERE id = $id");
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-container">
        <h1>Панель администратора</h1>
        
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
                <th>Биография</th>
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
                <td><?= htmlspecialchars(substr($app['biography'], 0, 50)) ?>...</td>
                <td><?= htmlspecialchars($app['languages']) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="edit_id" value="<?= $app['id'] ?>">
                        <button type="submit">Редактировать</button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить эту заявку?')">
                        <input type="hidden" name="delete_id" value="<?= $app['id'] ?>">
                        <button type="submit">Удалить</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
