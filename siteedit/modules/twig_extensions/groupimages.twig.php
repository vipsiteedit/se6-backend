<?php

namespace Twig_Extensions;

class Groupimages_Twig_Extension extends \Twig_Extension
{

    public function getFunctions()
    {

        return array(
            new \Twig_SimpleFunction('groupimages', array($this, 'getGroupImages'), array('deprecated'=>false))
        );
    }

    public function getName()
    {
        return 'SEdit_Groupimages';
    }

    public function getGroupImages($idGroup, $param = array())
    {
        $sect = new \Section('');
        $images = $sect->getGroupsImages($idGroup, $param);
		return $images['items'];
    }
}