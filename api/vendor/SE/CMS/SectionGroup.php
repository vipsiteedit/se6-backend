<?php

namespace SE\CMS;

use SE\DB as DB;

class SectionGroup extends TreeList
{
    protected $tableName = "app_section_groups";
    protected $sortOrder = "asc";
    protected $sortBy = "sort";

    protected function getSettingsFetch()
    {

        $result["select"] = '`asg`.id, `asg`.id_parent, `act`.`name`, `asg`.sort';
        $result["joins"][] = array(
            "type" => "left",
            "table" => 'app_section_groups_translate `act`',
            "condition" => '(`asg`.id = `act`.id_group AND `act`.id_lang='.intval($this->idLang).')'
        );

        return $result;
    }

    protected function getSettingsInfo()
    {
        if (empty($this->input['idLang'])) $this->input['idLang'] = intval($this->idLang);
        $result["select"] = '`asg`.id, `asg`.id_parent, `asg`.code, `act`.id AS `id_translate`, `act`.name, `act`.note, `act`.page_title, 
        `act`.meta_title, `act`.meta_description, `act`.meta_keywords, `asg`.is_show, `asg`.is_search';
        $result["joins"][] = array(
            "type" => "left",
            "table" => 'app_section_groups_translate `act`',
            "condition" => '(`asg`.id = `act`.id_group AND `act`.id_lang='.intval($this->input['idLang']).')'
        );
        return $result;
    }

	public function info($id = null)
    {
        $u = new DB($this->tableName);
        $u->addField('is_search','tinyint(1)', 1, 1);
        $u->addField('is_show','tinyint(1)', 1, 1);

        $this->result = parent::info();
        $this->result["nameParent"] = $this->getNameParent();
        $this->result['images'] = $this->getImages();
        $this->result['idLang'] = $this->input['idLang'];
        return $this->result;
    }

    public function getImages($idProduct = null)
    {
        $result = array();
        $id = $idProduct ? $idProduct : $this->input["id"];
        if (!$id)
            return $result;
        $u = new DB('app_section_groups_image', 'si');
        $u->select('si.id, ait.id AS id_translate,  si.id_image, IF(f.name IS NOT NULL, CONCAT(f.name, "/", img.name), img.name) image_path, img.name image_name, si.is_main, ait.alt, ait.title, si.sort');
        $u->leftJoin("app_section_groups_image_translate ait", "si.id = ait.id_image AND ait.id_lang=".intval($this->input['idLang']));

        $u->innerJoin("app_image img", "img.id = si.id_image");
        $u->leftJoin("app_image_folder f", "f.id = img.id_folder");
        $u->where('si.id_group = ?', $id);
        $u->orderBy("si.sort");
        $list = $u->getList();

        foreach ($list as &$item) {
            $item['imageUrlPreview'] = 'http://' . $this->hostname . "/api/CMS/Image/?size=100&img=" . $item['imagePath'];
        }

        return $list;

    }
	
	public function save()
    {
        unset($this->input['_edit_']);
        if (empty($this->input['code']) && $this->input['name']) {
            $this->input['code'] = se_translite_url($this->input['name']);
        }
        while ($this->uniqueUrl($this->tableName, 'code', $this->input['code'], intval($this->input['id']))) {
            $this->input['code'] .= '-';
        }
        return parent::save();
    }

    private function saveTranslate()
    {
        if (empty($this->input['idLang']))
            $this->input['idLang'] = $this->idLang;
        //$this->input['idLang'] = $this->idLang;
        $data = $this->input;

        $data['idGroup'] = $this->input["id"];
        if (!empty($data['idTranslate'])) {
            $data['id'] = $this->input['idTranslate'];
            unset($data['ids']);
        } else {
            unset($data['id']);
            unset($data['ids']);
        }
        if (empty($data['name']) && empty($data['note'])) return true;
        $act = new DB('app_section_groups_translate');
        $act->setValuesFields($data);
        return $act->save();
    }

    protected function saveAddInfo()
    {
        return $this->saveTranslate() && $this->saveListImages();
    }

}
