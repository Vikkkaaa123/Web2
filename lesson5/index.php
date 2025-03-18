<?php
session_start();
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

    // Удаляем Cookies с ошибками после использования
    foreach ($fields as $field) {
        setcookie($field . '_error', '', time() - 3600); // Удаляем Cookie с ошибкой
    }

    // Выводим сообщения об ошибках
    if ($errors['full_name']) {
        $messages['full_name'] = 'Некорректное имя. Допустимы только буквы и пробелы.';
    }
    if ($errors['phone']) {
        $messages['phone'] = 'Некорректный номер телефона. Формат: +7XXXXXXXXXX.';
    }
    if ($errors['email']) {
        $messages['email'] = 'Некорректный email.';
    }
    if ($errors['birth_day'] || $errors['birth_month'] || $errors['birth_year']) {
        $messages['birth_date'] = 'Некорректная дата рождения.';
    }
    if ($errors['gender']) {
        $messages['gender'] = 'Некорректный пол.';
    }
    if ($errors['biography']) {
        $messages['biography'] = 'Некорректная биография.';
    }
    if ($errors['languages']) {
        $messages['languages'] = 'Не выбран язык программирования.';
    }
    if ($errors['agreement']) {
        $messages['agreement'] = 'Необходимо согласие.';
    }

    // Если пользователь авторизован, загружаем его данные
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

    // Валидация данных
    if (empty($fio) || strlen($fio) > 128 || !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]+$/u', $fio)) {
        setcookie('full_name_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    if (empty($num) || !preg_match('/^\+7\d{10}$/', $num)) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    if (empty($gen) || !in_array($gen, ["male", "female"])) {
        setcookie('gender_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    if (empty($biography) || strlen($biography) > 512) {
        setcookie('biography_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    if (!checkdate($month, $day, $year)) {
        setcookie('birth_day_error', '1', time() + 24 * 60 * 60);
        setcookie('birth_month_error', '1', time() + 24 * 60 * 60);
        setcookie('birth_year_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    if (empty($languages)) {
        setcookie('languages_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    if (!$agreement) {
        setcookie('agreement_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }

    // Сохраняем значения всех полей в Cookies
    setcookie('full_name_value', $fio, time() + 365 * 24 * 60 * 60);
    setcookie('phone_value', $num, time() + 365 * 24 * 60 * 60);
    setcookie('email_value', $email, time() + 365 * 24 * 60 * 60);
    setcookie('birth_day_value', $day, time() + 365 * 24 * 60 * 60);
    setcookie('birth_month_value', $month, time() + 365 * 24 * 60 * 60);
    setcookie('birth_year_value', $year, time() + 365 * 24 * 60 * 60);
    setcookie('gender_value', $gen, time() + 365 * 24 * 60 * 60);
    setcookie('biography_value', $biography, time() + 365 * 24 * 60 * 60);
    setcookie('languages_value', implode(',', $languages), time() + 365 * 24 * 60 * 60);
    setcookie('agreement_value', $agreement, time() + 365 * 24 * 60 * 60);

    if ($errors) {
        // Перенаправляем на форму с сохранением данных
        header('Location: index.php');
        exit();
    } else {
        // Удаляем Cookies с признаками ошибок
        $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'languages', 'agreement'];
        foreach ($fields as $field) {
            setcookie($field . '_error', '', time() - 3600); // Удаляем Cookie с ошибкой
        }

        // Если пользователь авторизован, обновляем данные
        if (!empty($_SESSION['login'])) {
            $stmt = $db->prepare("UPDATE applications SET full_name = ?, phone = ?, email = ?, birth_date = ?, gender = ?, biography = ?, agreement = ? WHERE id = (SELECT application_id FROM user_applications WHERE user_id = (SELECT id FROM users WHERE login = ?))");
            $stmt->execute([
                $fio, $num, $email, "$year-$month-$day", $gen, $biography, $agreement, $_SESSION['login']
            ]);
        } else {
            // Сохраняем данные формы в таблицу `applications`
            $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, agreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $fio, $num, $email, "$year-$month-$day", $gen, $biography, $agreement
            ]);
            $application_id = $db->lastInsertId();

            // Генерируем логин и пароль
            $login = uniqid('user_');
            $pass = bin2hex(random_bytes(8));
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

            // Сохраняем пользователя в таблицу `users`
            $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
            $stmt->execute([$login, $pass_hash]);
            $user_id = $db->lastInsertId();

            // Связываем пользователя и заявку через таблицу `user_applications`
            $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $application_id]);

            // Сохраняем логин и пароль в Cookies
            setcookie('login', $login);
            setcookie('pass', $pass);

            // Отображаем логин и пароль пользователю
            echo "Ваш логин: $login<br>Ваш пароль: $pass<br>";
            echo '<a href="login.php">Войти</a>';
        }

        // Перенаправляем на форму
        setcookie('save', '1', time() + 24 * 60 * 60);
        header('Location: index.php');
        exit();
    }
}