<?php
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

header('Content-Type: text/html; charset=UTF-8');
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// CSRF-защита
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Подключение к БД
require_once __DIR__ . '/db.php';
$db = connectDB();

// Получение списка языков
function getLangs($db) {
function getLangs($db) {
    try {
        $allowed_lang = [];
        $stmt = $db->prepare("SELECT id, name FROM programming_languages");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($data as $lang) {
            if (is_numeric($lang['id'])) { 
                $allowed_lang[(int)$lang['id']] = htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8');
            } 
        } 
        return $allowed_lang;
    } catch(PDOException $e) {
        error_log("Database error in getLangs: " . $e->getMessage());
        die('Произошла ошибка при загрузке данных.');
    }  
}  

$allowed_lang = getLangs($db);

// Обработка GET запроса
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = [];
    $errors = [];
    $values = [];
    $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'languages', 'agreement'];
    
    // Защищенная обработка куки
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        if (empty($_COOKIE[$field . '_value'])) {
            $values[$field] = '';
        } else {
            $values[$field] = htmlspecialchars($_COOKIE[$field . '_value'], ENT_QUOTES, 'UTF-8');
        }
    }

    // Очистка куки ошибок
    foreach ($fields as $field) {
        setcookie($field . '_error', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    // Защищенные сообщения об ошибках
    if ($errors['full_name']) {
        $error_code = $_COOKIE['full_name_error'] ?? '';
        $messages['full_name'] = match($error_code) {
            '1' => 'Имя не указано.',
            '2' => 'Имя не должно превышать 128 символов.',
            '3' => 'Имя должно содержать только буквы и пробелы.',
            default => 'Некорректное имя.'
        };
    }
    
    if ($errors['phone']) {
        $error_code = $_COOKIE['phone_error'] ?? '';
        $messages['phone'] = match($error_code) {
            '1' => 'Телефон не указан.',
            '2' => 'Телефон должен быть в формате +7XXXXXXXXXX.',
            default => 'Некорректный телефон.'
        };
    }
    
    if ($errors['email']) {
        $error_code = $_COOKIE['email_error'] ?? '';
        $messages['email'] = match($error_code) {
            '1' => 'Email не указан.',
            '2' => 'Email должен быть в формате example@mail.com.',
            default => 'Некорректный email.'
        };
    }
    
    if ($errors['birth_day'] || $errors['birth_month'] || $errors['birth_year']) {
        $messages['birth_date'] = 'Некорректная дата рождения.';
    }
    
    if ($errors['gender']) {
        $error_code = $_COOKIE['gender_error'] ?? '';
        $messages['gender'] = match($error_code) {
            '1' => 'Пол не указан.',
            '2' => 'Недопустимое значение пола.',
            default => 'Некорректный пол.'
        };
    }
    
    if ($errors['biography']) {
        $error_code = $_COOKIE['biography_error'] ?? '';
        $messages['biography'] = match($error_code) {
            '1' => 'Биография не указана.',
            '2' => 'Биография не должна превышать 512 символов.',
            '3' => 'Биография содержит недопустимые символы.',
            default => 'Некорректная биография.'
        };
    }
    
    if ($errors['languages']) {
        $error_code = $_COOKIE['languages_error'] ?? '';
        $messages['languages'] = match($error_code) {
            '1' => 'Не выбран язык программирования.',
            '2' => 'Выбран недопустимый язык программирования.',
            default => 'Некорректные языки программирования.'
        };
    }
    
    if ($errors['agreement']) {
        $messages['agreement'] = 'Необходимо согласие с контрактом.';
    }

    // Защищенная загрузка данных пользователя
    if (!empty($_SESSION['login'])) {
        try {
            $stmt = $db->prepare("SELECT a.* FROM applications a JOIN user_applications ua ON a.id = ua.application_id JOIN users u ON ua.user_id = u.id WHERE u.login = ?");
            $stmt->execute([$_SESSION['login']]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($application) {
                $values['full_name'] = htmlspecialchars($application['full_name'] ?? '', ENT_QUOTES, 'UTF-8');
                $values['phone'] = htmlspecialchars($application['phone'] ?? '', ENT_QUOTES, 'UTF-8');
                $values['email'] = htmlspecialchars($application['email'] ?? '', ENT_QUOTES, 'UTF-8');
                
                $birth_date = strtotime($application['birth_date'] ?? '');
                $values['birth_day'] = $birth_date ? date('d', $birth_date) : '';
                $values['birth_month'] = $birth_date ? date('m', $birth_date) : '';
                $values['birth_year'] = $birth_date ? date('Y', $birth_date) : '';
                
                $values['gender'] = htmlspecialchars($application['gender'] ?? '', ENT_QUOTES, 'UTF-8');
                $values['biography'] = htmlspecialchars($application['biography'] ?? '', ENT_QUOTES, 'UTF-8');
                $values['agreement'] = (int)($application['agreement'] ?? 0);

                $stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
                $stmt->execute([$application['id']]);
                $selected_langs = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $values['languages'] = implode(',', array_map('intval', $selected_langs));
            }
        } catch (PDOException $e) {
            error_log("Error loading user data: " . $e->getMessage());
            die('Ошибка загрузки данных. Пожалуйста, попробуйте позже.');
        }
    }

    include('form.php');
    exit();
}

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Проверка CSRF токена
    if (!isset($_POST['csrf_token'])) {
        die('CSRF токен отсутствует');
    }
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Недействительный CSRF токен');
    }
   }
    
    $errors = FALSE;
    $fields = [
        'full_name' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'phone' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'email' => FILTER_SANITIZE_EMAIL,
        'birth_day' => FILTER_SANITIZE_NUMBER_INT,
        'birth_month' => FILTER_SANITIZE_NUMBER_INT,
        'birth_year' => FILTER_SANITIZE_NUMBER_INT,
        'gender' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'biography' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'agreement' => FILTER_SANITIZE_NUMBER_INT
    ];

    // Фильтрация входных данных
    $filtered = filter_input_array(INPUT_POST, $fields);
    if ($filtered === null) {
        die('Некорректные входные данные');
    }

    $fio = trim($filtered['full_name'] ?? '');
    $num = trim($filtered['phone'] ?? '');
    $email = trim($filtered['email'] ?? '');
    $day = (int)($filtered['birth_day'] ?? 0);
    $month = (int)($filtered['birth_month'] ?? 0);
    $year = (int)($filtered['birth_year'] ?? 0);
    $biography = trim($filtered['biography'] ?? '');
    $gen = $filtered['gender'] ?? '';
    $agreement = (int)($filtered['agreement'] ?? 0);

    // Валидация языков программирования
    $languages = [];
    if (isset($_POST['languages']) && is_array($_POST['languages'])) {
        foreach ($_POST['languages'] as $lang_id) {
            $lang_id = (int)$lang_id;
            if (array_key_exists($lang_id, $allowed_lang)) {
                $languages[] = $lang_id;
            }
        }
    }

    // Валидация ФИО
    if (empty($fio)) {
        setcookie('full_name_error', '1', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    } elseif (strlen($fio) > 128) {
        setcookie('full_name_error', '2', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    } elseif (!preg_match('/^[\p{L}\s]+$/u', $fio)) {
        setcookie('full_name_error', '3', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    }
    setcookie('full_name_value', $fio, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    // Валидация телефона
    if (empty($num)) {
        setcookie('phone_error', '1', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    } elseif (!preg_match('/^\+7\d{10}$/', $num)) {
        setcookie('phone_error', '2', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    }
    setcookie('phone_value', $num, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    // Валидация email
    if (empty($email)) {
        setcookie('email_error', '1', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '2', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    }
    setcookie('email_value', $email, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    // Валидация пола
    if (empty($gen)) {
        setcookie('gender_error', '1', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    } elseif (!in_array($gen, ["male", "female"])) {
        setcookie('gender_error', '2', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    }
    setcookie('gender_value', $gen, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    // Валидация биографии
    if (empty($biography)) {
        setcookie('biography_error', '1', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    } elseif (strlen($biography) > 512) {
        setcookie('biography_error', '2', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    } elseif (preg_match('/[<>{}\[\]]|<script|<\?php/i', $biography)) {
        setcookie('biography_error', '3', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    }
    setcookie('biography_value', $biography, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    // Валидация языков программирования
    if (empty($languages)) {
        setcookie('languages_error', '1', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    }
    setcookie('languages_value', implode(',', $languages), [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    // Валидация даты рождения
    if (!checkdate($month, $day, $year)) {
        setcookie('birth_day_error', '1', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        setcookie('birth_month_error', '1', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        setcookie('birth_year_error', '1', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    }
    setcookie('birth_day_value', $day, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    setcookie('birth_month_value', $month, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    setcookie('birth_year_value', $year, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    // Валидация согласия
    if (!$agreement) {
        setcookie('agreement_error', '1', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $errors = TRUE;
    }
    setcookie('agreement_value', $agreement, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    try {
        $birth_date = sprintf("%04d-%02d-%02d", $year, $month, $day);

        if (!empty($_SESSION['login'])) {
            // Обновление существующей заявки
            $stmt = $db->prepare("UPDATE applications SET full_name = ?, phone = ?, email = ?, birth_date = ?, gender = ?, biography = ?, agreement = ? WHERE id = (SELECT application_id FROM user_applications WHERE user_id = (SELECT id FROM users WHERE login = ?))");
            $stmt->execute([$fio, $num, $email, $birth_date, $gen, $biography, $agreement, $_SESSION['login']]);

            $stmt = $db->prepare("SELECT application_id FROM user_applications WHERE user_id = (SELECT id FROM users WHERE login = ?)");
            $stmt->execute([$_SESSION['login']]);
            $application_id = $stmt->fetchColumn();

            $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
            $stmt->execute([$application_id]);

            $stmt_insert = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $language_id) {
                $stmt_insert->execute([$application_id, $language_id]);
            }
        } else {
            // Создание новой заявки
            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, agreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fio, $num, $email, $birth_date, $gen, $biography, $agreement]);
            $application_id = $db->lastInsertId();

            $stmt_insert = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $language_id) {
                $stmt_insert->execute([$application_id, $language_id]);
            }

            $login = uniqid('user_');
            $pass = bin2hex(random_bytes(8));
            $pass_hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
            $stmt->execute([$login, $pass_hash]);
            $user_id = $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $application_id]);

            $db->commit();

            $_SESSION['generated_login'] = $login;
            $_SESSION['generated_password'] = $pass;
        }

        setcookie('save', '1', [
            'expires' => time() + 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Database save error: " . $e->getMessage());
        die('Ошибка сохранения данных. Пожалуйста, попробуйте позже.');
    } catch (Exception $e) {
        error_log("General error: " . $e->getMessage());
        die('Произошла ошибка. Пожалуйста, попробуйте позже.');
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Форма</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="index-page">
</body>
</html>
