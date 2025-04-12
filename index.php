<?php
session_start();

require $_SERVER['DOCUMENT_ROOT'].'/include/config.php';
require $_SERVER['DOCUMENT_ROOT'].'/include/db.php';

// Убедимся, что соединение у нас тоже в utf8mb4 (если не задано внутри db.php)
$db->set_charset('utf8mb4');

include $_SERVER['DOCUMENT_ROOT'].'/include/email.php';
$sendMail = new email();

include $_SERVER['DOCUMENT_ROOT'].'/include/error.php';
$errors = new errors();

include $_SERVER['DOCUMENT_ROOT'].'/include/includes.php';
include $_SERVER['DOCUMENT_ROOT'].'/include/stringProtected.php';
$strProtected = new stringProtected();

include $_SERVER['DOCUMENT_ROOT'].'/include/authCheck.php';

// Считываем из настроек каталог архива
$dirSaveRes = $db->query("SELECT value FROM settings WHERE code='directory_save'");
$directory_save = ($dirSaveRes && $row = $dirSaveRes->fetch_assoc()) ? rtrim($row['value'], '/') : '/archive';

// Фильтры поиска
$nomer         = $_GET['nomer'] ?? '';
$otpravitel    = $_GET['otpravitel'] ?? '';
$polychatel    = $_GET['polychatel'] ?? '';
$tema          = $_GET['tema'] ?? '';
$datestart     = $_GET['datestart'] ?? '';
$datestop      = $_GET['datestop'] ?? '';
$businessSelected = $_GET['business'] ?? '';

// Строим WHERE
$where = "";

// Сколько записей на странице
$countItemsPage = 50;
$currentPage = (int) ($_GET['page'] ?? 1);
if ($currentPage <= 0) { 
    $currentPage = 1; 
}
$offsetQuery = ($currentPage * $countItemsPage) - $countItemsPage;

$filters = '';

// Сортировка
$sorted = $_GET['sorted'] ?? 'id_DESC';
switch ($sorted) {
    case 'id_ASC':           $sort = " ORDER BY id ASC"; break;
    case 'from_DESC':        $sort = " ORDER BY sender DESC"; break;
    case 'from_ASC':         $sort = " ORDER BY sender ASC"; break;
    case 'to_DESC':          $sort = " ORDER BY recipients DESC"; break;
    case 'to_ASC':           $sort = " ORDER BY recipients ASC"; break;
    case 'business_id_DESC': $sort = " ORDER BY business_id DESC"; break;
    case 'business_id_ASC':  $sort = " ORDER BY business_id ASC"; break;
    case 'subject_DESC':     $sort = " ORDER BY subject DESC"; break;
    case 'subject_ASC':      $sort = " ORDER BY subject ASC"; break;
    case 'datetime_ASC':     $sort = " ORDER BY date_sent ASC"; break;
    default:                 $sort = " ORDER BY date_sent DESC"; break;
}

// Фильтр по ID (номер)
if (!empty($nomer)) {
    $where .= "id LIKE '%" . $db->real_escape_string($nomer) . "%'";
    $filters .= '&nomer=' . urlencode($nomer);
}

// Отправитель
if (!empty($otpravitel)) {
    $where .= (empty($where) ? "" : " AND ") . " sender LIKE '%" . $db->real_escape_string($otpravitel) . "%'";
    $filters .= '&otpravitel=' . urlencode($otpravitel);
}

// Получатель
if (!empty($polychatel)) {
    $where .= (empty($where) ? "" : " AND ") . " recipients LIKE '%" . $db->real_escape_string($polychatel) . "%'";
    $filters .= '&polychatel=' . urlencode($polychatel);
}

// Тема
if (!empty($tema)) {
    $where .= (empty($where) ? "" : " AND ") . " subject LIKE '%" . $db->real_escape_string($tema) . "%'";
    $filters .= '&tema=' . urlencode($tema);
}

// Дата начала
if (!empty($datestart)) {
    $where .= (empty($where) ? "" : " AND ") . " date_sent >= '" . $db->real_escape_string($datestart . " 00:00:00") . "'";
    $filters .= '&datestart=' . urlencode($datestart);
}

// Дата конца
if (!empty($datestop)) {
    $where .= (empty($where) ? "" : " AND ") . " date_sent <= '" . $db->real_escape_string($datestop . " 23:59:59") . "'";
    $filters .= '&datestop=' . urlencode($datestop);
}

// Бизнес (юридическое лицо)
if (!empty($businessSelected)) {
    $where .= (empty($where) ? "" : " AND ") . " business_id = '" . $db->real_escape_string($businessSelected) . "'";
    $filters .= '&business=' . urlencode($businessSelected);
}

// Формируем итоговый WHERE
if (!empty($where)) {
    $where = " WHERE " . $where;
}

$filters .= '&sorted=' . urlencode($sorted);

// Лимит и оффсет
$limit = " LIMIT $countItemsPage OFFSET $offsetQuery";

// Подсчитываем общее количество записей
$countItemsResult = $db->query("SELECT id FROM messages $where");
$countItems = $countItemsResult ? $countItemsResult->num_rows : 0;
$totalPagesCount = ceil($countItems / $countItemsPage);

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <!-- Добавляем шрифты, поддерживающие эмодзи -->
    <style>
        body { 
            font-family: "Segoe UI Emoji", "Apple Color Emoji", sans-serif; 
        }
    </style>
    <?php include $_SERVER['DOCUMENT_ROOT'].'/modules/head.php'; ?>
    <title><?=htmlspecialchars($app_name, ENT_QUOTES, 'UTF-8')?></title>
</head>
<body>
<div class="container">
    <div class="flex align-center">
        <h1>Архив</h1>
        <a class="link ml-3" href="/business/">Юр.лица</a>
    </div>

    <!-- Форма фильтрации -->
    <form class="filter" method="get" action="/">
        <input type="hidden" name="page" value="<?=htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8')?>">
        <div class="w-5 filter-item">
            <input type="text" class="form-control" placeholder="номер" name="nomer" 
                   value="<?=htmlspecialchars($nomer, ENT_QUOTES, 'UTF-8')?>">
        </div>
        <div class="w-20 filter-item">
            <input type="text" class="form-control" placeholder="поиск по отправителю" name="otpravitel" 
                   value="<?=htmlspecialchars($otpravitel, ENT_QUOTES, 'UTF-8')?>">
        </div>
        <div class="w-20 filter-item">
            <input type="text" class="form-control" placeholder="поиск по получателю" name="polychatel" 
                   value="<?=htmlspecialchars($polychatel, ENT_QUOTES, 'UTF-8')?>">
        </div>
        <div class="w-15 filter-item">
            <select class="form-control" name="business">
                <option value="">Все</option>
                <?php
                // Список бизнесов
                $businessResult = $db->query("SELECT `id`, `name` FROM `business`");
                while ($row = $businessResult->fetch_assoc()) {
                    $businessItemId = $row['id'];
                    $businessItemName = $row['name'];
                    $selected = ($businessItemId == $businessSelected) ? ' selected' : '';
                    echo "<option value=\"" . htmlspecialchars($businessItemId, ENT_QUOTES, 'UTF-8') . "\"$selected>"
                       . htmlspecialchars($businessItemName, ENT_QUOTES, 'UTF-8')
                       . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="w-15 filter-item">
            <input type="text" class="form-control" placeholder="тема" name="tema" 
                   value="<?=htmlspecialchars($tema, ENT_QUOTES, 'UTF-8')?>">
        </div>
        <div class="w-15 filter-item">
            <input type="date" class="form-control" name="datestart" 
                   value="<?=htmlspecialchars($datestart, ENT_QUOTES, 'UTF-8')?>">
            <input type="date" class="form-control" name="datestop" 
                   value="<?=htmlspecialchars($datestop, ENT_QUOTES, 'UTF-8')?>">
        </div>
        <div class="w-10 filter-item">
            <button class="knopka_poiska" type="submit">Поиск</button>
        </div>
    </form>

    <!-- Таблица писем -->
    <table class="mt-3 w-100" id="table-messages">
        <thead>
        <tr>
            <th class="w-5 text-left c-pointer" 
                onclick="location='/?page=<?=htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8').$filters?>&sorted=id_<?php echo ($sorted !== 'id_DESC') ? 'DESC' : 'ASC'; ?>'">
                №<?php 
                    echo ($sorted=='id_DESC') ? '<i class="fa-solid fa-chevron-down"></i>' 
                         : (($sorted=='id_ASC') ? '<i class="fa-solid fa-chevron-up"></i>' : ''); 
                ?>
            </th>
            <th class="w-20 text-left c-pointer" 
                onclick="location='/?page=<?=htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8').$filters?>&sorted=from_<?php echo ($sorted !== 'from_DESC') ? 'DESC' : 'ASC'; ?>'">
                Отправитель<?php 
                    echo ($sorted=='from_DESC') ? '<i class="fa-solid fa-chevron-down"></i>' 
                         : (($sorted=='from_ASC') ? '<i class="fa-solid fa-chevron-up"></i>' : ''); 
                ?>
            </th>
            <th class="w-20 text-left c-pointer" 
                onclick="location='/?page=<?=htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8').$filters?>&sorted=to_<?php echo ($sorted !== 'to_DESC') ? 'DESC' : 'ASC'; ?>'">
                Получатель<?php 
                    echo ($sorted=='to_DESC') ? '<i class="fa-solid fa-chevron-down"></i>' 
                         : (($sorted=='to_ASC') ? '<i class="fa-solid fa-chevron-up"></i>' : ''); 
                ?>
            </th>
            <th class="w-15 text-left c-pointer" 
                onclick="location='/?page=<?=htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8').$filters?>&sorted=datetime_<?php echo ($sorted !== 'datetime_DESC') ? 'DESC' : 'ASC'; ?>'">
                Дата и время<?php
                    echo ($sorted=='datetime_DESC') ? '<i class="fa-solid fa-chevron-down"></i>' 
                         : (($sorted=='datetime_ASC') ? '<i class="fa-solid fa-chevron-up"></i>' : ''); 
                ?>
            </th>
            <th class="w-10 text-left">Таймзона</th>
            <th class="w-15 text-left c-pointer" 
                onclick="location='/?page=<?=htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8').$filters?>&sorted=subject_<?php echo ($sorted !== 'subject_DESC') ? 'DESC' : 'ASC'; ?>'">
                Тема<?php 
                    echo ($sorted=='subject_DESC') ? '<i class="fa-solid fa-chevron-down"></i>' 
                         : (($sorted=='subject_ASC') ? '<i class="fa-solid fa-chevron-up"></i>' : ''); 
                ?>
            </th>
            <th class="w-10 text-left"></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $messagesQuery = "SELECT `id`, `sender`, `recipients`, `date_sent`, `timezone`, 
                                 `subject`, `file_path`, `datetime_create`, `business_id`
                          FROM `messages` 
                          $where 
                          $sort 
                          $limit";
        $messagesRes = $db->query($messagesQuery);

        $count = 0;
        if ($messagesRes) {
            while ($message = $messagesRes->fetch_row()) {
                $count++;
                $message_id         = htmlspecialchars($message[0], ENT_QUOTES, 'UTF-8');
                $message_sender     = htmlspecialchars($message[1], ENT_QUOTES, 'UTF-8');
                $message_recipients = htmlspecialchars($message[2], ENT_QUOTES, 'UTF-8');
                $message_date_sent  = htmlspecialchars($message[3], ENT_QUOTES, 'UTF-8');
                $message_timezone   = htmlspecialchars($message[4], ENT_QUOTES, 'UTF-8');
                // Применяем htmlspecialchars также к теме
                $message_subject    = htmlspecialchars($message[5], ENT_QUOTES, 'UTF-8');
                $message_file       = htmlspecialchars($message[6], ENT_QUOTES, 'UTF-8');
                $message_business_id= $message[8];

                // Выводим название бизнеса (если нужно)
                $businessRes = $db->query("SELECT `name` FROM `business` WHERE `id`='$message_business_id'");
                $message_business_name = ($businessRes && $businessRes->num_rows > 0) 
                                         ? htmlspecialchars($businessRes->fetch_assoc()['name'], ENT_QUOTES, 'UTF-8') 
                                         : '';

                // Форматируем дату
                $formattedDate = (new DateTime($message_date_sent))->format('d.m.Y H:i:s');

                // Формируем URL для скачивания: добавляем настройку directory_save как префикс к относительному пути file_path
                $downloadURL = rtrim($directory_save, '/') . '/' . $message_file;
                ?>
                <tr class="js-filter-item">
                    <td class="w-5"><?=$message_id?></td>
                    <td class="w-20"><code><?=$message_sender?></code></td>
                    <td class="w-20"><?=$message_recipients?></td>
                    <td class="w-15"><?=$formattedDate?></td>
                    <td class="w-10"><?=$message_timezone?></td>
                    <td class="w-15"><?=$message_subject?></td>
                    <td class="w-10">
                        <a href="<?=$downloadURL?>" class="icon-pocta"><i class="fa-regular fa-envelope"></i></a>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
        </tbody>
    </table>

    <div class="mt-3">
        <div class="flex align-center">
            <?php
            // Пагинация
            if ($currentPage !== 1) {
                echo '<a class="link-pagination" href="/?page='.($currentPage-1).$filters.'"><</a>';
            }
            if ($currentPage != 1) {
                echo '<a class="link-pagination" href="/?page=1'.$filters.'">1</a>';
            }
            echo '<a class="link-pagination" href="/?page='.$currentPage.$filters.'">'.$currentPage.'</a>';

            for ($i = 1; $i <= 5; $i++) {
                if ($currentPage + $i <= $totalPagesCount) {
                    echo '<a class="link-pagination" href="/?page='.($currentPage+$i).$filters.'">'.($currentPage+$i).'</a>';
                }
            }
            if ($currentPage + 5 < $totalPagesCount) {
                echo '<a class="link-pagination" href="/?page='.$totalPagesCount.$filters.'">'.$totalPagesCount.'</a>';
            }
            if ($totalPagesCount > $currentPage) {
                echo '<a class="link-pagination" href="/?page='.($currentPage+1).$filters.'">></a>';
            }
            ?>
        </div>
    </div>
    <div class="mt-3 mb-3">
        Всего: <?=$count?>
    </div>

    <input type="hidden" id="where_download" value="<?=htmlspecialchars($where, ENT_QUOTES, 'UTF-8')?>">

    <div>
        <button class="link js-download-result">Скачать результат</button>
    </div>

</div>

<?php include $_SERVER['DOCUMENT_ROOT'].'/modules/js.php'; ?>

<script>
    // Пример "Скачать результат" (заготовка для выгрузки)
    $('.js-download-result').on('click', function () {
        var where = $('#where_download').val();
        $.post('/app/getWhereDownload', {where: where}, function (res) {
            location = res; // например, прямая ссылка на файл
        });
    });
</script>

</body>
</html>
