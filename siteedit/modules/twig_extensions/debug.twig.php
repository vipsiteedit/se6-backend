<?php

namespace Twig_Extensions;

class Debug_Twig_Extension extends \Twig_Extension
{
    protected $placefolders;

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('dump', array($this, 'noFunction'), array('deprecated'=>false))
        );
    }

    public function getName()
    {
        return 'SEdit_NoFunction';
    }

    public function noFunction($var)
    {
        dump($var);
    }
}