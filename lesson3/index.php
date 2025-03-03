<?php
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
    if (!empty($_GET['save'])) {
        echo '<p style="color:green;">Спасибо, данные сохранены!</p>';
    }
    include('form.php');
    exit();
}

// Проверяем метод POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<pre>";
    print_r($_POST); // Отладочный вывод для проверки данных
    echo "</pre>";
}

// Получение данных из формы
$fio = trim($_POST['full_name'] ?? '');
$num = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$bdate = $_POST['birth_date'] ?? '';
$biography = trim($_POST['biography'] ?? '');
$gen = $_POST['gender'] ?? '';
$languages = is_array($_POST['languages']) ? $_POST['languages'] : [];
$agreement = isset($_POST['agreement']) && $_POST['agreement'] === 'on' ? 1 : 0;

// Валидация данных
$errors = [];

if (empty($fio) || strlen($fio) > 128 || !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]+$/u', $fio)) {
    $errors[] = 'Некорректное имя.';
}
if (empty($num) || !preg_match('/^\+7\d{10}$/', $num)) {
    $errors[] = 'Некорректный номер телефона.';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Некорректный email.';
}
if (empty($gen) || !in_array($gen, ["male", "female"])) {
    $errors[] = 'Некорректный пол.';
}
if (empty($biography) || strlen($biography) > 512) {
    $errors[] = 'Некорректная биография.';
}
if (empty($bdate)) {
    $errors[] = 'Дата рождения не указана.';
}
if (empty($languages)) {
    $errors[] = 'Не выбран язык программирования.';
}
if (!$agreement) {
    $errors[] = 'Необходимо согласие.';
}

// Если есть ошибки, показываем их и останавливаем выполнение
if ($errors) {
    foreach ($errors as $error) {
        echo "<p style='color:red;'>$error</p>";
    }
    include('form.php');
    exit();
}

// Запись данных в базу
try {
    $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, agreement) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fio, $num, $email, $bdate, $gen, $biography, $agreement]);
    
    // Получаем ID последней вставленной заявки
    $application_id = $db->lastInsertId();

    // Записываем языки программирования в базу
    $stmt_insert = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($languages as $language_id) {
        $stmt_insert->execute([$application_id, $language_id]);
    }

    // Перенаправляем пользователя на страницу с сообщением об успехе
    header('Location: index.php?save=1');
    exit();
} catch (PDOException $e) {
    die('Ошибка сохранения: ' . $e->getMessage());
}
?>
