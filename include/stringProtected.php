<?php


class stringProtected
{

    public function db($string){
        str_replace('"','\"',$string);

        require $_SERVER['DOCUMENT_ROOT'].'/include/db.php';
        $string = $db->real_escape_string($string);

        return $string;
    }

    public function view($string){
        $string = htmlspecialchars($string);

        return $string;
    }
}