<?php

namespace Twig_Extensions;

class Collectionimages_Twig_Extension extends \Twig_Extension
{

    public function getFunctions()
    {

        return array(
            new \Twig_SimpleFunction('collectionimages', array($this, 'getCollectionImages'), array('deprecated'=>false))
        );
    }

    public function getName()
    {
        return 'SEdit_Collectionimages';
    }

    public function getCollectionImages($idCollection, $param = array())
    {
        $sect = new \Section('');
        $images = $sect->getCollectionImages($idCollection, $param);
		return $images['items'];
    }
}