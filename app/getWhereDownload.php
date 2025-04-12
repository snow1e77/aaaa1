<?php

session_start();

/*
 * Подключение базовых модулей
 */

require $_SERVER['DOCUMENT_ROOT'].'/include/config.php';
require $_SERVER['DOCUMENT_ROOT'].'/include/db.php';
include $_SERVER['DOCUMENT_ROOT'].'/include/email.php';
$sendMail = new email();
include $_SERVER['DOCUMENT_ROOT'].'/include/error.php';
$errors = new errors();
include $_SERVER['DOCUMENT_ROOT'].'/include/includes.php';
include $_SERVER['DOCUMENT_ROOT'].'/include/stringProtected.php';
$strProtected = new stringProtected();
include $_SERVER['DOCUMENT_ROOT'].'/include/authCheck.php';

/*
 * Завершение подключения базовых модулей
 */

$where = $_POST['where'];

$textRes = '';

$messages = $db->query("SELECT `url` FROM `messages` $where")->fetch_all();
foreach ($messages as $message) {
    $messageUrl = $message[0];
    if (!empty($textRes)) {
        $textRes .= '
';
    }
    $textRes .= $messageUrl;
}

$titleFile = str_random().'.txt';

$filename = $_SERVER['DOCUMENT_ROOT'] . '/uploads/'.$titleFile;

file_put_contents($filename, $textRes);

$db->query("INSERT INTO `filesDownload`(`url`) VALUES ('/uploads/$titleFile')");

die('/uploads/'.$titleFile);