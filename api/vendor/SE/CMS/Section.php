<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class Section extends Base
{
    protected $tableName = "app_section";


    public function fetch($isId = false)
    {
        $u = new DB($this->tableName, 'as');
        $cnrec = "(SELECT COUNT(*) FROM app_section_collection WHERE id_section=`as`.id) AS rec_count";
        $gnrec = "(SELECT COUNT(*) FROM app_section_groups WHERE id_section=`as`.id) AS group_count";

        $u->select("`as`.id, `as`.id_app, `as`.alias, `as`.name, `as`.visible, 
        `as`.seo_enable, `as`.sort, $cnrec, $gnrec");
        $u->groupBy('`as`.id');
        $u->orderBy('sort');
        $u->where('id_app=?', intval($this->input['seIdApp']));
		$items = array();
        $its = array();
        foreach ($u->getList() as $item) {
            $its[intval($item['idParent'])][] = $item;
        }
        foreach ($its[0] as $item) {
            if (!empty($its[$item['id']])) {
                $its[$item['id']][] = $item;
                $item['items'] = $its[$item['id']];
            }
            $items[] = $item;
        }
        $this->result['items'] = $items;
        return $this->result;
    }

    public function info($id = null)
    {
        $u = new DB($this->tableName);
        $u->addField('is_search_default','tinyint(1)', 1, 1);
        $u->addField('is_show_default','tinyint(1)', 1, 1);

        $this->input['idLang'] = intval($this->input['idLang']);
        $u = new DB('app_section', 'as');
        $u->select('`as`.id, ast.id AS id_translate, `as`.id_app, `as`.alias, `as`.name, `as`.visible, 
            `as`.seo_enable, `as`.sort, ast.title, ast.description, `as`.is_show_default, `as`.is_search_default');
        $u->leftJoin('app_section_translate ast', "`as`.id=`ast`.id_section AND `ast`.id_lang={$this->input['idLang']}");
        //$u->leftJoin('app_pages ap', '`as`.id_page=`ap`.id');
        //$u->leftJoin('app_pages_translate apt', '`ap`.id=`apt`.id_page AND `ast`.id_lang=apt.id_lang');
        $u->where('`as`.id=?', $this->input['id']);
        $this->result = $u->fetchOne();
        $this->result['pattern'] = $this->getPattern('item');
        $this->result['patternGroup'] = $this->getPattern('group');
        $this->result['url'] = $this->result['pattern']['pattern'];
        $this->result['itemplate'] = $this->result['pattern']['template'];
        $this->result['urlGroup'] = $this->result['patternGroup']['pattern'];
        $this->result['gtemplate'] = $this->result['patternGroup']['template'];
        $this->result['permissions'] = $this->getPermission();
        $this->result['fields']['image-size'] = array('label' => 'Размер картинки', 'type' => 'text', 'default' => '800', 'list' => array('label' => '', 'value' => '', 'default' => ''));
        $this->result['fields']['image-prev-size'] = array('label' => 'Размер превью', 'type' => 'text', 'default' => '370x255', 'list' => array('label' => '', 'value' => '', 'default' => ''));
        $this->result['params'] = $this->getParams();
        $this->result['idLang'] = $this->input['idLang'];
        return $this->result;
    }

    private function getPermission()
    {
        $u = new DB('app_section_permission', 'asp');
        $u->select('ap.id, ap.name');
        $u->innerJoin('app_permission ap', 'asp.id_permission=ap.id');
        $u->where('asp.id_section = ?', $this->result['id']);
        return $u->getList();

    }

    private function getPattern($type = 'item')
    {
        $u = new DB('app_urls', 'au');
        $u->where("type='?'", $type);
        $u->andWhere('id_section=?', $this->result['id']);
        return $u->fetchOne();
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
            $u = new DB('app_section_permission', 'app');
            if ($idsExistsStr)
                $u->where("((NOT id_permission IN ({$idsExistsStr})) AND id_section IN (?))", $idsStr)->deleteList();
            else $u->where('id_section IN (?)', $idsStr)->deleteList();

            $idsExists = array();
            if ($idsExistsStr) {
                $u->select("id_section, id_permission");
                $u->where("((id_permission IN ({$idsExistsStr})) AND id_section IN (?))", $idsStr);
                $objects = $u->getList();
                foreach ($objects as $item) {
                    $idsExists[] = $item["idPermission"];
                }
            };
            $data = array();
            foreach ($permissions as $p)
                if (empty($idsExists) || !in_array($p["id"], $idsExists))
                    foreach ($ids as $idSection)
                        $data[] = array('id_section' => $idSection, 'id_permission' => $p["id"]);
            if (!empty($data))
                DB::insertList('app_section_permission', $data);
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить права доступа!";
            throw new Exception($this->error);
        }
    }

    private function getParamsDb()
    {
        if (!$this->input['id']) return array();
        $u = new DB('app_section_parametrs', 'asp');
        $u->select('asp.id, asp.field, asp.value');
        $u->where('id_section=?', $this->input['id']);
        return $u->getList();
    }

    private function getParams()
    {
        if (!$this->input['id']) return array();
        $list = $this->getParamsDb();
        $result = array();
        foreach ($list as $item) {
            $result[$item['field']] = $item['value'];
        }
        if (empty($result['image-prev-size'])) {
            $result['image-prev-size'] = '250x250';
        }
        if (empty($result['image-size'])) {
            $result['image-size'] = '800';
        }
        return $result;
    }

    private function savePattern($type = 'item')
    {
        try {
            if ($type == 'item') {
                $data = $this->input['pattern'];
                $data['pattern'] = $this->input['url'];

                if (empty($data['pattern'])) {
                    $u = new DB('app_urls');
                    $u->where('id_section=?', $this->input['id']);
                    $u->andWhere('type="item"');
                    $u->deleteList();
                    return true;
                }

                if (empty($data['alias'])) $data['alias'] = 's' . $this->input['alias'];
                if (!empty($this->input['itemplate']))
                    $data['template'] = $this->input['itemplate'];
            } else {
                $data = $this->input['patternGroup'];
                $data['pattern'] = $this->input['urlGroup'];
                if (empty($data['pattern'])) {
                    $u = new DB('app_urls');
                    $u->where('id_section=?', $this->input['id']);
                    $u->andWhere('type="group"');
                    $u->deleteList();
                    return true;
                }
                if (empty($data['alias'])) $data['alias'] = 'g' . $this->input['alias'];
                if (!empty($this->input['gtemplate']))
                    $data['template'] = $this->input['gtemplate'];
            }

            $data['idSection'] = $this->input['id'];
            $data['type'] = $type;
            $data['idPage'] = $this->input['idPage'] ? $this->input['idPage'] : null;
            $data['idApp'] = $this->input['idApp'];

            $u = new DB('app_urls');
            $u->setValuesFields($data);

            return $u->save();

        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить шаблон адреса!" . $e->getMessage();
            throw new Exception($this->error);
        }

    }

    private function saveTranslate()
    {

        $data = array();
        $data['idSection'] = $this->input["id"];
        if ($this->input['idTranslate']) {
            $data['id'] = $this->input['idTranslate'];
        }
        $data['title'] = $this->input["title"];
        $data['description'] = $this->input["description"];
        $data['idLang'] = $this->input["idLang"];
        if (empty($data['idLang']))
            $data['idLang'] = $this->idLang;
        if ($data['idLang']) {
            $act = new DB('app_section_translate');
            $act->setValuesFields($data);
            return $act->save();
        } else {
            return true;
        }
    }


    private function saveParams()
    {
        try {
            if (!$this->input['id']) return false;
            if (empty($this->input['params'])) return true;
            $params = $this->getParamsDb();
            $datas = array();
            foreach ($this->input['params'] as $name => $prm) {
                $id = false;
                foreach ($params as $old) {
                    if ($old['field'] == $name) {
                        $id = $old['id'];
                    }
                }
                if ($id) {
                    $datas[] = array('id' => $id, 'idSection' => $this->input['id'], 'field' => $name, 'value' => $prm);
                } else {
                    $datas[] = array('idSection' => $this->input['id'], 'field' => $name, 'value' => $prm);
                }
            }
            $u = new DB('app_section_parametrs');
            foreach ($datas as $data) {
                $u->setValuesFields($data);
                $u->save();
            }
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить параметры!";
            throw new Exception($this->error);
        }
    }
	
	public function save()
	{
		$this->input['idApp'] = $this->input['seIdApp'];
		$result = parent::save();
        $this->input['idCollection'] = $this->result['id'];
        $this->saveTranslate();
        return $result;
	}

    protected function saveAddInfo()
    {
        return $this->saveTranslate() && $this->savePermission() && $this->savePattern('item') && $this->savePattern('group') && $this->saveParams();
    }
}
