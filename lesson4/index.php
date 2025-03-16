<?php
header('Content-Type: text/html; charset=UTF-8');

// Подключение к базе данных
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

// Функция получения языков программирования
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

// Если метод запроса GET, показываем форму
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();
    $errors = array();
    $values = array();

    // Проверяем Cookies на наличие ошибок и значений
    $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'languages', 'agreement'];
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : $_COOKIE[$field . '_value'];
    }

    // Выводим сообщения об ошибках
    if ($errors['full_name']) {
        $messages['full_name'] = '<div class="error">Некорректное имя. Допустимы только буквы и пробелы.</div>';
    }
    if ($errors['phone']) {
        $messages['phone'] = '<div class="error">Некорректный номер телефона. Формат: +7XXXXXXXXXX.</div>';
    }
    if ($errors['email']) {
        $messages['email'] = '<div class="error">Некорректный email.</div>';
    }
    if ($errors['birth_day'] || $errors['birth_month'] || $errors['birth_year']) {
        $messages['birth_date'] = '<div class="error">Некорректная дата рождения.</div>';
    }
    if ($errors['gender']) {
        $messages['gender'] = '<div class="error">Некорректный пол.</div>';
    }
    if ($errors['biography']) {
        $messages['biography'] = '<div class="error">Некорректная биография.</div>';
    }
    if ($errors['languages']) {
        $messages['languages'] = '<div class="error">Не выбран язык программирования.</div>';
    }
    if ($errors['agreement']) {
        $messages['agreement'] = '<div class="error">Необходимо согласие.</div>';
    }

    include('form.php');
    exit();
}

// Проверяем метод POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = FALSE;

    // Получение данных из формы
    $fio = trim($_POST['full_name'] ?? '');
    $num = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $day = trim($_POST['birth_day'] ?? '');
    $month = trim($_POST['birth_month'] ?? ''); 
    $year = trim($_POST['birth_year'] ?? '');
    $biography = trim($_POST['biography'] ?? '');
    $gen = $_POST['gender'] ?? '';
    $languages = is_array($_POST['languages']) ? $_POST['languages'] : [];
    $agreement = isset($_POST['agreement']) && $_POST['agreement'] === 'on' ? 1 : 0;

    // Валидация данных
    if (empty($fio) || strlen($fio) > 128 || !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]+$/u', $fio)) {
        setcookie('full_name_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('full_name_value', $fio, time() + 365 * 24 * 60 * 60);

    if (empty($num) || !preg_match('/^\+7\d{10}$/', $num)) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('phone_value', $num, time() + 365 * 24 * 60 * 60);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('email_value', $email, time() + 365 * 24 * 60 * 60);

    if (empty($gen) || !in_array($gen, ["male", "female"])) {
        setcookie('gender_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('gender_value', $gen, time() + 365 * 24 * 60 * 60);

    if (empty($biography) || strlen($biography) > 512) {
        setcookie('biography_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('biography_value', $biography, time() + 365 * 24 * 60 * 60);

    if (!checkdate($month, $day, $year)) {
        setcookie('birth_day_error', '1', time() + 24 * 60 * 60);
        setcookie('birth_month_error', '1', time() + 24 * 60 * 60);
        setcookie('birth_year_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('birth_day_value', $day, time() + 365 * 24 * 60 * 60);
    setcookie('birth_month_value', $month, time() + 365 * 24 * 60 * 60);
    setcookie('birth_year_value', $year, time() + 365 * 24 * 60 * 60);

    if (empty($languages)) {
        setcookie('languages_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('languages_value', implode(',', $languages), time() + 365 * 24 * 60 * 60);

    if (!$agreement) {
        setcookie('agreement_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('agreement_value', $agreement, time() + 365 * 24 * 60 * 60);

    if ($errors) {
        // Перенаправляем на форму с сохранением данных
        header('Location: index.php');
        exit();
    } else {
        // Удаляем Cookies с признаками ошибок
        $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'languages', 'agreement'];
        foreach ($fields as $field) {
            setcookie($field . '_error', '', 100000);
        }

        // Сохранение в БД
        try {
            $birth_date = sprintf("%04d-%02d-%02d", $year, $month, $day);
            $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, agreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fio, $num, $email, $birth_date, $gen, $biography, $agreement]);

            $application_id = $db->lastInsertId();
            $stmt_insert = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $language_id) {
                $stmt_insert->execute([$application_id, $language_id]);
            }

            // Сохраняем значения в Cookies на год
            foreach ($_POST as $key => $value) {
                if (is_array($value)) {
                    setcookie($key, implode(',', $value), time() + 365 * 24 * 60 * 60);
                } else {
                    setcookie($key, $value, time() + 365 * 24 * 60 * 60);
                }
            }

            setcookie('save', '1', time() + 24 * 60 * 60);
            header('Location: index.php?save=1');
            exit();
        } catch (PDOException $e) {
            die('Ошибка сохранения: ' . $e->getMessage());
        }
    }
}
?>
