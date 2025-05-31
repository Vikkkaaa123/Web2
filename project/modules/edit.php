<?php
// modules/edit.php

require_once dirname(__DIR__) . '/scripts/init.php';
require_once dirname(__DIR__) . '/scripts/db.php';
require_once dirname(__DIR__) . '/modules/auth_basic.php'; // <-- добавляем подключение

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

// Если отправлена форма — обрабатываем
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    db_query("UPDATE applications SET full_name = ?, email = ?, phone = ?, gender = ?, biography = ? WHERE id = ?", 
        $_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['gender'], $_POST['biography'], $appId);

    db_query("DELETE FROM application_languages WHERE application_id = ?", $appId);

    if (!empty($_POST['languages'])) {
        $stmt = db_connect()->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($_POST['languages'] as $langId) {
            $stmt->execute([$appId, $langId]);
        }
    }

    header("Location: admin.php");
    exit();
}

// Загружаем шаблон
include dirname(__DIR__) . '/theme/edit_form.tpl.php';
