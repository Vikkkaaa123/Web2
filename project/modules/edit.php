<?php
require_once '../scripts/db.php';
require_once '../scripts/init.php';

checkAdminAuth();

$db = db_connect();
$appId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($appId <= 0) {
    die("Неверный ID заявки.");
}

// Получаем все языки
$allLangs = db_all("SELECT id, name FROM programming_languages");

// Получаем заявку
$app = db_row("SELECT * FROM applications WHERE id = ?", $appId);
if (!$app) {
    die("Заявка не найдена.");
}

// Получаем языки этой заявки
$selectedLangs = db_all("SELECT language_id FROM application_languages WHERE application_id = ?", $appId);
$selectedLangs = array_column($selectedLangs, 'language_id');

// Загружаем шаблон
include '../theme/edit_form.tpl.php';
