<?php

namespace SE\CMS;

use SE\DB as DB;

class TreeList extends Base
{
    protected function getParentItem($item, $items)
    {
        foreach ($items as $it)
            if ($it["id"] == $item["idParent"])
                return $it;
    }

    protected function getPathName($item, $items)
    {
        if (!$item["idParent"])
            return $item["name"];

        $parent = $this->getParentItem($item, $items);
        if (!$parent)
            return $item["code"];
        return $this->getPathName($parent, $items) . " / " . $item["name"];
    }

    protected function getTreeView($items, $idParent = null)
    {
        $result = array();
        foreach ($items as $item) {
            if ($item["idParent"] == $idParent) {
                $item["childs"] = $this->getTreeView($items, $item["id"]);
                $result[] = $item;
            }
        }
        return $result;
    }

    public function getPatches($items)
    {
        $result = array();
        $search = strtolower($this->input["searchText"]);
        foreach ($items as $item) {
            if (empty($search) || mb_strpos(strtolower($item["name"]), $search) !== false) {
                $item["name"] = $this->getPathName($item, $items);
                $item["level"] = substr_count($item["name"], "/");
                $result[] = $item;
            }
        }
        return $result;
    }

    protected function correctValuesBeforeFetch($items = array())
    {
        if ($this->input["isTree"] && empty($this->input["searchText"]))
            $result = $this->getTreeView($items);
        else $result = $this->getPatches($items);
        return $result;
    }


    public function info($id = null)
    {
        $this->result = parent::info();
        $this->result["nameParent"] = $this->getNameParent();
        return $this->result;
    }

    protected function getNameParent()
    {
        if (!$this->result["idParent"])
            return null;

        $db = new DB($this->tableName, $this->tableAlias);
        $db->select("`{$this->tableAlias}t`.`name`");
        $db->leftJoin("{$this->tableName}_translate `{$this->tableAlias}t`", "`{$this->tableAlias}`.id = `{$this->tableAlias}t`.id_group AND `{$this->tableAlias}t`.id_lang=".intval($this->input['idLang']));
        //echo $db->getSql();
        $result = $db->getInfo($this->result["idParent"]);
        return $result["name"];
    }

    protected function getAddInfo()
    {
        $result["childs"] = $this->getChilds();
        return $result;
    }

    protected function getChilds()
    {
        if ($idParent = $this->input["id"]) {
            $filter = array("field" => "idParent", "sign"=>'IN', "value" => $idParent);
            $category = new SectionGroup(array("filters" => $filter));
            $result = $category->fetch();
            return $result;
        }
        return array();
    }

}
