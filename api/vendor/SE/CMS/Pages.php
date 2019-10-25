<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class Pages extends Base
{
    protected $tableName = "app_pages";

    protected function getSettingsFetch()
    {
        $this->idLang = intval($this->idLang);
        $joins = array();
        $result["select"] = 'ap.*, apt.title, au.pattern, group_concat(DISTINCT `apm`.`name`) AS `access`';
        $joins[] = array(
            "type" => "inner",
            "table" => 'app_urls au',
            "condition" => "ap.id = au.id_page AND au.id_section IS NULL AND au.id_app='{$this->input['seIdApp']}'"
        );
        $joins[] = array(
            "type" => "left",
            "table" => 'app_pages_translate apt',
            "condition" => "(ap.id = apt.id_page AND `apt`.id_lang={$this->idLang})"
        );
        $joins[] = array(
            "type" => "left",
            "table" => 'app_page_permission app',
            "condition" => "ap.id = app.id_page"
        );
        $joins[] = array(
            "type" => "left",
            "table" => 'app_permission apm',
            "condition" => "apm.id = app.id_permission"
        );


        $result["joins"] = $joins;
        return $result;
    }

    public function save() {
        return parent::save();
    }

    protected function getSettingsInfo()
    {
        if (empty($this->input['idLang'])) $this->input['idLang'] = intval($this->idLang);
        $result["select"] = '`ap`.*, `apt`.title, `apt`.id AS `id_translate`, `apt`.page_title, 
        `apt`.meta_title, `apt`.meta_description, `apt`.meta_keywords, `ap`.is_show, `ap`.is_search';
        $result["joins"][] = array(
            "type" => "left",
            "table" => 'app_pages_translate `apt`',
            "condition" => '(`ap`.id = `apt`.id_page AND `apt`.id_lang='.intval($this->input['idLang']).')'
        );
        return $result;
    }

    public function info($id = null)
    {
        $u = new DB($this->tableName);
        $u->addField('is_search','tinyint(1)', 1, 1);
        $u->addField('is_show','tinyint(1)', 1, 1);

        $this->result = parent::info($id);
        $this->result['permissions'] = $this->getPermission();
        $this->result['pattern'] = $this->getPattern();
        $this->result['url'] = $this->result['pattern']['pattern'];
        $this->result["customFields"] = $this->getCustomFields();
        $this->result['idLang'] = $this->input['idLang'];
        return $this->result;
    }

    private function getPattern()
    {
        $u = new DB('app_urls', 'au');
        $u->where('id_page=?', $this->result['id']);
        $u->andWhere('id_section IS NULL');
        return $u->fetchOne();
    }

    private function getCustomFields()
    {
        $idPage = $this->input["id"];
        try {
            $u = new DB('app_fields', 'su');
            $u->select("cu.id, cu.id_page, `cut`.id AS id_translate, cut.value, su.id id_field, su.min, su.max, su.defvalue,
                      su.name, su.required, su.enabled, su.type, su.placeholder, su.description, su.values, sug.id id_group, sug.name name_group");
            $u->innerJoin('app_fields_service afs', 'su.id_service=afs.id');
            $u->leftJoin('app_pages_values cu', "cu.id_field = su.id AND cu.id_page='{$idPage}'");
            $u->leftJoin('app_pages_values_translate cut', "cut.id_values = cu.id AND cut.id_lang={$this->input['idLang']}");
            $u->leftJoin('app_fieldsgroup sug', 'su.id_group = sug.id');
            $u->where("afs.sect='?'", 'page');
            //$u->where('asl.id=?', $idCollection);
            $u->groupBy('su.id');
            $u->orderBy('sug.sort');
            $u->addOrderBy('su.sort');
            //echo $u->getSql();
            $result = $u->getList();

            $groups = array();
            foreach ($result as $item) {
                $groups[intval($item["idGroup"])]["id"] = $item["idGroup"];
                $groups[intval($item["idGroup"])]["name"] = empty($item["nameGroup"]) ? "Без категории" : $item["nameGroup"];
                if ($item['type'] == "date")
                    $item['value'] = date('Y-m-d', strtotime($item['value']));


                $groups[intval($item["idGroup"])]["items"][] = $item;
            }
            $grlist = array();
            foreach ($groups as $id => $gr) {
                $grlist[] = $gr;
            }
            return $grlist;
        } catch (Exception $e) {
            return array();
        }
    }

    private function savePattern()
    {
        // if (!isset($this->input['url'])) return true;

        try {
            $data = $this->input['pattern'];
            $data['pattern'] = $this->input['url'];
            if (empty($data['alias'])) $data['alias'] = 'a' . $this->input['name'];
            if (empty($data['pattern'])) {
                if ($this->input['name'] == 'index') {
                    $t = new DB('app_urls');
                    $t->where('pattern="?"', '/');
                    if (!$t->fetchOne()){
                        $this->input['name'] = '';
                    }
                }
                $data['pattern'] = '/' . $this->input['name'];
            }
            $data['idPage'] = $this->input['id'];
            $data['template'] = $this->input['template'];
            //$data['idApp'] =  $this->input['idApp'];
            $u = new DB('app_urls');
            $u->setValuesFields($data);
            return $u->save();
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить шаблон адреса!";
            throw new Exception($this->error);
        }

    }

    private function getPermission()
    {
        $u = new DB('app_page_permission', 'app');
        $u->select('ap.id, ap.name');
        $u->innerJoin('app_permission ap', 'app.id_permission=ap.id');
        $u->where('app.id_page = ?', $this->result['id']);
        return $u->getList();

    }

    private function savePermission()
    {
        try {
            $ids = $this->input["ids"];
            $permissions = $this->input['permissions'];
            if (!isset($permissions)) return true;
            $idsExists = array();
            foreach ($permissions as $p)
                if ($p["id"])
                    $idsExists[] = $p["id"];

            //$idsExists = array_diff($idsExists, $ids);
            $idsExistsStr = implode(",", $idsExists);
            $idsStr = implode(",", $ids);
            $u = new DB('app_page_permission', 'app');
            if ($idsExistsStr)
                $u->where("((NOT id_permission IN ({$idsExistsStr})) AND id_page IN (?))", $idsStr)->deleteList();
            else $u->where('id_page IN (?)', $idsStr)->deleteList();

            $idsExists = array();
            if ($idsExistsStr) {
                $u->select("id_page, id_permission");
                $u->where("((id_permission IN ({$idsExistsStr})) AND id_page IN (?))", $idsStr);
                $objects = $u->getList();
                foreach ($objects as $item) {
                    $idsExists[] = $item["idPermission"];
                }
            };
            $data = array();
            foreach ($permissions as $p)
                if (empty($idsExists) || !in_array($p["id"], $idsExists))
                    foreach ($ids as $idPage)
                        $data[] = array('id_page' => $idPage, 'id_permission' => $p["id"]);
            if (!empty($data))
                DB::insertList('app_page_permission', $data);
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить права доступа!";
            throw new Exception($this->error);
        }
    }

    private function saveTranslate()
    {
        $data = $this->input;
        $data['idPage'] = $this->input["id"];
        if ($data['idTranslate']) {
            $data['id'] = $this->input['idTranslate'];
            unset($data['ids']);
        } else {
            unset($data['id']);
            unset($data['ids']);
        }
        if (empty($data['idLang'])) $data['idLang'] = $this->idLang;
        if ($data['idLang']) {
            $act = new DB('app_pages_translate');
            $act->setValuesFields($data);
            return $act->save();
        } else {
            return true;
        }
    }

    private function saveCustomFields()
    {
        if (!isset($this->input["customFields"]) && !$this->input["customFields"])
            return true;

        try {
            $idPage = $this->input["id"];
            $groups = $this->input["customFields"];
            $customFields = array();
            foreach ($groups as $group)
                foreach ($group["items"] as $item)
                    $customFields[] = $item;
            foreach ($customFields as $field) {
                if (!in_array($field['type'], array('file', 'image'))) {
                    $field["idPage"] = $idPage;
                    $u = new DB('app_pages_values', 'cu');
                    $u->setValuesFields($field);
                    $id = $u->save();

                    if (empty($field['id'])) $field['id'] = $id;
                    $value = array('id' => $field['idTranslate'], 'idValues' => $field['id'], 'idLang' => $this->input['idLang'], 'value' => $field['value']);
                    if ($this->input['idLang']) {
                        $u = new DB('app_pages_values_translate', 'cu');
                        $u->setValuesFields($value);
                        $u->save();
                    }

                }

            }
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить доп. информацию!";
            throw new Exception($this->error);
        }
    }

    protected function saveAddInfo()
    {
        return $this->saveCustomFields() && $this->saveTranslate() && $this->savePermission() && $this->savePattern();
    }
}
