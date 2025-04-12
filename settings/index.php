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
    <div class="flex align-center">
        <h1>Настройки</h1>
        <a class="link ml-3" href="/">Архив</a>
        <a class="link ml-3" href="/business/">Юр.лица</a>
    </div>
    <form method="post" action="/settings/save">
        <?php
        $items = $db->query("SELECT `code`, `title`, `value` FROM `settings`")->fetch_all();
        foreach ($items as $item) {
            $code = $item[0];
            $title = $item[1];
            $value = $item[2];
        ?>
        <div class="w-100 mb-3">
            <label class="form-label"><?=$title?></label>
            <input class="form-control" name="<?=$code?>" value="<?=$value?>">
        </div>
        <?php
        }
        ?>
        <div class="w-100">
            <button class="knopka_poiska" type="submit">
                Сохранить
            </button>
        </div>
    </form>

</div>
<?php
include $_SERVER['DOCUMENT_ROOT'].'/modules/js.php';
?>

<script>
    $('.js-download-result').on('click',function () {
        var where = $('#where_download').val();
        $.post('/app/getWhereDownload',{where:where},function (res) {
            console.log(res);
            location = res;
        })
    })
</script>

</body>
</html>