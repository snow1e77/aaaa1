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

$file = $db->query("SELECT `url` FROM `filesDownload` ORDER BY `id` DESC")->fetch_assoc()['url'];

header('Content-Description: File Transfer');
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename=' . basename($file));
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($_SERVER['DOCUMENT_ROOT'].$file));