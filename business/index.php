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

?>
<!doctype html>
<html lang="ru">
<head>
    <?php
    include $_SERVER['DOCUMENT_ROOT'].'/modules/head.php';
    ?>
    <title><?=$app_name?></title>
</head>
<body>
<div class="container">
    <div class="flex align-center js-c-sb">
        <div class="flex align-center">
            <h1>Юр.лица</h1>
            <a class="link ml-3" href="/">Архив</a>
        </div>
        <div>
            <button class="btn" onclick="location='/business/edit?id=0'">Добавить</button>
        </div>
    </div>

    <table class="mt-3 w-100">
        <tr>
            <th class="w-5 text-left">№</th>
            <th class="w-15 text-left">Название</th>
            <th class="w-15  text-left">ИНН</th>
            <th class="w-15 text-left">ОГРН</th>
            <th class="w-15 text-left">БИК</th>
            <th class="w-15 text-left">Р/с</th>
            <th class="w-10 text-left">Домены</th>
            <th class="w-10 text-left"></th>
        </tr>
        <?php
        $businesses = $db->query("SELECT `id`, `name`, `inn`, `ogrn`, `bik`, `c_s` FROM `business` ORDER BY `id` DESC")->fetch_all();
        foreach ($businesses as $business) {
            $businessId = $business[0];
            $businessName = $business[1];
            $businessInn = $business[2];
            $businessOgrn = $business[3];
            $businessBik = $business[4];
            $businessCs = $business[5];

            $emailsString = '';
            $emails = $db->query("SELECT `email` FROM `business_emails` WHERE `business_id`='$businessId'")->fetch_all();
            foreach ($emails as $email) {
                $email = $email[0];
                if (!empty($emailsString)) {
                    $emailsString .= ', ';
                }

                $emailsString .= $email;
            }
        ?>
        <tr>
            <td><?=$businessId?></td>
            <td><?=$businessName?></td>
            <td><?=$businessInn?></td>
            <td><?=$businessOgrn?></td>
            <td><?=$businessBik?></td>
            <td><?=$businessCs?></td>
            <td><?=$emailsString?></td>
            <td><a href="/business/edit?id=<?=$businessId?>" class="icon-pocta"><i class="fa-solid fa-pen-to-square"></i></a></td>
        </tr>
        <?php
        }
        ?>
    </table>
</div>
<?php
include $_SERVER['DOCUMENT_ROOT'].'/modules/js.php';
?>
</body>
</html>