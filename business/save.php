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

$id = $strProtected->db($_POST['id']);

$name = $strProtected->db($_POST['name']);

if (empty($name)) {
    header("Location: /business/");
    die;
}

$inn = $strProtected->db($_POST['inn']);
$ogrn = $strProtected->db($_POST['ogrn']);
$bik = $strProtected->db($_POST['bik']);
$c_s = $strProtected->db($_POST['c_s']);
$emails = $strProtected->db($_POST['emails']);

$emails = explode(',', $emails);
if ($id == 0) {
    $db->query("INSERT INTO `business`(`name`, `inn`, `ogrn`, `bik`, `c_s`) VALUES ('$name','$inn','$ogrn','$bik','$c_s')");
    $id= $db->insert_id;
} else {
    $db->query("UPDATE `business` SET `name`='$name',`inn`='$inn',`ogrn`='$ogrn',`bik`='$bik',`c_s`='$c_s' WHERE `id`='$id'");
}
$db->query("DELETE FROM `business_emails` WHERE `business_id`= '$id'");
foreach ($emails as $email) {
    $db->query("INSERT INTO `business_emails`(`business_id`, `email`) VALUES ('$id','$email')");
}

header('Location: /business/');