<?php
session_start();
require_once __DIR__ . '/../scripts/db.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo "ID заявки не передан.";
    exit;
}

$id = intval($_GET['id']);

try {
    // Получаем заявку
    $stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        http_response_code(404);
        echo "Заявка не найдена.";
        exit;
    }

    // Получаем выбранные языки программирования
    $stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
    $stmt->execute([$id]);
    $lang_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Получаем все языки для отображения чекбоксов
    $stmt = $db->query("SELECT id, name FROM programming_languages");
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    include __DIR__ . '/../theme/edit_form.tpl.php';

} catch (PDOException $e) {
    http_response_code(500);
    echo "Ошибка при загрузке заявки: " . $e->getMessage();
    exit;
}
