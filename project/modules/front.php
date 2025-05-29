 <?php
function front_get($request) {
    $messages = [];
    $errors = [];
    $values = [];
    $allowed_lang = getLangs();

    $fields = ['fio', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year',
               'gender', 'biography', 'languages', 'agreement'];

    foreach ($fields as $field) {
        $values[$field] = $_COOKIE["{$field}_value"] ?? '';

        $errors[$field] = '';
        if (!empty($_COOKIE["{$field}_error"])) {
            $errors[$field] = getErrorMessage($field, $_COOKIE["{$field}_error"]);
        }

        setcookie("{$field}_value", '', time() - 3600, '/');
        setcookie("{$field}_error", '', time() - 3600, '/');
    }

    if (!empty($_COOKIE['save'])) {
        $messages[] = 'Спасибо, результаты сохранены.';
        setcookie('save', '', time() - 3600, '/');
    }

    return theme('form', [
        'messages' => $messages,
        'errors' => $errors,
        'values' => $values,
        'allowed_lang' => $allowed_lang
    ]);
}

function front_post($request) {
    $db = db_connect();
    if (!$db) {
        returnJsonOrRedirect(false, ['db' => 'Ошибка подключения к базе данных']);
    }

    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $post = $_POST;
    $errors = [];
    $values = [];

    // Все поля
    $values['fio'] = trim($post['fio'] ?? '');
    $values['phone'] = trim($post['phone'] ?? '');
    $values['email'] = trim($post['email'] ?? '');
    $values['birth_day'] = (int)($post['birth_day'] ?? 0);
    $values['birth_month'] = (int)($post['birth_month'] ?? 0);
    $values['birth_year'] = (int)($post['birth_year'] ?? 0);
    $values['gender'] = $post['gender'] ?? '';
    $values['languages'] = $post['languages'] ?? [];
    $values['biography'] = trim($post['biography'] ?? '');
    $values['agreement'] = isset($post['agreement']) ? 1 : 0;

    // Валидация
    if ($values['fio'] === '') $errors['fio'] = 'Укажите ФИО';
    if ($values['phone'] === '') $errors['phone'] = 'Укажите телефон';
    if ($values['email'] === '') $errors['email'] = 'Укажите email';

    if ($values['birth_day'] === 0 || $values['birth_month'] === 0 || $values['birth_year'] === 0) {
        $errors['birth_day'] = 'Некорректная дата';
        $errors['birth_month'] = 'Некорректная дата';
        $errors['birth_year'] = 'Некорректная дата';
    } elseif (!checkdate($values['birth_month'], $values['birth_day'], $values['birth_year'])) {
        $errors['birth_day'] = 'Некорректная дата';
        $errors['birth_month'] = 'Некорректная дата';
        $errors['birth_year'] = 'Некорректная дата';
    }

    if ($values['gender'] !== 'male' && $values['gender'] !== 'female') {
        $errors['gender'] = 'Укажите пол';
    }

    if (empty($values['languages']) || !is_array($values['languages'])) {
        $errors['languages'] = 'Выберите хотя бы один язык';
    }

    if ($values['biography'] === '') {
        $errors['biography'] = 'Напишите биографию';
    }

    if (!$values['agreement']) {
        $errors['agreement'] = 'Необходимо согласие';
    }

    // Если есть ошибки — вернуть или сохранить в куки
    if (!empty($errors)) {
        foreach ($values as $key => $val) {
            if ($key === 'languages') {
                setcookie("{$key}_value", implode(',', $val), time() + 3600, '/');
            } else {
                setcookie("{$key}_value", $val, time() + 3600, '/');
            }
        }
        foreach ($errors as $key => $msg) {
            setcookie("{$key}_error", 1, time() + 3600, '/');
        }

        returnJsonOrRedirect(false, $errors);
    }

    // Сохранение
    try {
        $db->beginTransaction();

        $birth = sprintf('%04d-%02d-%02d', $values['birth_year'], $values['birth_month'], $values['birth_day']);

        $stmt = $db->prepare("INSERT INTO applications 
            (full_name, phone, email, birth_date, gender, biography, agreement)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $values['fio'], $values['phone'], $values['email'],
            $birth, $values['gender'], $values['biography'], $values['agreement']
        ]);
        $app_id = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($values['languages'] as $lang) {
            $stmt->execute([$app_id, $lang]);
        }

        $login = 'user_' . bin2hex(random_bytes(3));
        $password = bin2hex(random_bytes(4));
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
        $stmt->execute([$login, $hash]);

        $user_id = $db->lastInsertId();
        $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $app_id]);

        $db->commit();

        setcookie('login', $login, time() + 3600, '/');
        setcookie('password', $password, time() + 3600, '/');
        setcookie('save', 1, time() + 3600, '/');

        returnJsonOrRedirect(true, [], $login, $password);

    } catch (PDOException $e) {
        $db->rollBack();
        returnJsonOrRedirect(false, ['db' => 'Ошибка при сохранении в базу']);
    }
}

function returnJsonOrRedirect($success, $errors = [], $login = '', $password = '') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'errors' => $errors,
            'login' => $login,
            'password' => $password
        ]);
        exit;
    } else {
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

function getErrorMessage($field, $code = 1) {
    $messages = [
        'fio' => 'Укажите ФИО',
        'phone' => 'Укажите телефон',
        'email' => 'Укажите email',
        'birth_day' => 'Укажите день рождения',
        'birth_month' => 'Укажите месяц рождения',
        'birth_year' => 'Укажите год рождения',
        'gender' => 'Укажите пол',
        'languages' => 'Выберите хотя бы один язык',
        'biography' => 'Напишите биографию',
        'agreement' => 'Необходимо согласие'
    ];
    return $messages[$field] ?? 'Ошибка в поле';
}
