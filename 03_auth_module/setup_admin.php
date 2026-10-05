<?php
// Одноразовый скрипт: создаёт первого администратора с правильно захэшированным
// паролем. Откройте этот файл один раз в браузере после создания БД
// (02_create_database.sql), затем можно удалить файл или оставить - повторный
// запуск просто ничего не сделает, если admin уже существует.

require_once __DIR__ . '/db.php';

$db = getConnection();

$check = $db->query("SELECT UserID FROM Users WHERE UserName = 'admin'");
if ($check->num_rows > 0) {
    echo 'Пользователь admin уже существует, ничего не создано.';
    exit;
}

$roleId = $db->query("SELECT RoleID FROM Roles WHERE RoleName = 'Администратор'")->fetch_assoc()['RoleID'];
$hash = password_hash('admin123', PASSWORD_DEFAULT);

$stmt = $db->prepare('INSERT INTO Users (UserName, RoleID, PasswordHash, PasswordChanged) VALUES (?, ?, ?, 0)');
$stmt->bind_param('sis', $login, $roleId, $hash);
$login = 'admin';
$stmt->execute();

echo 'Создан пользователь admin с паролем admin123 (потребуется сменить при первом входе).';
