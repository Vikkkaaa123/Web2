<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

header('Content-Type: text/html; charset=UTF-8');
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/db.php';
$db = connectDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

function getLangs($db) {
    try {
        $allowed_lang = [];
        $stmt = $db->query("SELECT id, name FROM programming_languages");
        while ($lang = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $allowed_lang[$lang['id']] = htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8');
        }
        return $allowed_lang;
    } catch(PDOException $e) {
        error_log("DB Error in getLangs: ".$e->getMessage());
        die("Ошибка загрузки языков");
    }
}

$allowed_lang = getLangs($db);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = [];
    $errors = [];
    $values = [];
    $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'languages', 'agreement'];

    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field.'_error']);
        if ($field == 'languages') {
            $values[$field] = empty($_COOKIE[$field.'_value']) ? [] : explode(',', $_COOKIE[$field.'_value']);
        } else {
            $values[$field] = empty($_COOKIE[$field.'_value']) ? '' : 
                htmlspecialchars($_COOKIE[$field.'_value'], ENT_QUOTES, 'UTF-8');
        }
        setcookie($field.'_error', '', time() - 3600, '/');
    }

    if ($errors['full_name']) {
        $messages['full_name'] = match($_COOKIE['full_name_error']) {
            '1' => 'Имя не указано.',
            '2' => 'Имя не должно превышать 128 символов.',
            '3' => 'Имя должно содержать только буквы и пробелы.',
            default => 'Некорректное имя.'
        };
    }
    
    if ($errors['phone']) {
        $messages['phone'] = match($_COOKIE['phone_error']) {
            '1' => 'Телефон не указан.',
            '2' => 'Телефон должен быть в формате +7XXXXXXXXXX.',
            default => 'Некорректный телефон.'
        };
    }
    
    if ($errors['email']) {
        $messages['email'] = match($_COOKIE['email_error']) {
            '1' => 'Email не указан.',
            '2' => 'Email должен быть в формате example@domain.com.',
            default => 'Некорректный email.'
        };
    }
    
    if ($errors['birth_day'] || $errors['birth_month'] || $errors['birth_year']) {
        $messages['birth_date'] = 'Некорректная дата рождения.';
    }
    
    if ($errors['gender']) {
        $messages['gender'] = match($_COOKIE['gender_error']) {
            '1' => 'Пол не указан.',
            '2' => 'Недопустимое значение пола.',
            default => 'Некорректный пол.'
        };
    }
    
    if ($errors['biography']) {
        $messages['biography'] = match($_COOKIE['biography_error']) {
            '1' => 'Биография не указана.',
            '2' => 'Биография не должна превышать 512 символов.',
            '3' => 'Биография содержит недопустимые символы.',
            default => 'Некорректная биография.'
        };
    }
    
    if ($errors['languages']) {
        $messages['languages'] = match($_COOKIE['languages_error']) {
            '1' => 'Не выбран язык программирования.',
            '2' => 'Выбран недопустимый язык программирования.',
            default => 'Некорректные языки программирования.'
        };
    }
    
    if ($errors['agreement']) {
        $messages['agreement'] = 'Необходимо согласие с контрактом.';
    }

   if (!empty($_SESSION['login'])) {
    try {
            $stmt = $db->prepare("SELECT a.* FROM applications a 
                                JOIN user_applications ua ON a.id = ua.application_id 
                                JOIN users u ON ua.user_id = u.id 
                                WHERE u.login = ?");
            $stmt->execute([$_SESSION['login']]);
            
            if ($application = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach (['full_name', 'phone', 'email', 'gender', 'biography'] as $field) {
                    $values[$field] = htmlspecialchars($application[$field], ENT_QUOTES, 'UTF-8');
                }
                
                $values['birth_day'] = date('d', strtotime($application['birth_date']));
                $values['birth_month'] = date('m', strtotime($application['birth_date']));
                $values['birth_year'] = date('Y', strtotime($application['birth_date']));
                $values['agreement'] = $application['agreement'];

                $stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
                $stmt->execute([$application['id']]);
                $lang_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $values['languages'] = $lang_ids; 
        setcookie('languages_value', implode(',', $lang_ids), time() + 31536000); 
    } catch (PDOException $e) {
        error_log("DB Error loading user data: ".$e->getMessage());
        die("Ошибка загрузки данных");
    }
}

    $fields['languages'] = isset($_POST['languages']) && is_array($_POST['languages']) ? $_POST['languages'] : [];
    
    include __DIR__.'/form.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die("Неверный CSRF-токен");
    }

    $errors = false;
    $fields = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'birth_day' => trim($_POST['birth_day'] ?? ''),
        'birth_month' => trim($_POST['birth_month'] ?? ''),
        'birth_year' => trim($_POST['birth_year'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'biography' => trim($_POST['biography'] ?? ''),
        'languages' => isset($_POST['languages']) && is_array($_POST['languages']) ? $_POST['languages'] : [],
        'agreement' => isset($_POST['agreement']) && $_POST['agreement'] === 'on' ? 1 : 0
    ];

    if (empty($fields['full_name'])) {
        setcookie('full_name_error', '1', time() + 86400, '/');
        $errors = true;
    } elseif (strlen($fields['full_name']) > 128) {
        setcookie('full_name_error', '2', time() + 86400, '/');
        $errors = true;
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]+$/u', $fields['full_name'])) {
        setcookie('full_name_error', '3', time() + 86400, '/');
        $errors = true;
    }
    setcookie('full_name_value', $fields['full_name'], time() + 31536000, '/');

    if (empty($fields['phone'])) {
        setcookie('phone_error', '1', time() + 86400, '/');
        $errors = true;
    } elseif (!preg_match('/^\+7\d{10}$/', $fields['phone'])) {
        setcookie('phone_error', '2', time() + 86400, '/');
        $errors = true;
    }
    setcookie('phone_value', $fields['phone'], time() + 31536000, '/');

    if (empty($fields['email'])) {
        setcookie('email_error', '1', time() + 86400, '/');
        $errors = true;
    } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '2', time() + 86400, '/');
        $errors = true;
    }
    setcookie('email_value', $fields['email'], time() + 31536000, '/');

    if (!checkdate($fields['birth_month'], $fields['birth_day'], $fields['birth_year'])) {
        setcookie('birth_day_error', '1', time() + 86400, '/');
        setcookie('birth_month_error', '1', time() + 86400, '/');
        setcookie('birth_year_error', '1', time() + 86400, '/');
        $errors = true;
    }
    setcookie('birth_day_value', $fields['birth_day'], time() + 31536000, '/');
    setcookie('birth_month_value', $fields['birth_month'], time() + 31536000, '/');
    setcookie('birth_year_value', $fields['birth_year'], time() + 31536000, '/');

    if (empty($fields['gender'])) {
        setcookie('gender_error', '1', time() + 86400, '/');
        $errors = true;
    } elseif (!in_array($fields['gender'], ['male', 'female'])) {
        setcookie('gender_error', '2', time() + 86400, '/');
        $errors = true;
    }
    setcookie('gender_value', $fields['gender'], time() + 31536000, '/');

    if (empty($fields['biography'])) {
        setcookie('biography_error', '1', time() + 86400, '/');
        $errors = true;
    } elseif (strlen($fields['biography']) > 512) {
        setcookie('biography_error', '2', time() + 86400, '/');
        $errors = true;
    } elseif (preg_match('/[<>{}\[\]]|<script|<\?php/i', $fields['biography'])) {
        setcookie('biography_error', '3', time() + 86400, '/');
        $errors = true;
    }
    setcookie('biography_value', $fields['biography'], time() + 31536000, '/');

    if (empty($fields['languages']) || !is_array($fields['languages'])) {
    setcookie('languages_error', '1', time() + 86400, '/');
    $errors = true;
} else {
    foreach ($fields['languages'] as $lang_id) {
        if (!array_key_exists($lang_id, $allowed_lang)) {
            setcookie('languages_error', '2', time() + 86400, '/');
            $errors = true;
            break;
        }
    }
}
setcookie('languages_value', implode(',', $fields['languages']), time() + 31536000, '/');

    if (!$fields['agreement']) {
        setcookie('agreement_error', '1', time() + 86400, '/');
        $errors = true;
    }
    setcookie('agreement_value', $fields['agreement'], time() + 31536000, '/');

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    try {
        $birth_date = sprintf("%04d-%02d-%02d", 
            $fields['birth_year'], 
            $fields['birth_month'], 
            $fields['birth_day']
        );

        if (!empty($_SESSION['login'])) {
            $db->beginTransaction();
            
            $stmt = $db->prepare("UPDATE applications SET 
                full_name = ?, phone = ?, email = ?, birth_date = ?, 
                gender = ?, biography = ?, agreement = ? 
                WHERE id = (SELECT application_id FROM user_applications 
                           WHERE user_id = (SELECT id FROM users WHERE login = ?))");
            $stmt->execute([
                $fields['full_name'], $fields['phone'], $fields['email'],
                $birth_date, $fields['gender'], $fields['biography'],
                $fields['agreement'], $_SESSION['login']
            ]);

            $stmt = $db->prepare("SELECT application_id FROM user_applications 
                                 WHERE user_id = (SELECT id FROM users WHERE login = ?)");
            $stmt->execute([$_SESSION['login']]);
            $app_id = $stmt->fetchColumn();

            $db->prepare("DELETE FROM application_languages WHERE application_id = ?")
               ->execute([$app_id]);

            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($fields['languages'] as $lang_id) {
                $stmt->execute([$app_id, $lang_id]);
            }
            
            $db->commit();
        } else {
            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO applications 
                (full_name, phone, email, birth_date, gender, biography, agreement) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $fields['full_name'], $fields['phone'], $fields['email'],
                $birth_date, $fields['gender'], $fields['biography'],
                $fields['agreement']
            ]);
            $app_id = $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($fields['languages'] as $lang_id) {
                $stmt->execute([$app_id, $lang_id]);
            }

            $login = uniqid('user_');
            $pass = bin2hex(random_bytes(8));
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

            $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)")
               ->execute([$login, $pass_hash]);
            $user_id = $db->lastInsertId();

            $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)")
               ->execute([$user_id, $app_id]);

            $_SESSION['generated_login'] = $login;
            $_SESSION['generated_password'] = $pass;
            
            $db->commit();
        }

        setcookie('save', '1', time() + 86400, '/');
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("DB Save Error: ".$e->getMessage());
        die("Ошибка сохранения данных");
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задание 7</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="index-page">
</body>
</html>
