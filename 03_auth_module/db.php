<?php
// Подключение к MySQL. Поправьте значения под свой OpenServer, если отличаются
// (стандартные логин/пароль OpenServer для MySQL обычно root / пустой пароль).

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'alpha_hotel');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function getConnection(): mysqli
{
    static $connection = null;
    if ($connection === null) {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $connection->set_charset('utf8mb4');
    }
    return $connection;
}
