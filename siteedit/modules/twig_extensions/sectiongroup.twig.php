<?php

namespace Twig_Extensions;

class Sectiongroup_Twig_Extension extends \Twig_Extension
{

    public function getFunctions()
    {

        return array(
            new \Twig_SimpleFunction('sectiongroup', array($this, 'getSectiongroup'), array('deprecated'=>false))
        );
    }

    public function getName()
    {
        return 'SEdit_Sectiongroup';
    }

    public function getSectiongroup($id)
    {
		/*$u = new DB('app_section_groups', 'asg');
        $u->select("asg.*");
        $u->where('asg.id=?', $id);
        $item = $u->fetchOne();
        $item['url'] = convert_pattern_url($this->section['patternGroup'], $item);
            $groups[intval($item['idParent'])][] = $item;
        }
        $items = $this->setTree($groups);
        return array('count' => count($items), 'items' => $items);

		
		*/
        $sect = new \Section('');
        return $sect->getSectionGroupItem($id);
    }
}