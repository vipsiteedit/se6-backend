<?php

namespace SE\CMS;

use SE\DB as DB;

class Nav extends Base
{
    protected $tableName = "app_nav";

    public function getAddInfo()
    {
        return array ("items" => $this->getItems());
    }

    public function save()
    {
	$this->input['idApp'] = $this->input['seIdApp'];
	parent::save();
    }

    private function getItems()
    {
        $result = [];
        $u = new DB("app_nav_url", 'anu');
        $u->select("anu.id, anu.id_url, anu.id_parent, ant.name, anu.sort, anu.is_active");
        $u->innerJoin('app_urls au', 'au.id=anu.id_url');
        $u->leftJoin('app_nav_url_translate ant', 'anu.id=ant.id_nav_url AND ant.id_lang='.intval($this->idLang));
        $u->where("id_nav = ?", $this->input["id"]);

        $items = $u->getList();

        foreach ($items as $item) {
            $result[] = $item;
        }
        return $result;
    }

    protected function correctValuesBeforeFetch($items)
    {
        foreach ($items as &$item) {
            $item['args'] = '{"isTree": true}';
        }
        return $items;
    }
}