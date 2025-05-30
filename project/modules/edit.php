<?php
require_once '../scripts/db.php';
require_once '../scripts/init.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo "Некорректный ID";
    exit();
}

$app_id = intval($_GET['id']);

// Получаем заявку
$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$app_id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    http_response_code(404);
    echo "Заявка не найдена";
    exit();
}

// Получаем языки заявки
$stmt = $db->prepare("SELECT language_id FROM application_languages WHERE app_id = ?");
$stmt->execute([$app_id]);
$app_langs = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Получаем список всех языков
$stmt = $db->query("SELECT * FROM programming_languages");
$allowed_lang = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Заполняем значения для формы
$values = [
    'fio' => $app['fio'],
    'email' => $app['email'],
    'phone' => $app['phone'],
    'birth_day' => date('d', strtotime($app['birth_date'])),
    'birth_month' => date('m', strtotime($app['birth_date'])),
    'birth_year' => date('Y', strtotime($app['birth_date'])),
    'gender' => $app['gender'],
    'lang' => implode(",", $app_langs),
    'biography' => $app['biography'],
    'agreement' => true
];

$errors = []; // Если будут ошибки, сюда попадут

include '../theme/edit_form.tpl.php';
