<?php

namespace SE\CMS;

use SE\DB as DB;

class NavUrl extends TreeList
{
    protected $tableName = "app_nav_url";

    protected $sortOrder = "asc";

    protected function getSettingsFetch()
    {

        $result["select"] = '`anu`.*, `ant`.id AS id_translate, `ant`.`name`';
        $result["joins"][] = array(
            "type" => "left",
            "table" => 'app_nav_url_translate `ant`',
            "condition" => '(`anu`.id = `ant`.id_nav_url AND `ant`.id_lang='.intval($this->idLang).')'
        );

        return $result;
    }

    protected function getChilds()
    {
        $result = [];
        $u = new DB("app_nav_url", 'anu');
        $u->select("anu.id, anu.id_url, anu.id_parent, ant.name, anu.sort, anu.is_active");
        $u->leftJoin('app_nav_url_translate ant', 'anu.id=ant.id_nav_url AND ant.id_lang='.intval($this->idLang));
        $u->innerJoin('app_urls au', 'au.id=anu.id_url');
        $u->where("id_nav = ?", $this->input["id"]);

        $items = $u->getList();

        foreach ($items as $item) {
            $item['childs'] = $item;
            $result[] = $item;
        }
        return $result;
    }

    public function info($id = null)
    {
        $this->input['idLang'] = intval($this->input['idLang']);
        $u = new DB('app_nav_url', 'anu');
        $u->select('`anu`.*, ant.id AS id_translate, `ant`.name');
        $u->leftJoin('app_nav_url_translate ant', "`anu`.id=`ant`.id_nav_url AND `ant`.id_lang={$this->input['idLang']}");
        $u->where('`anu`.id=?', $this->input['id']);
        $this->result = $u->fetchOne();
        $this->result['idLang'] = $this->input['idLang'];
        return $this->result;
    }

    private function saveTranslate()
    {

        $data = array();
        $data['idNavUrl'] = $this->input["id"];
        if ($this->input['idTranslate']) {
            $data['id'] = $this->input['idTranslate'];
        }
        if (empty($this->input["name"])) return true;
        $data['name'] = $this->input["name"];
        $data['idLang'] = $this->input["idLang"];
        if (empty($data['idLang'])) $data['idLang'] = $this->idLang;
        if ($data['idLang']) {
            $act = new DB('app_nav_url_translate');
            $act->setValuesFields($data);
            return $act->save();
        } else {
            return true;
        }
    }


    protected function saveAddInfo()
    {
        return $this->saveTranslate();
    }
}