<?php

session_start();

class errors {
    public function new_error($color, $text,$redirect_url = '',$none_redirect = false) {
        setcookie('error_color',$color,time()+1,'/');
        setcookie('error_text',$text,time()+1,'/');

        if ($none_redirect !== true){
            if (empty($redirect_url) && !empty($_SERVER['HTTP_REFERER'])){
                header("Location: ".$_SERVER['HTTP_REFERER']);
            }elseif (!empty($redirect_url)){
                header("Location: ".$redirect_url);
            }else{
                header("Location: /");
            }
            die;
        }
    }


    public function view() {
        $error_color = $_COOKIE['error_color'];
        $error_text = $_COOKIE['error_text'];

        if (!empty($error_color) && $error_color !== ' ' && !empty($error_text) && $error_text !== ' '){
            $data_error = '
                <div class="alert alert-'.$error_color.' mb-3"> '.$error_text.'</div>
            ';
            setcookie('error_color','',time()+1,'/');
            setcookie('error_text','',time()+1,'/');
            return $data_error;
        }
    }
}