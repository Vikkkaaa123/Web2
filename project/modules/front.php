<?php
function front_get($request) {
    $db = db_connect();
    $messages = [];
    $errors = [];
    $values = [];
    $allowed_lang = getLangs();

    // Если форма была успешно сохранена
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600, '/');
        setcookie('login', '', time() - 3600, '/');
        setcookie('password', '', time() - 3600, '/');
        $messages[] = 'Спасибо, результаты сохранены.';

        if (!empty($_COOKIE['password'])) {
            $messages[] = sprintf(
                'Вы можете <a href="%s">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.',
                htmlspecialchars(url('login'), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($_COOKIE['login'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($_COOKIE['password'], ENT_QUOTES, 'UTF-8')
            );
        }
    }

    $fields = ['fio', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'lang', 'agreement'];

    foreach ($fields as $field) {
        $error_code = $_COOKIE[$field . '_error'] ?? '';
        $errors[$field] = $error_code ? getErrorMessage($field, $error_code) : '';
        $values[$field] = $_COOKIE[$field . '_value'] ?? '';
    }

    // Если авторизован, загрузка данных из БД
    if (!empty($_SESSION['login'])) {
        try {
            $stmt = $db->prepare("SELECT a.* FROM applications a 
                                  JOIN user_applications ua ON a.id = ua.application_id 
                                  JOIN users u ON ua.user_id = u.id 
                                  WHERE u.login = ?");
            $stmt->execute([$_SESSION['login']]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($app) {
                $values['fio'] = $app['full_name'];
                $values['phone'] = $app['phone'];
                $values['email'] = $app['email'];
                $values['birth_day'] = date('d', strtotime($app['birth_date']));
                $values['birth_month'] = date('m', strtotime($app['birth_date']));
                $values['birth_year'] = date('Y', strtotime($app['birth_date']));
                $values['gender'] = $app['gender'];
                $values['biography'] = $app['biography'];
                $values['agreement'] = $app['agreement'];

                $stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
                $stmt->execute([$app['id']]);
                $values['lang'] = implode(',', $stmt->fetchAll(PDO::FETCH_COLUMN));
            }
        } catch (PDOException $e) {
            error_log('DB Error: ' . $e->getMessage());
            $messages[] = 'Ошибка загрузки данных';
        }
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
    if (!$db) return ['success' => false, 'errors' => ['db' => 'Ошибка БД']];

    $post = $request['post'] ?? $_POST;
    $is_ajax = $request['is_ajax'] ?? false;

    $values = [
        'fio' => trim($post['fio'] ?? ''),
        'phone' => trim($post['phone'] ?? ''),
        'email' => trim($post['email'] ?? ''),
        'birth_day' => trim($post['birth_day'] ?? ''),
        'birth_month' => trim($post['birth_month'] ?? ''),
        'birth_year' => trim($post['birth_year'] ?? ''),
        'gender' => $post['gender'] ?? '',
        'biography' => trim($post['biography'] ?? ''),
        'lang' => $post['languages'] ?? [],
        'agreement' => isset($post['agreement']) ? 1 : 0
    ];

    $errors = [];

    // Валидация
    if ($values['fio'] === '') $errors['fio'] = 1;
    if ($values['phone'] === '') $errors['phone'] = 1;
    if ($values['email'] === '') $errors['email'] = 1;
    if ($values['gender'] === '') $errors['gender'] = 1;
    if ($values['biography'] === '') $errors['biography'] = 1;
    if (!$values['agreement']) $errors['agreement'] = 1;

    if (empty($values['lang']) || !is_array($values['lang']) || count($values['lang']) === 0) {
        $errors['lang'] = 1;
    } else {
        $allowed = array_keys(getLangs());
        foreach ($values['lang'] as $l) {
            if (!in_array($l, $allowed)) {
                $errors['lang'] = 2;
                break;
            }
        }
    }

    if (!$values['birth_day'] || !$values['birth_month'] || !$values['birth_year'] ||
        !checkdate((int)$values['birth_month'], (int)$values['birth_day'], (int)$values['birth_year'])) {
        $errors['birth_day'] = 1;
    }

    // Сохраняем значения и ошибки в куки
    foreach ($values as $key => $val) {
        setcookie($key . '_value', is_array($val) ? implode(',', $val) : $val, time() + 365*24*60*60, '/');
    }

    // Удаляем старые ошибки
    foreach (array_keys($values) as $key) {
        setcookie($key . '_error', '', time() - 3600, '/');
    }

    if (!empty($errors)) {
        foreach ($errors as $key => $code) {
            setcookie($key . '_error', $code, time() + 3600, '/');
        }
        return ['success' => false, 'errors' => $errors];
    }

    // Сохраняем в БД
    try {
        $db->beginTransaction();

        $birth_date = sprintf('%04d-%02d-%02d', $values['birth_year'], $values['birth_month'], $values['birth_day']);
        $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, agreement)
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $values['fio'], $values['phone'], $values['email'],
            $birth_date, $values['gender'], $values['biography'], $values['agreement']
        ]);
        $app_id = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($values['lang'] as $lang_id) {
            $stmt->execute([$app_id, $lang_id]);
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

        if (!$is_ajax) {
            setcookie('save', '1', time() + 3600, '/');
            setcookie('login', $login, time() + 3600, '/');
            setcookie('password', $password, time() + 3600, '/');

            // Удаляем ошибки
            foreach (array_keys($values) as $key) {
                setcookie($key . '_error', '', time() - 3600, '/');
            }
        }

        return ['success' => true, 'login' => $login, 'password' => $password];
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("DB Error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['db' => 'Ошибка сохранения данных']];
    }
}


function getErrorMessage($field, $code) {
    $map = [
        'fio' => [
            1 => 'Поле ФИО обязательно.',
        ],
        'phone' => [
            1 => 'Поле телефона обязательно.',
        ],
        'email' => [
            1 => 'Поле email обязательно.',
        ],
        'gender' => [
            1 => 'Укажите пол.',
        ],
        'biography' => [
            1 => 'Биография обязательна.',
        ],
        'lang' => [
            1 => 'Выберите хотя бы один язык.',
            2 => 'Выбран недопустимый язык.',
        ],
        'agreement' => [
            1 => 'Вы должны согласиться с условиями.',
        ],
        'birth_day' => [
            1 => 'Введите корректную дату рождения.',
        ]
    ];

    return $map[$field][$code] ?? 'Ошибка в поле ' . $field;
}
