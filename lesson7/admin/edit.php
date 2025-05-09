<?php
require_once 'auth.php';
checkAdminAuth();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// CSRF защита
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

    // Получаем все языки
    $stmt = $db->prepare("SELECT * FROM programming_languages");
    $stmt->execute();
    $allLangs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем текущую заявку
    $stmt = $db->prepare("SELECT * FROM applications WHERE id = ? LIMIT 1");
    $stmt->execute([$appId]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        die('Заявка не найдена');
    }

    // Получаем выбранные языки
    $stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
    $stmt->execute([$appId]);
    $selectedLangs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Обработка формы
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Проверка CSRF токена
        if (!isset($_POST['csrf_token']) {
            die('CSRF токен отсутствует');
        }
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Недействительный CSRF токен');
        }

        // Фильтрация входных данных
        $fields = [
            'full_name' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'email' => FILTER_SANITIZE_EMAIL,
            'phone' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'gender' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'biography' => FILTER_SANITIZE_FULL_SPECIAL_CHARS
        ];
        
        $filtered = filter_input_array(INPUT_POST, $fields);
        if ($filtered === null) {
            die('Некорректные входные данные');
        }

        // Валидация языков
        $languages = [];
        if (isset($_POST['languages']) && is_array($_POST['languages'])) {
            foreach ($_POST['languages'] as $lang_id) {
                $lang_id = (int)$lang_id;
                foreach ($allLangs as $lang) {
                    if ($lang['id'] == $lang_id) {
                        $languages[] = $lang_id;
                        break;
                    }
                }
            }
        }

        try {
            $db->beginTransaction();

            // Обновление основной информации
            $stmt = $db->prepare("UPDATE applications SET full_name = ?, email = ?, phone = ?, gender = ?, biography = ? WHERE id = ?");
            $stmt->execute([
                $filtered['full_name'],
                $filtered['email'],
                $filtered['phone'],
                $filtered['gender'],
                $filtered['biography'],
                $appId
            ]);

            // Обновление языков
            $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
            $stmt->execute([$appId]);

            if (!empty($languages)) {
                $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
                foreach ($languages as $lang_id) {
                    $stmt->execute([$appId, $lang_id]);
                }
            }

            $db->commit();

            header('Location: admin.php');
            exit();

        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Edit application error: " . $e->getMessage());
            die('Ошибка при сохранении данных. Пожалуйста, попробуйте позже.');
        }
    }

} catch (PDOException $e) {
    error_log("Database error in edit.php: " . $e->getMessage());
    die('Ошибка загрузки данных. Пожалуйста, попробуйте позже.');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Редактирование заявки</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-container">
        <h1>Редактирование заявки #<?php echo (int)$appId; ?></h1>
        
        <form method="POST" class="edit-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="form-group">
                <label>ФИО:</label>
                <input type="text" name="full_name" 
                       value="<?php echo htmlspecialchars($app['full_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                       required maxlength="128">
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" 
                       value="<?php echo htmlspecialchars($app['email'], ENT_QUOTES, 'UTF-8'); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label>Телефон:</label>
                <input type="text" name="phone" 
                       value="<?php echo htmlspecialchars($app['phone'], ENT_QUOTES, 'UTF-8'); ?>"
                       pattern="\+7\d{10}">
            </div>
            
            <div class="form-group">
                <label>Пол:</label>
                <select name="gender">
                    <option value="male" <?php echo $app['gender'] === 'male' ? 'selected' : ''; ?>>Мужской</option>
                    <option value="female" <?php echo $app['gender'] === 'female' ? 'selected' : ''; ?>>Женский</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Биография:</label>
                <textarea name="biography" maxlength="512"><?php 
                    echo htmlspecialchars($app['biography'], ENT_QUOTES, 'UTF-8'); 
                ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Языки программирования:</label>
                <select name="languages[]" multiple class="multiselect">
                    <?php foreach ($allLangs as $lang): ?>
                    <option value="<?php echo (int)$lang['id']; ?>" 
                        <?php echo in_array($lang['id'], $selectedLangs) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="action-buttons">
                <button type="submit" class="button">Сохранить</button>
                <a href="admin.php" class="button">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html>
