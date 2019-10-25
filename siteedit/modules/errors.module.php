<?php


function go404()
{
    header('HTTP/1.0 404 File not found');
    if (!$url = getUrlAppAlias('error404')) {
        print preg_replace("/[\"](images|skin)\//", '"http://e-stile.ru/$1/', file_get_contents("http://e-stile.ru/404.html"));
    } else {
        print file_get_contents('http://' . $_SERVER['HTTP_HOST'] . seMultiDir() . '/404/');
    }
    exit;
}