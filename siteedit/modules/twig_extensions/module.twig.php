<?php

namespace Twig_Extensions;

class Module_Twig_Extension extends \Twig_Extension
{
    protected $placefolders;

    public function getFunctions()
    {

        return array(
            new \Twig_SimpleFunction('module', array($this, 'module'), array('deprecated'=>false))
        );
    }

    public function getName()
    {
        return 'SEdit_Module';
    }

    public function module($alias, $sectionAlias = false, $arguments = array())
    {
        return render_module($alias, $sectionAlias, $arguments);
    }
}