<?php

function app_template_load($name)
{
    $name = array_shift(explode('.', $name));
    $db = new DB('app_templates', 'at');
    $db->select('at.source');
    $db->where("at.name='?'", $name);
    $result = $db->fetchOne();
    return $result['source'];
}