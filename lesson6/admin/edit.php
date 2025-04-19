<?php
require_once 'db.php';
require_once 'auth.php';

checkAdminAuth();

$appId = (int)$_GET['id'];
if (!$appId) die('Не указан ID заявки');

// Получение данных заявки
$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$appId]);
$application = $stmt->fetch();

if (!$application) die('Заявка не найдена');

// Получение выбранных языков
$stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
$stmt->execute([$appId]);
$selectedLangs = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'full_name' => $_POST['full_name'],
        'phone' => $_POST['phone'],
        'email' => $_POST['email'],
        'birth_date' => $_POST['birth_year'].'-'.$_POST['birth_month'].'-'.$_POST['birth_day'],
        'gender' => $_POST['gender'],
        'biography' => $_POST['biography'],
        'agreement' => isset($_POST['agreement']) ? 1 : 0,
        'id' => $appId
    ];
    
    // Обновление данных
    $stmt = $db->prepare("
        UPDATE applications SET 
        full_name = :full_name, 
        phone = :phone, 
        email = :email, 
        birth_date = :birth_date, 
        gender = :gender, 
        biography = :biography, 
        agreement = :agreement 
        WHERE id = :id
    ");
    $stmt->execute($data);
    
    // Обновление языков
    $db->exec("DELETE FROM application_languages WHERE application_id = $appId");
    if (!empty($_POST['languages'])) {
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($_POST['languages'] as $langId) {
            $stmt->execute([$appId, $langId]);
        }
    }
    
    header('Location: admin.php');
    exit;
}

// Подготовка данных для формы
$birthDate = explode('-', $application['birth_date']);
$values = [
    'full_name' => $application['full_name'],
    'phone' => $application['phone'],
    'email' => $application['email'],
    'birth_day' => $birthDate[2],
    'birth_month' => $birthDate[1],
    'birth_year' => $birthDate[0],
    'gender' => $application['gender'],
    'biography' => $application['biography'],
    'languages' => $selectedLangs,
    'agreement' => $application['agreement']
];

// Получение списка языков
$allowed_lang = $db->query("SELECT id, name FROM programming_languages")->fetchAll(PDO::FETCH_KEY_PAIR);

include 'form.php';
