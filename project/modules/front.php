<?php
function front_get($request) {
    $db = db_connect();
    $messages = [];
    $errors = [];
    $values = [];
    $allowed_lang = getLangs();

    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600, '/');
        $messages[] = 'Спасибо, результаты сохранены.';

        if (!empty($_COOKIE['login']) && !empty($_COOKIE['password'])) {
            $messages[] = sprintf(
                'Вы можете <a href="%s">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.',
                htmlspecialchars(url('login'), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($_COOKIE['login'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($_COOKIE['password'], ENT_QUOTES, 'UTF-8')
            );
            setcookie('login', '', time() - 3600, '/');
            setcookie('password', '', time() - 3600, '/');
        }
    }

    $fields = ['fio', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'lang', 'agreement'];

    foreach ($fields as $field) {
        $errors[$field] = '';
        if (!empty($_COOKIE[$field . '_error'])) {
            $errors[$field] = getErrorMessage($field, $_COOKIE[$field . '_error']);
            setcookie($field . '_error', '', time() - 3600, '/');
        }

        $values[$field] = $_COOKIE[$field . '_value'] ?? '';
    }

    if (!empty($_SESSION['login'])) {
        try {
            $stmt = $db->prepare("SELECT a.* FROM applications a 
                                  JOIN user_applications ua ON a.id = ua.application_id 
                                  JOIN users u ON ua.user_id = u.id 
                                  WHERE u.login = ?");
            $stmt->execute([$_SESSION['login']]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($application) {
                $values['fio'] = $application['full_name'];
                $values['phone'] = $application['phone'];
                $values['email'] = $application['email'];
                $values['birth_day'] = date('d', strtotime($application['birth_date']));
                $values['birth_month'] = date('m', strtotime($application['birth_date']));
                $values['birth_year'] = date('Y', strtotime($application['birth_date']));
                $values['gender'] = $application['gender'];
                $values['biography'] = $application['biography'];
                $values['agreement'] = $application['agreement'];

                $stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
                $stmt->execute([$application['id']]);
                $lang_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $values['lang'] = implode(',', $lang_ids);
            }
        } catch (PDOException $e) {
            error_log('DB Error: ' . $e->getMessage());
            $messages[] = '<div class="error">Ошибка загрузки данных.</div>';
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
    if (!$db) {
        return ['success' => false, 'errors' => ['db' => 'Ошибка подключения к БД']];
    }

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

    if ($values['fio'] === '') $errors['fio'] = '1';
    if ($values['phone'] === '') $errors['phone'] = '1';
    if ($values['email'] === '') $errors['email'] = '1';
    if ($values['gender'] === '') $errors['gender'] = '1';
    if ($values['biography'] === '') $errors['biography'] = '1';
    if (empty($values['lang'])) $errors['lang'] = '1';
    if (empty($values['agreement'])) $errors['agreement'] = '1';

    if (empty($values['birth_day']) || empty($values['birth_month']) || empty($values['birth_year']) ||
        !checkdate((int)$values['birth_month'], (int)$values['birth_day'], (int)$values['birth_year'])) {
        $errors['birth_day'] = '1';
    }

    foreach ($values as $key => $val) {
        setcookie($key . '_value', is_array($val) ? implode(',', $val) : $val, time() + 365 * 24 * 60 * 60, '/');
    }

    if (!empty($errors)) {
        foreach ($errors as $key => $val) {
            setcookie($key . '_error', $val, time() + 3600, '/');
        }
        return ['success' => false];
    }

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
        foreach ((array)$values['lang'] as $lang_id) {
            $stmt->execute([$app_id, $lang_id]);
        }

        $login = 'user_' . bin2hex(random_bytes(3));
        $password = bin2hex(random_bytes(4));
        $pass_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
        $stmt->execute([$login, $pass_hash]);
        $user_id = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $app_id]);

        $db->commit();

        if (!$is_ajax) {
            setcookie('save', '1', time() + 3600, '/');
            setcookie('login', $login, time() + 3600, '/');
            setcookie('password', $password, time() + 3600, '/');
        }

        return ['success' => true, 'login' => $login, 'password' => $password];

    } catch (PDOException $e) {
        $db->rollBack();
        error_log('DB Error: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['db' => 'Ошибка при сохранении данных']];
    }
}

function getErrorMessage($field, $code) {
    $messages = [
        'fio' => ['1' => 'Имя не указано.'],
        'phone' => ['1' => 'Телефон не указан.'],
        'email' => ['1' => 'Email не указан.'],
        'gender' => ['1' => 'Пол не выбран.'],
        'biography' => ['1' => 'Биография не заполнена.'],
        'lang' => ['1' => 'Не выбран ни один язык.'],
        'agreement' => ['1' => 'Необходимо согласие.'],
        'birth_day' => ['1' => 'Некорректная дата рождения.'],
        'birth_month' => ['1' => 'Некорректная дата рождения.'],
        'birth_year' => ['1' => 'Некорректная дата рождения.']
    ];

    return $messages[$field][$code] ?? 'Ошибка';
}
