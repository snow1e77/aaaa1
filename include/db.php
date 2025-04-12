<?php
// db.php - настройки подключения к базе данных

header('Content-Type: text/html; charset=utf-8');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Замените на ваше имя пользователя MySQL
define('DB_PASS', '');            // Замените пустую строку на пароль, если он установлен
define('DB_NAME', 'my_database');    // Замените 'database' на имя вашей базы данных

// Создаем объект подключения к базе данных
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Проверка подключения
if ($db->connect_error) {
    die('Database connection error: ' . $db->connect_error);
}
