<?php


class email
{

    public $urlBasic = '';

    public function __construct()
    {
        include $_SERVER['DOCUMENT_ROOT'].'/include/config.php';
        $this->urlBasic = $url;
    }

    public function new_message($to,$subject,$text,$button_title='Перейти в личный кабинет',$button_url=''){
        session_start();

        require $_SERVER['DOCUMENT_ROOT'].'/include/db.php';
        require $_SERVER['DOCUMENT_ROOT'].'/include/authCheck.php';
        require $_SERVER['DOCUMENT_ROOT'].'/include/config.php';

        if (empty($button_url)){
            $button_url = $this->urlBasic;
        }

        $headers  = "From: " . strip_tags($app_email_message) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $message = '<b>'.$subject.'</b><br><p>'.$text.'</p><br><a href="'.$button_url.'">'.$button_title.'</a>';

        return mail($to, $subject, $message, $headers);
    }
}