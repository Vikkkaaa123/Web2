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
    
    // Сообщение об успешном сохранении
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600);
        $messages[] = 'Спасибо, результаты сохранены.';
        
        // Показываем логин и пароль если они есть в куках
        if (!empty($_COOKIE['login']) && !empty($_COOKIE['password'])) {
            $messages[] = sprintf(
                'Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.',
                strip_tags($_COOKIE['login']),
                strip_tags($_COOKIE['password'])
            );
        }
    }

    $errors = array();
    $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'languages', 'agreement'];
    
    // Проверяем ошибки
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
    }

    // Сообщения об ошибках
    if ($errors['full_name']) {
        $code = $_COOKIE['full_name_error'];
        if ($code == '1') $messages[] = '<div class="error">Имя не указано.</div>';
        elseif ($code == '2') $messages[] = '<div class="error">Имя не должно превышать 128 символов.</div>';
        else $messages[] = '<div class="error">Имя должно содержать только буквы и пробелы.</div>';
    }
    // Аналогично для других полей...

    // Удаляем куки ошибок
    foreach ($fields as $field) {
        setcookie($field . '_error', '', time() - 3600);
    }

    $values = array();
    foreach ($fields as $field) {
        $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : $_COOKIE[$field . '_value'];
    }

    // Особый случай для языков программирования
    if (!empty($_COOKIE['languages_value'])) {
        $values['languages'] = explode(',', $_COOKIE['languages_value']);
    } else {
        $values['languages'] = array();
    }

    // Если пользователь авторизован, загружаем его данные
    if (!empty($_SESSION['login'])) {
        try {
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
                
                // Получаем выбранные языки программирования
                $stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
                $stmt->execute([$application['id']]);
                $values['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            }
            
            // Добавляем сообщение о текущем входе
            $messages[] = 'Вы вошли как ' . $_SESSION['login'] . 
                         ' (<a href="logout.php">выйти</a>)';
        } catch (PDOException $e) {
            die('Ошибка: ' . $e->getMessage());
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

    // Аналогичная валидация для других полей...

    // Сохраняем языки программирования
    setcookie('languages_value', implode(',', $languages), time() + 365 * 24 * 60 * 60);

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    // Удаляем куки ошибок
    $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'languages', 'agreement'];
    foreach ($fields as $field) {
        setcookie($field . '_error', '', time() - 3600);
    }

    // Если пользователь авторизован, обновляем данные
    if (!empty($_SESSION['login'])) {
        try {
            $db->beginTransaction();
            
            // Обновляем заявку
            $stmt = $db->prepare("UPDATE applications SET full_name = ?, phone = ?, email = ?, birth_date = ?, gender = ?, biography = ?, agreement = ? WHERE id = (SELECT application_id FROM user_applications WHERE user_id = (SELECT id FROM users WHERE login = ?))");
            $stmt->execute([$fio, $num, $email, "$year-$month-$day", $gen, $biography, $agreement, $_SESSION['login']]);
            
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
            
            // Сохраняем заявку
            $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, agreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fio, $num, $email, "$year-$month-$day", $gen, $biography, $agreement]);
            $application_id = $db->lastInsertId();

            // Генерируем логин и пароль
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
            
            // Сохраняем данные для входа в куки
            setcookie('login', $login, time() + 24 * 60 * 60);
            setcookie('password', $pass, time() + 24 * 60 * 60);
            
            // Автоматически входим под новым пользователем
            $_SESSION['login'] = $login;
            $_SESSION['uid'] = $user_id;
        } catch (PDOException $e) {
            $db->rollBack();
            die('Ошибка: ' . $e->getMessage());
        }
    }

    setcookie('save', '1', time() + 24 * 60 * 60);
    header('Location: index.php');
    exit();
}
