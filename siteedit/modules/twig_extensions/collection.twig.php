<?php

namespace Twig_Extensions;

class Collection_Twig_Extension extends \Twig_Extension
{

    public function getFunctions()
    {

        return array(
            new \Twig_SimpleFunction('collection', array($this, 'getCollection'), array('deprecated'=>false))
        );
    }

    public function getName()
    {
        return 'SEdit_Collection';
    }

    public function getCollection($ids = array(), $isValues = true)
    {
        $sect = new \Section('');
        return $sect->getCollectionItems($ids, $isValues);
    }
}