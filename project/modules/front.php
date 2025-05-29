<?php
function front_get($request) {
    $db = db_connect();
    $messages = [];
    $errors = [];
    $values = [];
    $allowed_lang = getLangs();

    // Обработка успешного сохранения
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600);
        setcookie('login', '', time() - 3600);
        setcookie('password', '', time() - 3600);
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

    // Поля формы и их ошибки
    $fields = ['fio', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'lang', 'agreement'];
    
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field.'_error']) ? getErrorMessage($field, $_COOKIE[$field.'_error']) : '';
        $values[$field] = empty($_COOKIE[$field.'_value']) ? '' : $_COOKIE[$field.'_value'];
        setcookie($field.'_error', '', time() - 3600);
        setcookie($field.'_value', '', time() - 3600);
    }

    // Загрузка данных для авторизованных пользователей
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
                $selected_langs = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $values['lang'] = implode(',', $selected_langs);
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

    // Валидации
    if (empty($values['fio'])) $errors['fio'] = 1;
    if (empty($values['phone'])) $errors['phone'] = 1;
    if (empty($values['email'])) $errors['email'] = 1;
    if (empty($values['gender'])) $errors['gender'] = 1;
    if (empty($values['biography'])) $errors['biography'] = 1;

    $langs = $values['lang'] ?? [];
    if (empty($langs) || (is_array($langs) && count($langs) == 0)) {
        $errors['lang'] = 1;
    } elseif (!is_array($langs)) {
        $errors['lang'] = 2;
    } else {
        $validLangs = array_keys(getLangs());
        foreach ($langs as $langId) {
            if (!in_array($langId, $validLangs)) {
                $errors['lang'] = 2;
                break;
            }
        }
    }

    if (empty($values['agreement'])) $errors['agreement'] = 1;

    if (empty($values['birth_day']) || empty($values['birth_month']) || empty($values['birth_year'])) {
        $errors['birth_day'] = 1;
    } elseif (!checkdate((int)$values['birth_month'], (int)$values['birth_day'], (int)$values['birth_year'])) {
        $errors['birth_day'] = 1;
    }

    // ⛔ Если есть ошибки — сохранить их в куки и вернуть
    if (!empty($errors)) {
        foreach ($values as $key => $val) {
            setcookie($key . '_value', is_array($val) ? implode(',', $val) : $val, time() + 365 * 24 * 60 * 60, '/');
        }

        foreach ($errors as $key => $val) {
            setcookie($key . '_error', $val, time() + 3600, '/');
        }

        return ['success' => false, 'errors' => $errors];
    }

    // ✅ Если всё верно — сохраняем в БД
    try {
        $db->beginTransaction();

        $birth_date = sprintf("%04d-%02d-%02d", $values['birth_year'], $values['birth_month'], $values['birth_day']);
        
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

        // Языки
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($values['lang'] as $lang_id) {
            $stmt->execute([$app_id, $lang_id]);
        }

        // Создание пользователя
        $login = 'user_' . bin2hex(random_bytes(3));
        $password = bin2hex(random_bytes(4));
        $pass_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
        $stmt->execute([$login, $pass_hash]);
        $user_id = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $app_id]);

        $db->commit();

        // Устанавливаем куки только для не-AJAX
        if (!$is_ajax) {
            setcookie('save', '1', time() + 3600, '/');
            setcookie('login', $login, time() + 3600, '/');
            setcookie('password', $password, time() + 3600, '/');
        }

        return [
            'success' => true,
            'login' => $login,
            'password' => $password
        ];

    } catch (PDOException $e) {
        $db->rollBack();
        error_log("DB Error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['db' => 'Ошибка сохранения данных']];
    }
}




function front_post($request) {
    $db = db_connect();
    if (!$db) {
        return ['success' => false, 'errors' => ['db' => 'Ошибка подключения к БД']];
    }

    $is_ajax = $request['is_ajax'] ?? false;
    $post_data = $request['post'] ?? $_POST;

    // Валидация данных
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

    // Проверка обязательных полей
    if (empty($values['fio'])) $errors['fio'] = 'Укажите ФИО';
    if (empty($values['phone'])) $errors['phone'] = 'Укажите телефон';
    if (empty($values['email'])) $errors['email'] = 'Укажите email';
    if (empty($values['gender'])) $errors['gender'] = 'Укажите пол';
    if (empty($values['biography'])) $errors['biography'] = 'Напишите биографию';

    $langs = $values['lang'] ?? [];
if (empty($langs) || (is_array($langs) && count($langs) == 0)) {
    $errors['lang'] = 'Выберите хотя бы один язык';
} elseif (!is_array($langs)) {
    $errors['lang'] = 'Некорректный формат данных';
} else {
    // Дополнительная проверка, что выбранные языки существуют в БД
    $validLangs = array_keys(getLangs());
    foreach ($langs as $langId) {
        if (!in_array($langId, $validLangs)) {
            $errors['lang'] = 'Выбран недопустимый язык программирования';
            break;
        }
    }
}
   
    if (empty($values['agreement'])) $errors['agreement'] = 'Необходимо согласие';
    
   if (empty($values['birth_day']) || empty($values['birth_month']) || empty($values['birth_year'])) {
    $errors['birth_date'] = 'Укажите дату рождения';
} elseif (!checkdate((int)$values['birth_month'], (int)$values['birth_day'], (int)$values['birth_year'])) {
    $errors['birth_date'] = 'Некорректная дата рождения';
}

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    try {
        $db->beginTransaction();

        // Сохранение заявки
        $birth_date = sprintf("%04d-%02d-%02d", $values['birth_year'], $values['birth_month'], $values['birth_day']);
        
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

        // Сохранение языков
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($values['lang'] as $lang_id) {
            $stmt->execute([$app_id, $lang_id]);
        }

        // Создание пользователя
        $login = 'user_' . bin2hex(random_bytes(3));
        $password = bin2hex(random_bytes(4));
        $pass_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
        $stmt->execute([$login, $pass_hash]);
        $user_id = $db->lastInsertId();

        // Связь пользователя с заявкой
        $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $app_id]);

        $db->commit();

        // Устанавливаем куки только для не-AJAX запросов
        if (!$is_ajax) {
            setcookie('save', '1', time() + 3600, '/');
            setcookie('login', $login, time() + 3600, '/');
            setcookie('password', $password, time() + 3600, '/');
        }

        return [
            'success' => true,
            'login' => $login,
            'password' => $password
        ];

    } catch (PDOException $e) {
        $db->rollBack();
        error_log("DB Error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['db' => 'Ошибка сохранения данных']];
    }
}


function getErrorMessage($field, $code) {
    $messages = [
        'fio' => [
            '1' => 'Имя не указано.',
            '2' => 'Имя не должно превышать 128 символов.',
            '3' => 'Имя должно содержать только буквы и пробелы.'
        ],
        'phone' => [
            '1' => 'Телефон не указан.',
            '2' => 'Телефон должен быть в формате +7XXXXXXXXXX.'
        ],
        'email' => [
            '1' => 'Email не указан.',
            '2' => 'Email должен быть в формате example@domain.com.'
        ],
        'gender' => [
            '1' => 'Пол не указан.',
            '2' => 'Недопустимое значение пола.'
        ],
        'biography' => [
            '1' => 'Биография не указана.',
            '2' => 'Биография не должна превышать 512 символов.',
            '3' => 'Биография содержит недопустимые символы.'
        ],
        'lang' => [
            '1' => 'Не выбран язык программирования.',
            '2' => 'Выбран недопустимый язык программирования.'
        ],
        'agreement' => [
            '1' => 'Необходимо согласие с контрактом.'
        ],
        'birth_day' => ['1' => 'Некорректная дата рождения.'],
        'birth_month' => ['1' => 'Некорректная дата рождения.'],
        'birth_year' => ['1' => 'Некорректная дата рождения.']
    ];

    return $messages[$field][$code] ?? 'Некорректное значение';
}
