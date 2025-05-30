<?php
require_once __DIR__ . '/../scripts/db.php';
session_start();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo "Неверный ID заявки.";
    exit;
}

// Загружаем заявку
$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    http_response_code(404);
    echo "Заявка не найдена.";
    exit;
}

// Загружаем языки, выбранные пользователем
$stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
$stmt->execute([$id]);
$app['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Загружаем все возможные языки
$stmt = $db->query("SELECT * FROM programming_languages");
$languages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ошибки из сессии, если есть
$errors = $_SESSION['edit_errors'] ?? [];
unset($_SESSION['edit_errors']);

include __DIR__ . '/../theme/edit_form.tpl.php';
