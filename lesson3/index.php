<?php
// Устанавливаем кодировку
header('Content-Type: text/html; charset=UTF-8');

// Подключение к базе данных
$user = 'u68606'; 
$pass = '9347178'; 
$db = new PDO('mysql:host=localhost;dbname=u68606', $user, $pass, [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Функция для получения списка допустимых языков программирования
function getLangs($db) {
    try {
        $allowed_lang = [];
        $data = $db->query("SELECT id, name FROM programming_languages")->fetchAll();
        foreach ($data as $lang) {
            $id = $lang['id'];
            $name = $lang['name'];
            $allowed_lang[$id] = $name;
        }
        return $allowed_lang;
    } catch (PDOException $e) {
        print('Error: ' . $e->getMessage());
        exit();
    }
}

$allowed_lang = getLangs($db);

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Если есть параметр save, выводим сообщение
    if (!empty($_GET['save'])) {
        print('Спасибо, результаты сохранены.');
    }
    // Подключаем форму
    include('form.php');
    exit();
}

// Если метод POST, выводим данные формы для отладки
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<pre>";
    print_r($_POST); // Вывод данных формы
    echo "</pre>";
    // Не завершаем выполнение, чтобы продолжить обработку
}

// Получаем данные из формы
$fio = $_POST['full_name'];
$num = $_POST['phone'];
$email = $_POST['email'];
$bdate = $_POST['birth_date'];
$biography = $_POST['biography'];
$gen = $_POST['gender'];
$languages = $_POST['languages'] ?? [];
$agreement = isset($_POST['agreement']) ? 1 : 0;

// Инициализируем массив для ошибок
$errors = FALSE;

// Проверка поля "ФИО"
if (empty($fio)) {
    print('Имя не указано.<br/>');
    $errors = TRUE;
} elseif (strlen($fio) > 128) {
    print('Введенное имя указано некорректно. Имя не должно превышать 128 символов.<br/>');
    $errors = TRUE;
} elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]+$/u', $fio)) {
    print('Введенное имя указано некорректно. Имя должно содержать только буквы и пробелы.<br/>');
    $errors = TRUE;
}

// Проверка поля "Телефон"
if (empty($num)) {
    print('Номер не указан.<br/>');
    $errors = TRUE;
} elseif (!preg_match('/^\+7\d{10}$/', $num)) {
    print('Номер указан некорректно.<br/>');
    $errors = TRUE;
}

// Проверка поля "Email"
if (empty($email)) {
    print('Email не указан.<br/>');
    $errors = TRUE;
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    print('Введенный email указан некорректно.<br/>');
    $errors = TRUE;
}

// Проверка поля "Пол"
if (empty($gen)) {
    print('Пол не указан.<br/>');
    $errors = TRUE;
} else {
    $allowed_genders = ["male", "female"];
    if (!in_array($gen, $allowed_genders)) {
        print('Поле "пол" содержит недопустимое значение.<br/>');
        $errors = TRUE;
    }
}

// Проверка поля "Биография"
if (empty($biography)) {
    print('Заполните биографию.<br/>');
    $errors = TRUE;
} elseif (strlen($biography) > 512) {
    print('Количество символов в поле "биография" не должно превышать 512.<br/>');
    $errors = TRUE;
} elseif (!preg_match('/^[а-яА-Яa-zA-Z1-9.,: ]+$/u', $biography)) {
    print('Поле "биография" содержит недопустимые символы.<br/>');
    $errors = TRUE;
}

// Проверка поля "Любимые языки программирования"
if (empty($languages)) {
    print('Укажите любимый(ые) язык(и) программирования.<br/>');
    $errors = TRUE;
} else {
    foreach ($languages as $lang) {
        if (!array_key_exists($lang, $allowed_lang)) {
            print('Указан недопустимый язык (' . $lang . ').<br/>');
            $errors = TRUE;
        }
    }
}

// Проверка поля "Дата рождения"
if (empty($bdate)) {
    print('Дата рождения не указана.<br/>');
    $errors = TRUE;
}

// Проверка поля "Согласие с контрактом"
if (!isset($_POST['agreement'])) {
    print('Подтвердите, что вы ознакомлены с контрактом.<br/>');
    $errors = TRUE;
}

// Если есть ошибки, завершаем выполнение
if ($errors) {
    exit();
}

// Сохранение данных в таблицу applications
try {
    $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, agreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fio, $num, $email, $bdate, $gen, $biography, $agreement]);

    // Получаем ID последней вставленной записи
    $application_id = $db->lastInsertId();
    echo "ID заявки: $application_id<br>"; // Отладочный вывод

    // Сохраняем выбранные языки программирования в таблицу application_languages
    $stmt_insert = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($languages as $language_id) {
        $stmt_insert->execute([$application_id, $language_id]);
        echo "Добавлен язык: " . $allowed_lang[$language_id] . "<br>"; // Отладочный вывод
    }

    // Перенаправляем на страницу с сообщением об успехе
    header('Location: ?save=1');
    exit();
} catch (PDOException $e) {
    print('Ошибка: ' . $e->getMessage());
    exit();
}
?>
