<?php
// Аварийный сброс: разблокировать пользователя и/или задать ему новый пароль,
// если он сам забыл пароль или заблокировался (3 неверных попытки / неактивность).
// Использование: открыть в браузере с параметрами в адресной строке, например
//   reset_password.php?username=admin&new_password=admin123
// После входа система всё равно попросит сменить этот пароль (PasswordChanged=0).
// Это служебный инструмент для разработки/самопроверки, не часть сдаваемого
// функционала модуля 3 - после экзамена можно удалить файл. 

require_once __DIR__ . '/db.php';

$username = $_GET['username'] ?? '';
$newPassword = $_GET['new_password'] ?? '';

if ($username === '' || $newPassword === '') {
    echo 'Укажите в адресной строке ?username=...&new_password=...';
    exit;
}

$db = getConnection();

$check = $db->prepare('SELECT UserID FROM Users WHERE UserName = ?');
$check->bind_param('s', $username);
$check->execute();
$user = $check->get_result()->fetch_assoc();

if ($user === null) {
    echo 'Пользователь "' . htmlspecialchars($username) . '" не найден.';
    exit;
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $db->prepare(
    'UPDATE Users SET PasswordHash = ?, Blocked = 0, FailedAttempts = 0, PasswordChanged = 0 WHERE UserID = ?'
);
$update->bind_param('si', $hash, $user['UserID']);
$update->execute();

echo 'Пользователю "' . htmlspecialchars($username) . '" установлен новый пароль, блокировка снята. '
    . 'При следующем входе система попросит сменить этот пароль.';
