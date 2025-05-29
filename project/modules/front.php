<?php
function front_get($request) {
    $db = db_connect();
    $messages = [];
    $errors = [];
    $values = [];
    $allowed_lang = getLangs();

    $fields = ['fio', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'lang', 'agreement'];

    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field.'_error']) ? getErrorMessage($field, $_COOKIE[$field.'_error']) : '';
        $values[$field] = isset($_COOKIE[$field.'_value']) ? $_COOKIE[$field.'_value'] : '';
        setcookie($field.'_error', '', time() - 3600, '/');
    }

    // Обработка успешного сохранения
    if (!empty($_COOKIE['save'])) {
        $messages[] = 'Спасибо, результаты сохранены.';

        if (!empty($_COOKIE['login']) && !empty($_COOKIE['password'])) {
            $messages[] = sprintf(
                'Вы можете <a href="%s">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.',
                htmlspecialchars(url('login'), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($_COOKIE['login'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($_COOKIE['password'], ENT_QUOTES, 'UTF-8')
            );
        }

        // Удаляем куки после показа
        setcookie('save', '', time() - 3600, '/');
        setcookie('login', '', time() - 3600, '/');
        setcookie('password', '', time() - 3600, '/');
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
        return ['success' => false, 'errors' => ['db' => 'Ошибка подключения к БД']];
    }

    $is_ajax = $request['is_ajax'] ?? false;
    $post_data = $request['post'] ?? $_POST;

    $errors = [];
    $values = [
        'fio' => trim($post_data['fio'] ?? ''),
        'phone' => trim($post_data['phone'] ?? ''),
        'email' => trim($post_data['email'] ?? ''),
        'birth_day' => trim($post_data['birth_day'] ?? ''),
        'birth_month' => trim($post_data['birth_month'] ?? ''),
        'birth_year' => trim($post_data['birth_year'] ?? ''),
        'gender' => $post_data['gender'] ?? '',
        'biography' => trim($post_data['biography'] ?? ''),
        'lang' => $post_data['languages'] ?? [],
        'agreement' => isset($post_data['agreement']) ? 1 : 0
    ];

    // Валидация
    if ($values['fio'] === '') $errors['fio'] = 1;
    if ($values['phone'] === '') $errors['phone'] = 1;
    if ($values['email'] === '') $errors['email'] = 1;
    if ($values['gender'] === '') $errors['gender'] = 1;
    if ($values['biography'] === '') $errors['biography'] = 1;
    if (empty($values['lang']) || !is_array($values['lang'])) {
        $errors['lang'] = 1;
    } else {
        $validLangs = array_keys(getLangs());
        foreach ($values['lang'] as $langId) {
            if (!in_array($langId, $validLangs)) {
                $errors['lang'] = 2;
                break;
            }
        }
    }

    if (!$values['agreement']) $errors['agreement'] = 1;

    // Дата рождения
    if (empty($values['birth_day']) || empty($values['birth_month']) || empty($values['birth_year'])) {
        $errors['birth_day'] = 1;
    } elseif (!checkdate((int)$values['birth_month'], (int)$values['birth_day'], (int)$values['birth_year'])) {
        $errors['birth_day'] = 1;
    }

    // Сохраняем значения в куки
    foreach ($values as $key => $val) {
        setcookie($key . '_value', is_array($val) ? implode(',', $val) : $val, time() + 365 * 24 * 60 * 60, '/');
    }

    // Если есть ошибки
    if (!empty($errors)) {
        foreach ($errors as $key => $code) {
            setcookie($key . '_error', $code, time() + 3600, '/');
        }
        return ['success' => false];
    }

    // Сохраняем в БД
    try {
        $db->beginTransaction();

        $birth_date = sprintf('%04d-%02d-%02d', $values['birth_year'], $values['birth_month'], $values['birth_day']);

        $stmt = $db->prepare("INSERT INTO applications 
            (full_name, phone, email, birth_date, gender, biography, agreement) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $values['fio'],
            $values['phone'],
            $values['email'],
            $birth_date,
            $values['gender'],
            $values['biography'],
            $values['agreement']
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
            setcookie('save', 1, time() + 3600, '/');
            setcookie('login', $login, time() + 3600, '/');
            setcookie('password', $password, time() + 3600, '/');
        }

        return ['success' => true, 'login' => $login, 'password' => $password];
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('DB Error: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['db' => 'Ошибка при сохранении в БД']];
    }
}

function getErrorMessage($field, $code) {
    $messages = [
        'fio' => [
            '1' => 'Имя не указано.'
        ],
        'phone' => [
            '1' => 'Телефон не указан.'
        ],
        'email' => [
            '1' => 'Email не указан.'
        ],
        'gender' => [
            '1' => 'Пол не указан.'
        ],
        'biography' => [
            '1' => 'Биография не указана.'
        ],
        'lang' => [
            '1' => 'Не выбран язык программирования.',
            '2' => 'Выбран недопустимый язык.'
        ],
        'agreement' => [
            '1' => 'Вы должны согласиться с условиями.'
        ],
        'birth_day' => [
            '1' => 'Неверная или неполная дата рождения.'
        ]
    ];
    return $messages[$field][$code] ?? 'Ошибка в поле';
}
