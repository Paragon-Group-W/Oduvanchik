<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}
if ((int)$_SESSION['RoleID'] !== 1) {
    http_response_code(403);
    die('Доступ только для роли "Администратор".');
}

$db = getConnection();
$errorMessage = '';
$successMessage = '';

// Снятие блокировки / разблокировка пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unblock') {
    $targetId = (int)$_POST['user_id'];
    $stmt = $db->prepare('UPDATE Users SET Blocked = 0, FailedAttempts = 0 WHERE UserID = ?');
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $successMessage = 'Пользователь разблокирован.';
}

// Добавление нового пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_user') {
    $newLogin = trim($_POST['new_username'] ?? '');
    $newRole = (int)($_POST['new_role'] ?? 0);
    $newPassword = $_POST['new_user_password'] ?? '';

    if ($newLogin === '' || $newPassword === '' || $newRole === 0) {
        $errorMessage = 'Все поля для нового пользователя обязательны для заполнения.';
    } else {
        $check = $db->prepare('SELECT UserID FROM Users WHERE UserName = ?');
        $check->bind_param('s', $newLogin);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errorMessage = 'Пользователь с таким логином уже существует.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $insert = $db->prepare(
                'INSERT INTO Users (UserName, RoleID, PasswordHash, PasswordChanged) VALUES (?, ?, ?, 0)'
            );
            $insert->bind_param('sis', $newLogin, $newRole, $hash);
            $insert->execute();
            $successMessage = 'Пользователь "' . htmlspecialchars($newLogin) . '" создан. При первом входе ему нужно будет сменить пароль.';
        }
    }
}

$roles = $db->query('SELECT RoleID, RoleName FROM Roles ORDER BY RoleID');
$users = $db->query(
    'SELECT u.UserID, u.UserName, r.RoleName, u.Blocked, u.LastLoginDate, u.FailedAttempts
     FROM Users u JOIN Roles r ON r.RoleID = u.RoleID
     ORDER BY u.UserID'
);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Alpha Hotel - Управление пользователями</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="card wide-card">
    <h1>Управление пользователями</h1>
    <p><a href="dashboard.php">&larr; Назад</a></p>

    <?php if ($errorMessage !== ''): ?>
        <div class="message-error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>
    <?php if ($successMessage !== ''): ?>
        <div class="message-success"><?= $successMessage /* уже экранировано там, где нужно */ ?></div>
    <?php endif; ?>

    <table>
        <tr>
            <th>Логин</th>
            <th>Роль</th>
            <th>Статус</th>
            <th>Последний вход</th>
            <th>Действие</th>
        </tr>
        <?php while ($row = $users->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['UserName']) ?></td>
                <td><?= htmlspecialchars($row['RoleName']) ?></td>
                <td><?= $row['Blocked'] ? 'Заблокирован (' . $row['FailedAttempts'] . ' неудачных попыток)' : 'Активен' ?></td>
                <td><?= htmlspecialchars($row['LastLoginDate'] ?? 'ещё не входил') ?></td>
                <td>
                    <?php if ($row['Blocked']): ?>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="action" value="unblock">
                            <input type="hidden" name="user_id" value="<?= (int)$row['UserID'] ?>">
                            <button type="submit">Разблокировать</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h1 style="margin-top:28px;">Добавить пользователя</h1>
    <form method="post">
        <input type="hidden" name="action" value="add_user">

        <label for="new_username">Логин</label>
        <input type="text" id="new_username" name="new_username" required>

        <label for="new_user_password">Начальный пароль</label>
        <input type="text" id="new_user_password" name="new_user_password" required>

        <label for="new_role">Роль</label>
        <select id="new_role" name="new_role" required
                style="width:100%; padding:8px; margin-bottom:14px; border-radius:4px; border:1px solid #ccc;">
            <option value="">-- выберите роль --</option>
            <?php foreach ($roles as $role): ?>
                <option value="<?= (int)$role['RoleID'] ?>"><?= htmlspecialchars($role['RoleName']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Создать пользователя</button>
    </form>
</div>
</body>
</html>
