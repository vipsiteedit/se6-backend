<?php

function dump($what, $exit = false, $color = '')
{
    echo "<pre style='color:{$color}'>";
    print_r($what);
    echo '</pre>';

    if($exit) {
        exit;
    }
}