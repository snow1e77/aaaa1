<?php

//Необходимость откладки
$depositing = true;
if ($depositing === true){
    ini_set('error_reporting', E_ERROR);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

//Название приложение
$app_name = 'Архивация почты';

//EMail поддержки приложения
$app_support = 'support@'.$_SERVER['HTTP_HOST'];

//EMail для отправки писем
$app_email_message = 'noreply@'.$_SERVER['HTTP_HOST'];

if (!function_exists('str_random')){
    function str_random($num=30){
        $razreshenniye_simvoli = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($razreshenniye_simvoli), 0, $num);
    }
}

$url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

$urlShort = $_SERVER['REQUEST_URI'];
$urlShort = explode('?', $urlShort);
$urlShort = $urlShort[0];

if (!function_exists('translit')) {
    function translit($value)
    {
        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya', ' ' => '_', '"' => '_',
            "'" => '_', ';' => '_',':' => '_','?' => '_','/' => '_',
            '|' => '_',']' => '_','[' => '_','}' => '_','{' => '_',
            '(' => '_',')' => '_','&' => '_','*' => '_','!' => '_',
            '@' => '_','№' => 'num','#' => 'num',

            'А' => 'a', 'Б' => 'b', 'В' => 'v', 'Г' => 'g', 'Д' => 'd',
            'Е' => 'e', 'Ё' => 'e', 'Ж' => 'zh', 'З' => 'z', 'И' => 'i',
            'Й' => 'y', 'К' => 'k', 'Л' => 'l', 'М' => 'm', 'Н' => 'n',
            'О' => 'o', 'П' => 'p', 'Р' => 'r', 'С' => 's', 'Т' => 't',
            'У' => 'u', 'Ф' => 'f', 'Х' => 'h', 'Ц' => 'c', 'Ч' => 'ch',
            'Ш' => 'sh', 'Щ' => 'sch', 'Ь' => '', 'Ы' => 'y', 'Ъ' => '',
            'Э' => 'e', 'Ю' => 'yu', 'Я' => 'ya',
        );

        $value = strtr($value, $converter);
        return $value;
    }
}

/**
 * Склонение существительных после числительных.
 *
 * @param string $value Значение
 * @param array $words Массив вариантов, например: array('товар', 'товара', 'товаров')
 * @param bool $show Включает значение $value в результирующею строку
 * @return string
 */
if (!function_exists('num_word')) {
    function num_word($value, $words, $show = true)
    {
        $num = $value % 100;
        if ($num > 19) {
            $num = $num % 10;
        }

        $out = ($show) ? $value . ' ' : '';
        switch ($num) {
            case 1:
                $out .= $words[0];
                break;
            case 2:
            case 3:
            case 4:
                $out .= $words[1];
                break;
            default:
                $out .= $words[2];
                break;
        }

        return $out;
    }
}


if (!function_exists('upload_image')){
    function upload_image($file){

        if (!empty($file['name'])) {
            $allowed_files_array_photo = array(
                'image/gif',
                'image/jpeg',
                'image/pjpeg',
                'image/png',
                'image/svg+xml',
                'image/tiff',
                'image/vnd.microsoft.icon',
                'image/vnd.wap.wbmp',
                'image/webp',
                'audio/mpeg',
                'application/zip',
                'audio/mp3',
                'application/octet-stream',
                'application/x-zip-compressed',
                'application/vnd.ms-excel'
            );

            $file_type = $file['type'];

            if( preg_match('<'.implode($allowed_files_array_photo).'>i', $file_type) ) {
                $res = array('status' => 'error', 'text' => 'Неверный формат изображения');
                return $res;
            }

            $folder = date('Ymd');
            if(!is_dir($_SERVER['DOCUMENT_ROOT'].'/upload/'.$folder.'/')){
                mkdir($_SERVER['DOCUMENT_ROOT'].'/upload/'.$folder.'/',0777,true);
            }

            $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/upload/".$folder.'/';
            $name_avatar = str_random();
            $target_file_type = $target_dir . basename($file["name"]);
            $imageFileType = strtolower(pathinfo($target_file_type, PATHINFO_EXTENSION));
            $target_file = $target_dir . $name_avatar . '.' . $imageFileType;
            $avatar_url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/upload/' . $folder . '/' . $name_avatar . '.' . $imageFileType;
            if (!move_uploaded_file($file["tmp_name"], $target_file)) {
                $res = array('status' => 'error', 'text' => 'Не удалось загрузить изображение (tmp_name: '.$file["tmp_name"].', target_file: '.$target_file.', file_exist (folder): '.var_dump(file_exists($_SERVER['DOCUMENT_ROOT'].'/upload/'.$folder.'/')).')');
                return $res;
            }else{
                $res = array('status' => 'success', 'text' => 'Файл успешно загружен', 'url'=> $avatar_url, 'name_file'=>$name_avatar . '.' . $imageFileType, 'folder'=>$folder);
                return $res;
            }
        }else{
            $res = array('status' => 'error', 'text' => 'Файл не передан');
            return $res;
        }

    }
}

if (!function_exists('phone_format')){
    function phone_format($phone)
    {
        $phone = trim($phone);

        $res = preg_replace(
            array(
                '/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{3})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
                '/[\+]?([7|8])[-|\s]?(\d{3})[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
                '/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
                '/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
                '/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{3})/',
                '/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{3})[-|\s]?(\d{3})/',
            ),
            array(
                '+7 ($2) $3 $4-$5',
                '+7 ($2) $3 $4-$5',
                '+7 ($2) $3 $4-$5',
                '+7 ($2) $3 $4-$5',
                '+7 ($2) $3 $4',
                '+7 ($2) $3 $4',
            ),
            $phone
        );

        return $res;
    }
}

if (!function_exists('phone_string')){
    function phone_string($phone)
    {
        $phone = trim($phone);

        $res = preg_replace(
            array(
                '/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{3})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
                '/[\+]?([7|8])[-|\s]?(\d{3})[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
                '/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
                '/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
                '/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{3})/',
                '/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{3})[-|\s]?(\d{3})/',
            ),
            array(
                '+7$2$3$4$5',
                '+7$2$3$4$5',
                '+7$2$3$4$5',
                '+7$2$3$4$5',
                '+7$2$3$4',
                '+7$2$3$4',
            ),
            $phone
        );

        return $res;
    }
}

if (!function_exists('date_ru')) {
    function date_ru($timestamp, $show_time = false)
    {
        if (empty($timestamp)) {
            return '-';
        } else {
            $now = explode(' ', date('Y n j H i'));
            $value = explode(' ', date('Y n j H i', $timestamp));

            if ($now[0] == $value[0] && $now[1] == $value[1] && $now[2] == $value[2]) {
                return 'Сегодня в ' . $value[3] . ':' . $value[4];
            } else {
                $month = array(
                    '', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
                    'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'
                );
                $out = $value[2] . ' ' . $month[$value[1]] . ' ' . $value[0];
                if ($show_time) {
                    $out .= ' в ' . $value[3] . ':' . $value[4];
                }
                return $out;
            }
        }
    }
}

$lettersRu = array(
    'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р',
    'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'
);

$ip = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'];

//Мететеги для получния
$searchItems = ['Authentication-Results', 'Received', 'X-Rcpt-To', 'X-Envelope-From', 'DKIM-Signature', 'Date', 'To', 'From', 'Reply-To', 'Subject', 'Message-ID', 'X-Sender', 'X-Receiver', 'MIME-Version', 'X-Return-Path', 'Content-Type', 'Content-Transfer-Encoding','X-ClientProxiedBy','X-SG-EID','X-Entity-ID'];