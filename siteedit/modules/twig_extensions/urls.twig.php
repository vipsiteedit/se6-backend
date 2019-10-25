<?php

namespace Twig_Extensions;

use DB;

class Urls_Twig_Extension extends \Twig_Extension
{
    protected $placefolders;

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('url', array($this, 'url'))
        );
    }

    public function getName()
    {
        return 'SEdit_Url';
    }

    public function url($alias, $arguments = array())
    {
        $app_name = false;
        $alias_name = '';
        if (is_array($arguments) && !is_string($arguments)) {
            if (!empty($alias)) {
                $alias_name = $alias;
            }
            if (!empty($arguments['app'])) {
                $app_name = $arguments['app'];
                unset($arguments['app']);
            }
        }
        return get_app_alias_url($alias, $arguments);
    }

}