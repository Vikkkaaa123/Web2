<?php
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

function getLangs($db) {
    $allowed_lang = [];
    $stmt = $db->query("SELECT id, name FROM programming_languages");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $allowed_lang[$row['id']] = $row['name'];
    }
    return $allowed_lang;
}

$allowed_lang = getLangs($db);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = [];
    
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600);
        $messages[] = '<div class="success">Спасибо, результаты сохранены.</div>';
        
        if (!empty($_COOKIE['login']) && !empty($_COOKIE['password'])) {
            $messages[] = sprintf(
                '<div class="info">Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong>.</div>',
                htmlspecialchars($_COOKIE['login']),
                htmlspecialchars($_COOKIE['password'])
            );
        }
    }

    $errors = [];
    $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'languages', 'agreement'];
    
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        setcookie($field . '_error', '', time() - 3600);
    }

    $values = [];
    foreach ($fields as $field) {
        $values[$field] = $_COOKIE[$field . '_value'] ?? '';
    }

    // Обработка языков программирования
    $values['languages'] = [];
    if (!empty($_COOKIE['languages_value'])) {
        $values['languages'] = explode(',', $_COOKIE['languages_value']);
    }

    if (!empty($_SESSION['login'])) {
        try {
            $stmt = $db->prepare("SELECT a.* FROM applications a JOIN user_applications ua ON a.id = ua.application_id JOIN users u ON ua.user_id = u.id WHERE u.login = ?");
            $stmt->execute([$_SESSION['login']]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($application) {
                $values = [
                    'full_name' => $application['full_name'],
                    'phone' => $application['phone'],
                    'email' => $application['email'],
                    'birth_day' => date('d', strtotime($application['birth_date'])),
                    'birth_month' => date('m', strtotime($application['birth_date'])),
                    'birth_year' => date('Y', strtotime($application['birth_date'])),
                    'gender' => $application['gender'],
                    'biography' => $application['biography'],
                    'agreement' => $application['agreement'],
                    'languages' => []
                ];
                
                $stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
                $stmt->execute([$application['id']]);
                $values['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            }
            
            $messages[] = '<div class="info">Вы вошли как ' . htmlspecialchars($_SESSION['login']) . ' (<a href="logout.php">выйти</a>)</div>';
        } catch (PDOException $e) {
            die('Ошибка: ' . $e->getMessage());
        }
    }

    include('form.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = FALSE;

    $fields = [
        'full_name' => $_POST['full_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'birth_day' => $_POST['birth_day'] ?? '',
        'birth_month' => $_POST['birth_month'] ?? '',
        'birth_year' => $_POST['birth_year'] ?? '',
        'biography' => $_POST['biography'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'languages' => $_POST['languages'] ?? [],
        'agreement' => isset($_POST['agreement']) ? 1 : 0
    ];

    // Валидация
    if (empty($fields['full_name'])) {
        setcookie('full_name_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('full_name_value', $fields['full_name'], time() + 365 * 24 * 60 * 60);

    // Аналогичные проверки для других полей...

    setcookie('languages_value', implode(',', $fields['languages']), time() + 365 * 24 * 60 * 60);

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    // Очистка куков ошибок
    foreach ($fields as $field => $value) {
        setcookie($field . '_error', '', time() - 3600);
    }

    try {
        $db->beginTransaction();
        
        if (!empty($_SESSION['login'])) {
            // Обновление существующей заявки
            $stmt = $db->prepare("UPDATE applications SET full_name=?, phone=?, email=?, birth_date=?, gender=?, biography=?, agreement=? WHERE id=(SELECT application_id FROM user_applications WHERE user_id=(SELECT id FROM users WHERE login=?))");
            $stmt->execute([
                $fields['full_name'], $fields['phone'], $fields['email'], 
                $fields['birth_year'].'-'.$fields['birth_month'].'-'.$fields['birth_day'], 
                $fields['gender'], $fields['biography'], $fields['agreement'],
                $_SESSION['login']
            ]);
            
            // Удаление старых языков
            $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id=(SELECT application_id FROM user_applications WHERE user_id=(SELECT id FROM users WHERE login=?))");
            $stmt->execute([$_SESSION['login']]);
        } else {
            // Создание новой заявки
            $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, agreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $fields['full_name'], $fields['phone'], $fields['email'],
                $fields['birth_year'].'-'.$fields['birth_month'].'-'.$fields['birth_day'],
                $fields['gender'], $fields['biography'], $fields['agreement']
            ]);
            $application_id = $db->lastInsertId();

            // Генерация логина и пароля
            $login = substr(md5(time()), 0, 16);
            $pass = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

            // Создание пользователя
            $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
            $stmt->execute([$login, $pass_hash]);
            $user_id = $db->lastInsertId();

            // Связывание пользователя и заявки
            $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $application_id]);

            // Сохранение данных для входа
            setcookie('login', $login, time() + 24 * 60 * 60);
            setcookie('password', $pass, time() + 24 * 60 * 60);
            
            // Автоматический вход
            $_SESSION['login'] = $login;
            $_SESSION['uid'] = $user_id;
        }

        // Добавление языков программирования
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($fields['languages'] as $lang_id) {
            $stmt->execute([$application_id ?? $_SESSION['uid'], $lang_id]);
        }
        
        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        die('Ошибка: ' . $e->getMessage());
    }

    setcookie('save', '1', time() + 24 * 60 * 60);
    header('Location: index.php');
    exit();
}
