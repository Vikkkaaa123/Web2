<?php
// admin/edit.php
session_start();
header('Content-Type: text/html; charset=UTF-8');

$user = 'u68606';
$pass = '9347178';

try {
    $db = new PDO('mysql:host=localhost;dbname=u68606', $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}

// Проверка HTTP-авторизации
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Требуется авторизация';
    exit;
} else {
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Неверные учетные данные';
        exit;
    }
}

// Получение списка языков
function getLangs($db) {
    $stmt = $db->query("SELECT id, name FROM programming_languages");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

$allowed_lang = getLangs($db);

// Получение данных заявки
$id = (int)$_GET['id'];
$stmt = $db->prepare("
    SELECT a.*, GROUP_CONCAT(al.language_id) as languages 
    FROM applications a
    LEFT JOIN application_languages al ON a.id = al.application_id
    WHERE a.id = ?
    GROUP BY a.id
");
$stmt->execute([$id]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    die('Заявка не найдена');
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fio = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $birth_date = $_POST['birth_date'];
    $gender = $_POST['gender'];
    $biography = trim($_POST['biography']);
    $agreement = isset($_POST['agreement']) ? 1 : 0;
    $languages = isset($_POST['languages']) ? $_POST['languages'] : [];

    try {
        $db->beginTransaction();
        
        // Обновление основной информации
        $stmt = $db->prepare("
            UPDATE applications 
            SET full_name = ?, phone = ?, email = ?, birth_date = ?, 
                gender = ?, biography = ?, agreement = ?
            WHERE id = ?
        ");
        $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, $agreement, $id]);
        
        // Удаление старых языков
        $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$id]);
        
        // Добавление новых языков
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($languages as $lang_id) {
            $stmt->execute([$id, $lang_id]);
        }
        
        $db->commit();
        header("Location: admin.php?updated=1");
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        die('Ошибка при обновлении: ' . $e->getMessage());
    }
}

// Разделение даты на компоненты
$birth_date = $application['birth_date'];
list($year, $month, $day) = explode('-', $birth_date);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование заявки</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="email"], input[type="tel"], 
        textarea, select { width: 100%; padding: 8px; }
        .date-group { display: flex; gap: 10px; }
        .date-group input { width: 60px; }
        .form-actions { margin-top: 20px; }
        .btn { padding: 8px 15px; cursor: pointer; }
        .btn-save { background-color: #4CAF50; color: white; border: none; }
        .btn-cancel { background-color: #f44336; color: white; border: none; }
    </style>
</head>
<body>
    <h1>Редактирование заявки #<?= $id ?></h1>
    
    <form method="post">
        <div class="form-group">
            <label for="full_name">ФИО:</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($application['full_name']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Телефон:</label>
            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($application['phone']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($application['email']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Дата рождения:</label>
            <input type="date" name="birth_date" value="<?= $birth_date ?>" required>
        </div>
        
        <div class="form-group">
            <label>Пол:</label>
            <label><input type="radio" name="gender" value="male" <?= $application['gender'] == 'male' ? 'checked' : '' ?> required> Мужской</label>
            <label><input type="radio" name="gender" value="female" <?= $application['gender'] == 'female' ? 'checked' : '' ?>> Женский</label>
        </div>
        
        <div class="form-group">
            <label for="languages">Языки программирования:</label>
            <select id="languages" name="languages[]" multiple size="5">
                <?php foreach ($allowed_lang as $id => $name): ?>
                    <option value="<?= $id ?>" <?= in_array($id, explode(',', $application['languages'])) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="biography">Биография:</label>
            <textarea id="biography" name="biography" rows="4"><?= htmlspecialchars($application['biography']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="agreement" <?= $application['agreement'] ? 'checked' : '' ?>>
                Согласие на обработку данных
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-save">Сохранить</button>
            <a href="admin.php" class="btn btn-cancel">Отмена</a>
        </div>
    </form>
</body>
</html>
