<?php

function rus2translit($string)
{

    $converter = array(
        'а' => 'a', 'б' => 'b', 'в' => 'v',
        'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'yo', 'ж' => 'zh', 'з' => 'z',
        'и' => 'i', 'й' => 'j', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r',
        'сх' => 'skh', 'цх' => 'ckh', 'ех' => 'ekh',
        'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
        'ь' => "", 'ы' => 'y', 'ъ' => "",
        'э' => 'eh', 'ю' => 'yu', 'я' => 'ya',
        'ö' => 'o', 'Š' => 's', 'á' => 'a', 'ñ' => 'n',
        'ä' => 'a', 'ö' => 'o', 'ü' => 'u',
        'é' => 'e', 'ý' => 'y', 'ú' => 'u', 'í' => 'i',
        'ó' => 'o', 'ç' => 'c', 'š' => 's', 'ī' => 'i',
        'ד' => 'dh', 'צ׳' => 'ch', 'ב' => 'b', 'ג' => 'g',
        'ל' => 'l', 'מ' => 'm', 'נ' => 'n', 'ר' => 'r',
        'ק' => 'q', 'פ' => 'p',
        'А' => 'a', 'Б' => 'b', 'В' => 'v',
        'Г' => 'g', 'Д' => 'd', 'Е' => 'e',
        'Ё' => 'e', 'Ж' => 'zh', 'З' => 'z',
        'И' => 'i', 'Й' => 'j', 'К' => 'k',
        'Л' => 'l', 'М' => 'm', 'Н' => 'n',
        'О' => 'o', 'П' => 'p', 'Р' => 'r',
        'СХ' => 'skh', 'ЦХ' => 'ckh', 'ЕХ' => 'ekh',
        'С' => 's', 'Т' => 't', 'У' => 'u',
        'Ф' => 'f', 'Х' => 'h', 'Ц' => 'c',
        'Ч' => 'ch', 'Ш' => 'sh', 'Щ' => 'shch',
        'Ь' => "", 'Ы' => 'y', 'Ъ' => "",
        'Э' => 'eh', 'Ю' => 'yu', 'Я' => 'ya',
        '«' => '', '»' => '', '"' => '',
        '`' => '', '\'' => '',
    );
    return strtr(utf8_strtolower($string), $converter);
}

function se_translite_url($string)
{
    $string = str_replace(array('№', '%20', ',', '.', '!', '?', '&', '(', ')', '<', '>', '{', '}', ' ', '_', '/', "\\", '[', ']'), '-', $string);
    $string = str_replace(array('+'), array('-plus'), $string);

    $result = preg_replace("/[\s]/", '-', rus2translit($string));
    while (strpos($result, '--') !== false) {
        $result = str_replace('--', '-', $result);
    }
    if (strlen($result)) {
        if (substr($result, 0, 1) == '-') {
            $result = substr($result, 1, strlen($result) - 1);
        }
        if (substr($result, strlen($result) - 1, 1) == '-') {
            $result = substr($result, 0, strlen($result) - 1);
        }
    }
    $result = str_replace('hh', 'hkh', $result);
    return preg_replace('/[^a-zA-Z0-9_-]/i', '', $result);
}