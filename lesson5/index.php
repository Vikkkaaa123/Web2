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
            $allowed_lang[$lang['name']] = $lang['name']; // Используем name как ключ
        }
        return $allowed_lang;
    } catch (PDOException $e) {
        die('Ошибка: ' . $e->getMessage());
    }
}

$allowed_lang = getLangs($db);

// Обработка GET-запроса (показ формы)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = [];
    $errors = [];
    $values = [];

    // Список полей формы
    $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 
              'gender', 'biography', 'languages', 'agreement'];

    // Проверяем Cookies на ошибки и сохраненные значения
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : $_COOKIE[$field . '_value'];
    }

    // Удаляем Cookies с ошибками
    foreach ($fields as $field) {
        setcookie($field . '_error', '', time() - 3600);
    }

    // Формируем сообщения об ошибках
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

    // Если пользователь авторизован, загружаем его данные из БД
    if (!empty($_SESSION['login'])) {
        try {
            // Загружаем основную информацию
            $stmt = $db->prepare("SELECT a.* FROM applications a 
                                JOIN user_applications ua ON a.id = ua.application_id 
                                JOIN users u ON ua.user_id = u.id 
                                WHERE u.login = ?");
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

                // Загружаем языки программирования
                $stmt = $db->prepare("SELECT pl.name FROM programming_languages pl
                                    JOIN application_languages al ON pl.id = al.language_id
                                    WHERE al.application_id = ?");
                $stmt->execute([$application['id']]);
                $user_langs = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $values['languages'] = $user_langs;
            }
        } catch (PDOException $e) {
            die('Ошибка загрузки данных: ' . $e->getMessage());
        }
    }

    include('form.php');
    exit();
}

// Обработка POST-запроса (отправка формы)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = FALSE;

    // Получаем данные из формы
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

    if (empty($num)) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!preg_match('/^\+7\d{10}$/', $num)) {
        setcookie('phone_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    }

    if (empty($email)) {
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    }

    if (empty($gen)) {
        setcookie('gender_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } elseif (!in_array($gen, ["male", "female"])) {
        setcookie('gender_error', '2', time() + 24 * 60 * 60);
        $errors = TRUE;
    }

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

    if (!checkdate($month, $day, $year)) {
        setcookie('birth_day_error', '1', time() + 24 * 60 * 60);
        setcookie('birth_month_error', '1', time() + 24 * 60 * 60);
        setcookie('birth_year_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }

    if (empty($languages)) {
        setcookie('languages_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    } else {
        foreach ($languages as $lang) {
            if (!array_key_exists($lang, $allowed_lang)) {
                setcookie('languages_error', '2', time() + 24 * 60 * 60);
                $errors = TRUE;
                break;
            }
        }
    }

    if (!$agreement) {
        setcookie('agreement_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }

    // Сохраняем введенные значения в Cookies
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
        header('Location: index.php');
        exit();
    }

    // Удаляем Cookies с ошибками
    $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 
              'gender', 'biography', 'languages', 'agreement'];
    foreach ($fields as $field) {
        setcookie($field . '_error', '', time() - 3600);
    }

    // Если пользователь авторизован - обновляем данные
    if (!empty($_SESSION['login'])) {
        try {
            $db->beginTransaction();

            // Обновляем основную информацию
            $stmt = $db->prepare("UPDATE applications SET full_name = ?, phone = ?, email = ?, 
                                 birth_date = ?, gender = ?, biography = ?, agreement = ? 
                                 WHERE id = (SELECT application_id FROM user_applications 
                                            WHERE user_id = (SELECT id FROM users WHERE login = ?))");
            $stmt->execute([$fio, $num, $email, "$year-$month-$day", $gen, $biography, $agreement, $_SESSION['login']]);

            // Получаем ID заявки
            $stmt = $db->prepare("SELECT application_id FROM user_applications 
                                 WHERE user_id = (SELECT id FROM users WHERE login = ?)");
            $stmt->execute([$_SESSION['login']]);
            $application_id = $stmt->fetchColumn();

            // Удаляем старые языки
            $db->prepare("DELETE FROM application_languages WHERE application_id = ?")
               ->execute([$application_id]);

            // Добавляем новые языки
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) 
                                 VALUES (?, (SELECT id FROM programming_languages WHERE name = ?))");
            foreach ($languages as $lang_name) {
                $stmt->execute([$application_id, $lang_name]);
            }

            $db->commit();

            setcookie('save', '1', time() + 24 * 60 * 60);
            header('Location: index.php');
            exit();

        } catch (PDOException $e) {
            $db->rollBack();
            die('Ошибка обновления: ' . $e->getMessage());
        }
    } 
    // Если пользователь не авторизован - создаем новую запись
    else {
        try {
            $db->beginTransaction();

            // Сохраняем основную информацию
            $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, 
                                 gender, biography, agreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fio, $num, $email, "$year-$month-$day", $gen, $biography, $agreement]);
            $application_id = $db->lastInsertId();

            // Сохраняем языки программирования
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) 
                                 VALUES (?, (SELECT id FROM programming_languages WHERE name = ?))");
            foreach ($languages as $lang_name) {
                $stmt->execute([$application_id, $lang_name]);
            }

            // Генерируем логин и пароль
            $login = uniqid('user_');
            $pass = bin2hex(random_bytes(8));
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

            // Сохраняем пользователя
            $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
            $stmt->execute([$login, $pass_hash]);
            $user_id = $db->lastInsertId();

            // Связываем пользователя и заявку
            $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $application_id]);

            $db->commit();

            // Сохраняем логин и пароль для показа пользователю
            $_SESSION['generated_login'] = $login;
            $_SESSION['generated_password'] = $pass;
            header('Location: login.php');
            exit();

        } catch (PDOException $e) {
            $db->rollBack();
            die('Ошибка сохранения: ' . $e->getMessage());
        }
    }
}
?>
