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
    try {
        $allowed_lang = [];
        $data = $db->query("SELECT id, name FROM programming_languages")->fetchAll();
        foreach ($data as $lang) {
            $allowed_lang[$lang['id']] = $lang['name'];
        }
        return $allowed_lang;
    } catch (PDOException $e) {
        die('Ошибка: ' . $e->getMessage());
    }
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
                strip_tags($_COOKIE['login']),
                strip_tags($_COOKIE['password'])
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
        $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : strip_tags($_COOKIE[$field . '_value']);
    }

    // Обработка языков программирования
    $values['languages'] = [];
    if (!empty($_COOKIE['languages_value'])) {
        if (is_array($_COOKIE['languages_value'])) {
            $values['languages'] = $_COOKIE['languages_value'];
        } else {
            $values['languages'] = explode(',', $_COOKIE['languages_value']);
        }
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
            
            $messages[] = '<div class="info">Вы вошли как ' . $_SESSION['login'] . ' (<a href="logout.php">выйти</a>)</div>';
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
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'birth_day' => trim($_POST['birth_day'] ?? ''),
        'birth_month' => trim($_POST['birth_month'] ?? ''),
        'birth_year' => trim($_POST['birth_year'] ?? ''),
        'biography' => trim($_POST['biography'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'languages' => is_array($_POST['languages'] ?? []) ? $_POST['languages'] : [],
        'agreement' => isset($_POST['agreement']) && $_POST['agreement'] === 'on' ? 1 : 0
    ];

    // Валидация
    if (empty($fields['full_name'])) {
        setcookie('full_name_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (strlen($fields['full_name']) > 128) {
        setcookie('full_name_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]+$/u', $fields['full_name'])) {
        setcookie('full_name_error', '3', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('full_name_value', $fields['full_name'], time() + 365 * 24 * 60 * 60);

    // Аналогичные проверки для других полей...

    setcookie('languages_value', implode(',', $fields['languages']), time() + 365 * 24 * 60 * 60);

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    foreach ($fields as $field => $value) {
        setcookie($field . '_error', '', time() - 3600);
    }

    try {
        $db->beginTransaction();
        
        if (!empty($_SESSION['login'])) {
            $stmt = $db->prepare("UPDATE applications SET full_name=?, phone=?, email=?, birth_date=?, gender=?, biography=?, agreement=? WHERE id=(SELECT application_id FROM user_applications WHERE user_id=(SELECT id FROM users WHERE login=?))");
            $stmt->execute([
                $fields['full_name'], $fields['phone'], $fields['email'], 
                "{$fields['birth_year']}-{$fields['birth_month']}-{$fields['birth_day']}", 
                $fields['gender'], $fields['biography'], $fields['agreement'],
                $_SESSION['login']
            ]);
            
            $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id=(SELECT application_id FROM user_applications WHERE user_id=(SELECT id FROM users WHERE login=?))");
            $stmt->execute([$_SESSION['login']]);
        } else {
            $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, agreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $fields['full_name'], $fields['phone'], $fields['email'],
                "{$fields['birth_year']}-{$fields['birth_month']}-{$fields['birth_day']}",
                $fields['gender'], $fields['biography'], $fields['agreement']
            ]);
            $application_id = $db->lastInsertId();

            $login = substr(md5(time()), 0, 16);
            $pass = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
            $stmt->execute([$login, $pass_hash]);
            $user_id = $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $application_id]);

            setcookie('login', $login, time() + 24 * 60 * 60);
            setcookie('password', $pass, time() + 24 * 60 * 60);
            
            $_SESSION['login'] = $login;
            $_SESSION['uid'] = $user_id;
        }

        // Добавляем языки программирования
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
