<?php

namespace Twig_Extensions;

class Nav_Twig_Extension extends \Twig_Extension
{
    protected $placefolders;

    public function getFunctions()
    {

        return array(
            new \Twig_SimpleFunction('nav', array($this, 'nav'))
        );
    }

    public function getName()
    {
        return 'SEdit_Nav';
    }

    public function nav($alias, $args = array())
    {
        $nav = new \Nav($alias);

        return $nav->getNav($args);
    }
}