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

$id = $strProtected->db($_GET['id']);

$db->query("DELETE FROM `business` WHERE `id`='$id'");

header('Location: /business/');