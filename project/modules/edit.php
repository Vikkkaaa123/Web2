<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../scripts/db.php';

$db = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        echo "Заявка не найдена.";
        exit;
    }

    $stmt = $db->prepare("
        SELECT language_id FROM application_languages WHERE application_id = ?
    ");
    $stmt->execute([$id]);
    $lang_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $db->query("SELECT * FROM programming_languages");
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    include __DIR__ . '/../theme/edit_form.tpl.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $agreement = isset($_POST['agreement']) ? 1 : 0;
    $languages = $_POST['languages'] ?? [];

    $stmt = $db->prepare("
        UPDATE applications
        SET full_name = ?, email = ?, phone = ?, birth_date = ?, gender = ?, biography = ?, agreement = ?
        WHERE id = ?
    ");
    $stmt->execute([$full_name, $email, $phone, $birth_date, $gender, $biography, $agreement, $id]);

    $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
    $stmt->execute([$id]);

    $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($languages as $lang_id) {
        $stmt->execute([$id, $lang_id]);
    }

    header("Location: ../admin");
    exit;
}
