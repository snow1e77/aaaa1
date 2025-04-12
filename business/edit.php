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

if ($id != 0 && !empty($id)) {
    $data = $db->query("SELECT `name`, `inn`, `ogrn`, `bik`, `c_s` FROM `business` WHERE `id`='$id'")->fetch_assoc();
    $name = $data['name'];
    if (empty($name)) {
        header("Location: /business/");
        die;
    }
    $inn = $data['inn'];
    $ogrn = $data['ogrn'];
    $bik = $data['bik'];
    $c_s = $data['c_s'];
    $emails = $db->query("SELECT `email` FROM `business_emails` WHERE `business_id`='$id'")->fetch_all();
    $emailString = '';
    foreach ($emails as $emailItem) {
        $emailItem = $emailItem[0];
        if (!empty($emailString)) {
            $emailString .= ',';
        }
        $emailString .= $emailItem;
    }
}

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
    <div class="flex align-center">
        <h1>Юр.лица</h1>
        <a class="link ml-3" href="/">Архив</a>
    </div>

    <form method="post" action="/business/save">
        <input type="hidden" name="id" value="<?=$id?>">
        <?php
        if ($id != 0 && !empty($id)) {
        ?>
        <div class="mb-3">
            <a class="link" href="javascript:" onclick="if(confirm('Подтвердите удаление')){location='/business/delete?id=<?=$id?>'}">Удалить</a>
        </div>
        <?php
        }
        ?>
        <div class="mb-3">
        <!-- гандон -->
            <label class="form-label">Название</label>
            <input class="form-control" name="name" required value="<?=$name?>">
        </div>
        <div class="mb-3">
            <label class="form-label">ИНН</label>
            <input class="form-control" name="inn" value="<?=$inn?>">
        </div>
        <div class="mb-3">
            <label class="form-label">ОГРН</label>
            <input class="form-control" name="ogrn" value="<?=$ogrn?>">
        </div>
        <div class="mb-3">
            <label class="form-label">БИК</label>
            <input class="form-control" name="bik" value="<?=$bik?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Р/C</label>
            <input class="form-control" name="c_s" value="<?=$c_s?>">
        </div>
         <div class="mb-3">
             <label class="form-label">Доменные адреса, принадлежащие юр.лицу (черезе запятую)</label>
             <input class="form-control" name="emails" value="<?=$emailString?>">
         </div>
        <div class="mb-3">
            <button class="btn" type="submit">Сохранить</button>
        </div>
    </form>
</div>
<?php
include $_SERVER['DOCUMENT_ROOT'].'/modules/js.php';
?>
</body>
</html>