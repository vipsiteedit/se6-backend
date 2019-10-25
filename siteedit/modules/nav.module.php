<?php

class Nav
{
    private $alias;

    private $app;
    private $idLang = 0;

    public function __construct($alias = '')
    {
        $this->alias = $alias;
        $this->app = App::get();
        $this->idLang = intval($_SESSION['SE_APP_LANG']) ? intval($_SESSION['SE_APP_LANG']) : 0;
    }

    protected function getTree($items, $idParent = null)
    {
        $result = array();
        foreach ($items as $item) {
            if ($item["idParent"] == $idParent) {
                $item["items"] = $this->getTree($items, $item["id"]);
                $result[] = $item;
            }
        }
        return $result;
    }

    private function getParentItem($item, $items)
    {
        foreach ($items as $it)
            if ($it["id"] == $item["idParent"])
                return $it;
    }

    private function getPathName($item, $items)
    {
        if (!$item["idParent"])
            return $item["name"];

        $parent = $this->getParentItem($item, $items);
        if (!$parent)
            return null;
        return $this->getPathName($parent, $items) . " / " . $item["name"];
    }

    protected function getPatches($items)
    {
        $result = array();
        foreach ($items as $item) {
            if ($name = $this->getPathName($item, $items)) {
                $item["name"] = $name;
                $item["level"] = substr_count($item["name"], "/");
                $result[] = $item;
            }
        }
        return $result;
    }

    public function getNav($args = array())
    {
        $nav = array();

        $isTree = isset($args['isTree']) ? (bool)$args['isTree'] : true;

        $u = new DB('app_nav', 'an');
        $u->select('anu.*, aut.name, au.alias, (CASE WHEN (au.type = \'item\' AND anu.id_collection) THEN CONCAT_WS("", `asc`.id, "~~", `asc`.code, "~~", `asc`.url) WHEN (au.type = \'group\' AND anu.id_group) THEN CONCAT_WS("", asg.id, "~~", asg.code, "~~") ELSE NULL END) AS args');
        $u->innerJoin('app_nav_url anu', 'anu.id_nav=an.id');
        $u->leftJoin('app_nav_url_translate aut', "anu.id=aut.id_nav_url AND aut.id_lang={$this->idLang}");
        $u->leftJoin('app_urls au', 'anu.id_url=au.id');
        $u->leftJoin('app_section_collection `asc`', 'anu.id_collection=`asc`.id');
        $u->leftJoin('app_section_groups asg', 'anu.id_group=asg.id');
        $u->where('an.id_app=?', $this->app['idApp']);
        $u->andWhere('an.code = "?"', $this->alias);
        $u->andWhere('anu.is_active');
        $u->orderBy('anu.sort', 0);

        if ($items = $u->getList()) {
            foreach ($items as &$item) {
                $args = array();
                if ($item['args']) {
                    list($args['id'], $args['code'], $args['url']) = explode('~~', $item['args']);
                }
                $item['url'] = get_app_alias_url($item['alias'], $args);
            }

            if ($isTree) {
                $nav = $this->getTree($items);
            }
            else {
                $nav = $this->getPatches($items);
            }
        }

        return $nav;
    }
}