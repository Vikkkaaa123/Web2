<?php
// Обработчик GET запросов
function front_get($request) {
    $db = db_connect();
    $messages = [];
    $errors = [];
    $values = [];
    $allowed_lang = getLangs($db);

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
    $fields = ['full_name', 'phone', 'email', 'birth_day', 'birth_month', 'birth_year', 'gender', 'biography', 'languages', 'agreement'];
    
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field.'_error']);
        $values[$field] = empty($_COOKIE[$field.'_value']) ? '' : $_COOKIE[$field.'_value'];
        setcookie($field.'_error', '', time() - 3600);
    }

    // Сообщения об ошибках
    $errorMessages = [
        'full_name' => [
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
        'languages' => [
            '1' => 'Не выбран язык программирования.',
            '2' => 'Выбран недопустимый язык программирования.'
        ],
        'agreement' => [
            '1' => 'Необходимо согласие с контрактом.'
        ]
    ];

    foreach ($errorMessages as $field => $messagesByCode) {
        if ($errors[$field]) {
            $code = $_COOKIE[$field.'_error'];
            $messages[] = '<div class="error">'.($messagesByCode[$code] ?? 'Некорректное значение.').'</div>';
        }
    }

    // Проверка даты рождения
    if ($errors['birth_day'] || $errors['birth_month'] || $errors['birth_year']) {
        $messages[] = '<div class="error">Некорректная дата рождения.</div>';
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
                $values['full_name'] = $application['full_name'];
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
                $values['languages'] = implode(',', $selected_langs);
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

// Обработчик POST запросов
function front_post($request) {
    $db = db_connect();
    $allowed_lang = getLangs($db);
    $errors = false;

    // Получение данных формы
    $fields = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'birth_day' => trim($_POST['birth_day'] ?? ''),
        'birth_month' => trim($_POST['birth_month'] ?? ''),
        'birth_year' => trim($_POST['birth_year'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'biography' => trim($_POST['biography'] ?? ''),
        'languages' => isset($_POST['languages']) && is_array($_POST['languages']) ? $_POST['languages'] : [],
        'agreement' => isset($_POST['agreement']) && $_POST['agreement'] === 'on' ? 1 : 0
    ];

    // Валидация
    $validationRules = [
        'full_name' => [
            'required' => true,
            'max_length' => 128,
            'regex' => '/^[a-zA-Zа-яА-ЯёЁ\s]+$/u'
        ],
        'phone' => [
            'required' => true,
            'regex' => '/^\+7\d{10}$/'
        ],
        'email' => [
            'required' => true,
            'filter' => FILTER_VALIDATE_EMAIL
        ],
        'gender' => [
            'required' => true,
            'allowed_values' => ['male', 'female']
        ],
        'biography' => [
            'required' => true,
            'max_length' => 512,
            'forbidden_pattern' => '/[<>{}\[\]]|<script|<\?php/i'
        ],
        'languages' => [
            'required' => true,
            'allowed_values' => array_keys($allowed_lang)
        ],
        'agreement' => [
            'required' => true
        ]
    ];

    foreach ($validationRules as $field => $rules) {
        $value = $fields[$field];
        
        if ($rules['required'] && empty($value)) {
            setcookie($field.'_error', '1', time() + 3600);
            $errors = true;
        } elseif ($field === 'languages' && empty($value)) {
            setcookie($field.'_error', '1', time() + 3600);
            $errors = true;
        } elseif ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            setcookie($field.'_error', '2', time() + 3600);
            $errors = true;
        } elseif (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
            setcookie($field.'_error', '2', time() + 3600);
            $errors = true;
        } elseif (isset($rules['regex']) && !preg_match($rules['regex'], $value)) {
            setcookie($field.'_error', '3', time() + 3600);
            $errors = true;
        } elseif (isset($rules['allowed_values']) && !array_diff($value, $rules['allowed_values']) !== $value) {
            setcookie($field.'_error', '2', time() + 3600);
            $errors = true;
        } elseif (isset($rules['forbidden_pattern']) && preg_match($rules['forbidden_pattern'], $value)) {
            setcookie($field.'_error', '3', time() + 3600);
            $errors = true;
        }
        
        setcookie($field.'_value', is_array($value) ? implode(',', $value) : $value, time() + 3600);
    }

    // Проверка даты рождения
    if (!checkdate($fields['birth_month'], $fields['birth_day'], $fields['birth_year'])) {
        setcookie('birth_day_error', '1', time() + 3600);
        setcookie('birth_month_error', '1', time() + 3600);
        setcookie('birth_year_error', '1', time() + 3600);
        $errors = true;
    }

    // Редирект при ошибках
    if ($errors) {
        header('Location: ' . url(''));
        exit();
    }

    // Очистка ошибок
    foreach ($fields as $field => $value) {
        setcookie($field.'_error', '', time() - 3600);
    }

    // Сохранение данных
    try {
        $birth_date = sprintf("%04d-%02d-%02d", $fields['birth_year'], $fields['birth_month'], $fields['birth_day']);

        if (!empty($_SESSION['login'])) {
            // Обновление существующей заявки
            $stmt = $db->prepare("UPDATE applications SET 
                full_name = ?, phone = ?, email = ?, birth_date = ?, 
                gender = ?, biography = ?, agreement = ? 
                WHERE id = (SELECT application_id FROM user_applications 
                           WHERE user_id = (SELECT id FROM users WHERE login = ?))");
            $stmt->execute([
                $fields['full_name'], $fields['phone'], $fields['email'], $birth_date,
                $fields['gender'], $fields['biography'], $fields['agreement'],
                $_SESSION['login']
            ]);

            // Обновление языков программирования
            $app_id = $db->query("SELECT application_id FROM user_applications 
                                 WHERE user_id = (SELECT id FROM users WHERE login = '{$_SESSION['login']}')")
                         ->fetchColumn();
            
            $db->prepare("DELETE FROM application_languages WHERE application_id = ?")
               ->execute([$app_id]);
            
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($fields['languages'] as $lang_id) {
                $stmt->execute([$app_id, $lang_id]);
            }
        } else {
            // Создание новой заявки и пользователя
            $db->beginTransaction();

            // Добавление заявки
            $stmt = $db->prepare("INSERT INTO applications 
                (full_name, phone, email, birth_date, gender, biography, agreement) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $fields['full_name'], $fields['phone'], $fields['email'], $birth_date,
                $fields['gender'], $fields['biography'], $fields['agreement']
            ]);
            $app_id = $db->lastInsertId();

            // Добавление языков
            $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($fields['languages'] as $lang_id) {
                $stmt->execute([$app_id, $lang_id]);
            }

            // Создание пользователя
            $login = uniqid('user_');
            $password = bin2hex(random_bytes(8));
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
            $stmt->execute([$login, $pass_hash]);
            $user_id = $db->lastInsertId();

            // Связь пользователя и заявки
            $stmt = $db->prepare("INSERT INTO user_applications (user_id, application_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $app_id]);

            $db->commit();

            // Сохранение данных для отображения
            setcookie('login', $login, time() + 3600);
            setcookie('password', $password, time() + 3600);
            $_SESSION['generated_login'] = $login;
            $_SESSION['generated_password'] = $password;
        }

        setcookie('save', '1', time() + 3600);
        header('Location: ' . url(''));
        exit();
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('DB Error: ' . $e->getMessage());
        die('Произошла ошибка при сохранении данных. Пожалуйста, попробуйте позже.');
    }
}

// Получение списка языков программирования
function getLangs($db) {
    try {
        $stmt = $db->query("SELECT id, name FROM programming_languages");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log('DB Error: ' . $e->getMessage());
        return [];
    }
}
