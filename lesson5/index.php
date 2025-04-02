<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Подключение к базе данных (оставляем как было)
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

// Функция получения языков программирования (оставляем как было)
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
    
    // Сообщение об успешном сохранении (из второго варианта)
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600);
        setcookie('login', '', time() - 3600);
        setcookie('password', '', time() - 3600);
        $messages[] = 'Спасибо, результаты сохранены.';
        
        if (!empty($_COOKIE['password'])) {
            $messages[] = sprintf('Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong>
                и паролем <strong>%s</strong> для изменения данных.',
                strip_tags($_COOKIE['login']),
                strip_tags($_COOKIE['password']));
        }
    }

    $errors = array();
    $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'languages', 'agreement'];
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
    }

    // Сообщения об ошибках (из второго варианта с адаптацией)
    if ($errors['full_name']) {
        $code = $_COOKIE['full_name_error'];
        if ($code == '1') $messages[] = '<div class="error">Имя не указано.</div>';
        elseif ($code == '2') $messages[] = '<div class="error">Имя не должно превышать 128 символов.</div>';
        else $messages[] = '<div class="error">Имя должно содержать только буквы и пробелы.</div>';
    }
    if ($errors['phone']) {
        $code = $_COOKIE['phone_error'];
        if ($code == '1') $messages[] = '<div class="error">Телефон не указан.</div>';
        else $messages[] = '<div class="error">Телефон должен быть в формате +7XXXXXXXXXX.</div>';
    }
    if ($errors['email']) {
        $code = $_COOKIE['email_error'];
        if ($code == '1') $messages[] = '<div class="error">Email не указан.</div>';
        else $messages[] = '<div class="error">Некорректный email.</div>';
    }
    if ($errors['gender']) {
        $code = $_COOKIE['gender_error'];
        if ($code == '1') $messages[] = '<div class="error">Пол не указан.</div>';
        else $messages[] = '<div class="error">Некорректное значение пола.</div>';
    }
    if ($errors['biography']) {
        $code = $_COOKIE['biography_error'];
        if ($code == '1') $messages[] = '<div class="error">Биография не указана.</div>';
        elseif ($code == '2') $messages[] = '<div class="error">Биография не должна превышать 512 символов.</div>';
        else $messages[] = '<div class="error">Биография содержит недопустимые символы.</div>';
    }
    if ($errors['languages']) {
        $code = $_COOKIE['languages_error'];
        if ($code == '1') $messages[] = '<div class="error">Языки программирования не выбраны.</div>';
        else $messages[] = '<div class="error">Выбран недопустимый язык программирования.</div>';
    }
    if ($errors['birth_day'] || $errors['birth_month'] || $errors['birth_year']) {
        $messages[] = '<div class="error">Дата рождения не указана.</div>';
    }
    if ($errors['agreement']) {
        $messages[] = '<div class="error">Необходимо согласие с контрактом.</div>';
    }

    // Удаляем куки ошибок
    foreach ($fields as $field) {
        setcookie($field . '_error', '', time() - 3600);
    }

    $values = array();
    foreach ($fields as $field) {
        $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : strip_tags($_COOKIE[$field . '_value']);
    }

    // Если пользователь авторизован (оставляем оригинальную логику)
    if (!empty($_SESSION['login'])) {
        $stmt = $db->prepare("SELECT a.* FROM applications a JOIN user_applications ua ON a.id = ua.application_id JOIN users u ON ua.user_id = u.id WHERE u.login = ?");
        $stmt->execute([$_SESSION['login']]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($application) {
            $values['full_name'] = $application['full_name'];
            $values['phone'] = $application['phone'];
            $values['email'] = $application['email'];
            $values['birth_day'] = date('d', strtotime($application['birth_date']));
            $values['birth_month'] = date('m', strtotime($application['birth_date']));
            $values['birth_year'] = date('Y', strtotime($application['birth_date']));
            $values['gender'] = $application['gender'];
            $values['biography'] = $application['biography'];
            $values['agreement'] = $application['agreement'];
            
            // Получаем языки программирования
            $stmt = $db->prepare("SELECT pl.name FROM programming_languages pl JOIN application_languages al ON pl.id = al.language_id WHERE al.application_id = ?");
            $stmt->execute([$application['id']]);
            $langs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            $values['languages'] = implode(",", $langs);
        }
    }

    include('form.php');
    exit();
}

// Если метод запроса POST, обрабатываем данные формы
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

    // Валидация данных (по аналогии со вторым вариантом)
    if (empty($fio)) {
        setcookie('full_name_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (strlen($fio) > 128) {
        setcookie('full_name_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]+$/u', $fio)) {
        setcookie('full_name_error', '3', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('full_name_value', $fio, time() + 365 * 24 * 60 * 60);

    if (empty($num)) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!preg_match('/^\+7\d{10}$/', $num)) {
        setcookie('phone_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('phone_value', $num, time() + 365 * 24 * 60 * 60);

    if (empty($email)) {
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('email_value', $email, time() + 365 * 24 * 60 * 60);

    if (empty($gen)) {
        setcookie('gender_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!in_array($gen, ["male", "female"])) {
        setcookie('gender_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('gender_value', $gen, time() + 365 * 24 * 60 * 60);

    if (empty($biography)) {
        setcookie('biography_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (strlen($biography) > 512) {
        setcookie('biography_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (preg_match('/[<>{}\[\]]|<script|<\?php/i', $biography)) {
        setcookie('biography_error', '3', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('biography_value', $biography, time() + 365 * 24 * 60 * 60);

    if (empty($languages)) {
        setcookie('languages_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } else {
        foreach ($languages as $lang_id) {
            if (!array_key_exists($lang_id, $allowed_lang)) {
                setcookie('languages_error', '2', time() + 24 * 60 * 60);
                $errors = TRUE;
                break;
            }
        }
    }
    setcookie('languages_value', implode(',', $languages), time() + 365 * 24 * 60 * 60);

    if (!checkdate($month, $day, $year)) {
        setcookie('birth_day_error', '1', time() + 24 * 60 * 60);
        setcookie('birth_month_error', '1', time() + 24 * 60 * 60);
        setcookie('birth_year_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('birth_day_value', $day, time() + 365 * 24 * 60 * 60);
    setcookie('birth_month_value', $month, time() + 365 * 24 * 60 * 60);
    setcookie('birth_year_value', $year, time() + 365 * 24 * 60 * 60);

    if (!$agreement) {
        setcookie('agreement_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('agreement_value', $agreement, time() + 365 * 24 * 60 * 60);

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    // Удаляем куки ошибок
    $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'languages', 'agreement'];
    foreach ($fields as $field) {
        setcookie($field . '_error', '', time() - 3600);
    }

    // Если пользователь авторизован (оставляем оригинальную логику)
    if (!empty($_SESSION['login'])) {
        try {
            $db->beginTransaction();
            
            // Обновляем заявку
            $stmt = $db->prepare("UPDATE applications SET full_name = ?, phone = ?, email = ?, birth_date = ?, gender = ?, biography = ?, agreement = ? WHERE id = (SELECT application_id FROM user_applications WHERE user_id = (SELECT id FROM users WHERE login = ?))");
            $stmt->execute([
                $fio, $num, $email, "$year-$month-$day", $gen, $biography, $agreement, $_SESSION['login']
            ]);
            
            // Удаляем старые языки
            $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = (SELECT application_id FROM user_applications WHERE user_id = (SELECT id FROM users WHERE login = ?))");
            $stmt->execute([$_SESSION['login']]);
            
            // Добавляем новые языки
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES ((SELECT application_id FROM user_applications WHERE user_id = (SELECT id FROM users WHERE login = ?)), ?)");
            foreach ($languages as $lang_id) {
                $stmt->execute([$_SESSION['login'], $lang_id]);
            }
            
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            die('Ошибка: ' . $e->getMessage());
        }
    } else {
        try {
            $db->beginTransaction();
            
            // Сохраняем заявку (оригинальная логика)
            $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, agreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $fio, $num, $email, "$year-$month-$day", $gen, $biography, $agreement
            ]);
            $application_id = $db->lastInsertId();

            // Генерируем логин и пароль (по аналогии со вторым вариантом)
            $login = substr(md5(time()), 0, 16);
            $pass = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

            // Сохраняем пользователя
            $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
            $stmt->execute([$login, $pass_hash]);
            $user_id = $db->lastInsertId();

            // Связываем пользователя и заявку
            $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $application_id]);

            // Сохраняем языки программирования
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $lang_id) {
                $stmt->execute([$application_id, $lang_id]);
            }
            
            $db->commit();
            
            // Сохраняем данные для входа (по аналогии со вторым вариантом)
            setcookie('login', $login);
            setcookie('password', $pass);
        } catch (PDOException $e) {
            $db->rollBack();
            die('Ошибка: ' . $e->getMessage());
        }
    }

    setcookie('save', '1', time() + 24 * 60 * 60);
    header('Location: index.php');
    exit();
}
