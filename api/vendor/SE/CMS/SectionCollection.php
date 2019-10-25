<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class SectionCollection extends Base
{
    protected $tableName = "app_section_collection";

    private function convertFields($str)
    {
        //$str = str_replace('id ', '`asc`.id ', $str);
        $str = preg_replace('#(?<!\w)id(?!\w)#u', '`asc`.id', $str);
        $str = str_replace('idSection', '`asc`.id_section', $str);
        //$str = str_replace('newsDate', 'n.news_date', $str);
        $str = str_replace('nameGroup', '`ag`.name', $str);
        $str = str_replace('idGroup', '`ag`.id_group', $str);
        $str = str_replace('image', '`asc`.id', $str);
        $str = str_replace('name', '`asc`.title', $str);
        $str = str_replace('display', '`asc`.title', $str);

        //$str = str_replace('localize', 'ng.id', $str);


        return $str;
    }


    public function fetch($isId = false)
    {
        try {
            $items = array();
            $u = new DB($this->tableName, 'asc');
            $u->select('as.alias, 
                `asc`.id, `asc`.code, `asc`.is_date_public, `asc`.date_public, `act`.name, `act`.note,
                GROUP_CONCAT(DISTINCT `ag`.name) `groups`, GROUP_CONCAT(`agc`.name) AS `localize`, 
                CONCAT(IFNULL(CONCAT(f.name, "/"), \'\'), img.name) image');
            $u->innerJoin('app_section `as`', '`as`.id=`asc`.id_section');
            $u->leftJoin('app_section_collection_translate `act`', "`act`.id_collection=`asc`.id AND `act`.id_lang='{$this->idLang}'");

            $u->leftJoin('app_section_collection_group `asg`', '`asg`.id_collection=`asc`.id');
            $u->leftJoin('app_section_groups_translate `ag`', "`asg`.id_group=`ag`.id_group AND `ag`.id_lang='{$this->idLang}'");

            $u->leftJoin('app_section_collection_values `ascv`', '`asc`.id=`ascv`.id_collection');

            $u->leftJoin('app_section_collection_region `ascg`', '`asc`.id = `ascg`.id_collection');
            $u->leftJoin('app_regions `agc`', '`agc`.id = `ascg`.id_region');
            $u->leftJoin('app_section_collection_image `asci`', '`asc`.id = `asci`.id_collection AND asci.is_main=1');
            $u->leftJoin('app_image `img`', '`asci`.id_image = `img`.id');
            $u->leftJoin('app_image_folder `f`', '`img`.id_folder = `f`.id');

            $filter = array();
            if (!empty($this->input["filters"])) {
                foreach ($this->input["filters"] as $f) {
                    $filter[] = '(' . $this->convertFields($f['field']) . ' ' . $f['sign'] . ' (' . $f['value'] . '))';
                }
            }

            if (!empty($this->input["searchText"])) {
                $s = trim($this->input["searchText"]);
                $filter[] = "(`ascv`.value LIKE '%{$s}%' OR `asc`.title LIKE '%{$s}%')";
            }
            //$filter[] = '`asc`.id_section=' . $this->input['sectionId'];

            if (!empty($filter))
                $where = implode(' AND ', $filter);

            if (!empty($where))
                $u->where($where);
            $u->groupBy('asc.id');

            //$sortBy = $this->convertFields($this->sortBy);
            //if ($sortBy)
            $u->orderBy('`asc`.sort', false);

            $count = $u->getListCount();

            $objects = $u->getList($this->limit, $this->offset);

            foreach ($objects as $item) {
                $item['isActive'] = $item['active'] == 'Y';
                $item['title'] = utf8_substr(strip_tags($item['title']), 0, 50);
                $item['shortDescription'] = utf8_substr(strip_tags($item['note']), 0, 60);

                if (!empty($item['datePublic'])) {
                    $item['publicationDate'] = $item['datePublic'];
                    //$new['publicationDateDisplay'] = date('d.m.Y', $item['pubDate']);
                }
                if ($item['image']) {
                    if (strpos($item['image'], "://") === false) {
                        $item['imageUrlPreview'] = 'http://' . $this->hostname . "/api/CMS/Image/?size=100&img=" . $item['image'];
                    } else {
                        $item['imageUrlPreview'] = $item['image'];
                    }
                }
                $items[] = $item;
            }

            $this->result['count'] = $count;
            $this->result['items'] = $items;

        } catch (Exception $e) {
            $this->error = "Не удаётся получить список записей!";
        }

        return $this;
    }


    public function getImages($idProduct = null)
    {
        $result = array();
        $id = $idProduct ? $idProduct : $this->input["id"];
        if (!$id)
            return $result;
        $u = new DB('app_section_collection_image', 'si');
        $u->select('si.id, ait.id AS id_translate, si.id_image, IF(f.name IS NOT NULL, CONCAT(f.name, "/", img.name), img.name) image_path,
        img.name image_name, si.is_main, ait.alt, ait.title, si.sort');
        $u->leftJoin("app_section_collection_image_translate ait", "si.id = ait.id_image AND ait.id_lang=" . intval($this->input['idLang']));
        $u->innerJoin("app_image img", "img.id = si.id_image");
        $u->leftJoin("app_image_folder f", "f.id = img.id_folder");
        $u->where('si.id_collection = ?', $id);
        $u->orderBy("si.sort");
        $list = $u->getList();

        foreach ($list as &$item) {
            $item['imageUrlPreview'] = 'http://' . $this->hostname . "/api/CMS/Image/?size=100&img=" . $item['imagePath'];
        }

        return $list;

    }

    /*protected function getSettingsFetch()
    {

        $result["select"] = '`asc`.id, `asc`.id, `au`.`name` AS `unit_name`, COUNT(asc.id) AS rec_count';
        $result["joins"][] = array(
            "type" => "left",
            "table" => 'app_units `au`',
            "condition" => '`au`.id = `as`.id_unit'
        );
        $result["joins"][] = array(
            "type" => "left",
            "table" => 'app_section_collection `asc`',
            "condition" => '`as`.id = `asc`.id_section'
        );
        $result["joins"][] = array(
            "type" => "left",
            "table" => 'app_section_collection_translate `act`',
            "condition" => '(`asc`.id = `act`.id_collection) AND (`act`.id_lang IS NULL OR `act`.id_lang='.$this->input['idLang'].')'
        );

        return $result;
    }*/

    protected function getSettingsInfo()
    {
        if (empty($this->input['idLang'])) $this->input['idLang'] = intval($this->idLang);
        $result["select"] = '`asc`.id, `asc`.id_section, `asc`.code,  `asc`.url, `asc`.visible,`asc`.is_date_public, 
        `asc`.date_public, `act`.id AS `id_translate`, `act`.name, `act`.note, `act`.page_title, `act`.meta_title, 
        `act`.meta_description, `act`.meta_keywords, `asc`.is_show, `asc`.is_search';
        $result["joins"][] = array(
            "type" => "left",
            "table" => 'app_section_collection_translate `act`',
            "condition" => '(`asc`.id = `act`.id_collection) AND (`act`.id_lang IS NULL OR `act`.id_lang=' . intval($this->input['idLang']) . ')'
        );
        return $result;
    }

    public function info($id = null)
    {
        $u = new DB($this->tableName);
        $u->addField('is_search', 'tinyint(1)', 1, 1);
        $u->addField('is_show', 'tinyint(1)', 1, 1);

        $this->result = parent::info($id);
        $this->result['images'] = $this->getImages();
        $this->result['permissions'] = $this->getPermission();
        $this->result['categories'] = $this->getCategories();
        $this->result['similars'] = $this->getSimilars();
        $this->result["customFields"] = $this->getCustomFields();
        $this->result["files"] = $this->getFiles($this->result["id"]);
        $this->result['idLang'] = $this->input['idLang'];
        return $this->result;
    }

    public function getFiles($idCollection = null)
    {
        $result = array();
        if (!$idCollection)
            return $result;

        $u = new DB('app_section_collection_files', 'si');
        $u->where('si.id_collection = ?', $idCollection);
        // $u->andwhere('si.id_field = ?', $idCollection);
        $u->orderBy("sort");
        $objects = $u->getList();

        foreach ($objects as $item) {
            $file = null;
            $file['id'] = $item['id'];
            if (strpos($item['file'], '://') === false)
                $file['fileUrl'] = "http://{$this->hostname}/" . $item['file'];
            else $file['fileUrl'] = $item['file'];
            //$file['fileUrl'] = "http://{$this->hostname}/" . $item['file'];
            $file['fileText'] = $item['name'];
            $file['fileName'] = basename($item['file']);
            $file['fileExt'] = strtoupper(substr(strrchr($item['file'], '.'), 1));
            $file['icon'] = $item['icon'];
            $imgurl = "www/{$this->apps}/" . $file['icon'];
            $file['iconUrlPreview'] = ($item['icon']) ? "http://{$this->hostname}/lib/image.php?size=100&img={$imgurl}" : "";
            $file['description'] = $item['description'];
            $file['sort'] = $item['sort'];
            //$file['sortIndex'] = $item['sort'];
            $result[] = $file;
        }
        return $result;
    }

    private function getPermission()
    {
        $u = new DB('app_section_collection_permission', 'asp');
        $u->select('ap.id, ap.name');
        $u->innerJoin('app_permission ap', 'asp.id_permission=ap.id');
        $u->where('asp.id_collection = ?', intval($this->result['id']));
        return $u->getList();

    }

    private function getCategories()
    {
        try {
            $groups = new SectionGroup(array('limit' => '10000', 'offest' => 0, 'seIdApp' => $this->input['seIdApp']));
            $groups->initPath();
            $groupList = $groups->fetch();

            foreach ($groupList as $key => $val) {
                $groupList[$val['id']] = $val;
                unset($groupList[$key]);
            }

            $u = new DB('app_section_collection_group', 'ascg');
            $u->select('ascg.id AS idTable, asg.id, asgt.name, ascg.is_main');
            $u->innerJoin('app_section_groups asg', 'ascg.id_group=asg.id');
            $u->leftJoin('app_section_groups_translate asgt', 'asgt.id_group=asg.id AND asgt.id_lang=' . $this->input['idLang']);
            $u->where('ascg.id_collection = ?', $this->result['id']);
            $list = $u->getList();

            foreach ($list as $key => $val) {
                $list[$key]['path'] = $groupList[$val['id']]['name'];
            }
            return $list;
        } catch (Exception $e) {
            return array();
        }
    }

    private function getSimilars()
    {
        try {
            $u = new DB('app_section_collection_similar', 'ascg');
            $u->select("asg.id, CONCAT_WS('/', `aps`.`name`, `asct`.`name`) AS `name`");
            $u->innerJoin('app_section_collection asg', 'ascg.id_item=asg.id');
            $u->leftJoin('app_section_collection_translate asct', 'asct.id_collection=asg.id AND asct.id_lang=' . $this->input['idLang']);
            $u->innerJoin('app_section `aps`', 'asg.id_section=`aps`.id');
            $u->where('ascg.id_collection = ?', $this->result['id']);
            return $u->getList();
        } catch (Exception $e) {
            return array();
        }
    }


    private function getCustomFields()
    {
        $idCollection = $this->input["id"];
        try {
            $u = new DB('app_fields', 'su');
            $u->select("cu.id, cu.id_collection, `cut`.id AS id_translate, cut.value, su.id id_field, su.min, su.max, su.defvalue,
                      su.name, su.required, su.enabled, su.type, su.placeholder, su.description, su.values, sug.id id_group, sug.name name_group");
            $u->innerJoin('app_fields_service afs', 'su.id_service=afs.id');
            $u->innerJoin('app_section_collection asl', 'afs.id_section=asl.id_section');
            $u->leftJoin('app_section_collection_values cu', "cu.id_field = su.id AND cu.id_collection=asl.id");
            $u->leftJoin('app_section_collection_values_translate cut', "cut.id_values = cu.id AND cut.id_lang={$this->input['idLang']}");
            $u->leftJoin('app_fieldsgroup sug', 'su.id_group = sug.id');
            $u->where('asl.id=?', $idCollection);
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

    public function save()
    {
        unset($this->input['_edit_']);
        if (empty($this->input['code']) && $this->input['name']) {
            $this->input['code'] = se_translite_url($this->input['name']);
        }
        while ($this->uniqueUrl($this->tableName, 'code', $this->input['code'], intval($this->input['id']))) {
            $this->input['code'] .= '-';
        }
        $this->input['code'] = trim($this->input['code']);
        return parent::save();
    }

    private function saveTranslate()
    {

        $data = $this->input;

        $data['idCollection'] = $this->input["id"];
        if (empty($data['idLang']))
            $data['idLang'] = $this->idLang;
        if ($data['idTranslate']) {
            $data['id'] = $this->input['idTranslate'];
            unset($data['ids']);
        } else {
            unset($data['id']);
            unset($data['ids']);
        }
        if ($data['idLang']) {
            $act = new DB('app_section_collection_translate');
            $act->setValuesFields($data);
            return $act->save();
        } else return true;
    }

    private function saveCategory()
    {
        try {
            $ids = $this->input["ids"];
            $categories = $this->input['categories'];
            if (!isset($categories)) return true;
            $idsExists = array();
            $isMain = false;

            foreach ($categories as $key => $p) {
                if ($p["id"])
                    $idsExists[] = $p["id"];
                if ($isMain) {
                    $categories[$key]['isMain'] = false;
                }
                if ($categories[$key]['isMain']) {
                    $isMain = true;
                }
            }

            if (!$isMain && !empty($categories)) {
                $categories[0]['isMain'] = true;
            }

            //$idsExists = array_diff($idsExists, $ids);
            $idsExistsStr = implode(",", $idsExists);
            $idsStr = implode(",", $ids);
            $u = new DB('app_section_collection_group');
            if ($idsExistsStr)
                $u->where("((NOT id_group IN ({$idsExistsStr})) AND id_collection IN (?))", $idsStr)->deleteList();
            else $u->where('id_collection IN (?)', $idsStr)->deleteList();

            $idsExists = array();
            if ($idsExistsStr) {
                $u->select("id_collection, id_group");
                $u->where("((id_group IN ({$idsExistsStr})) AND id_collection IN (?))", $idsStr);
                $objects = $u->getList();
                foreach ($objects as $item) {
                    $idsExists[] = $item["idGroup"];
                }
            };
            $data = array();
            foreach ($categories as $p) {
                if (empty($idsExists) || !in_array($p["id"], $idsExists))
                    foreach ($ids as $idSection)
                        $data[] = array('id_collection' => $idSection, 'id_group' => $p["id"], 'is_main' => (int)$p["isMain"]);
                else {
                    foreach ($ids as $idSection) {
                        $dataUpdate = array('id' => $p['idTable'], 'id_collection' => $idSection, 'id_group' => $p["id"], 'is_main' => (int)$p["isMain"]);
                        $u = new DB('app_section_collection_group');
                        $u->setValuesFields($dataUpdate);
                        $u->save();
                    }
                }
            }
            if (!empty($data))
                DB::insertList('app_section_collection_group', $data);
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить категории!";
            throw new Exception($this->error);
        }
    }


    private function saveSimilars()
    {
        try {
            $ids = $this->input["ids"];
            $similars = $this->input['similars'];
            if (!isset($similars)) return true;
            $idsExists = array();
            foreach ($similars as $p)
                if ($p["id"])
                    $idsExists[] = $p["id"];

            //$idsExists = array_diff($idsExists, $ids);
            $idsExistsStr = implode(",", $idsExists);
            $idsStr = implode(",", $ids);
            $u = new DB('app_section_collection_similar');
            if ($idsExistsStr)
                $u->where("((NOT id_item IN ({$idsExistsStr})) AND id_collection IN (?))", $idsStr)->deleteList();
            else $u->where('id_collection IN (?)', $idsStr)->deleteList();

            $idsExists = array();
            if ($idsExistsStr) {
                $u->select("id_collection, id_item");
                $u->where("((id_item IN ({$idsExistsStr})) AND id_collection IN (?))", $idsStr);
                $objects = $u->getList();
                foreach ($objects as $item) {
                    $idsExists[] = $item["idItem"];
                }
            };
            $data = array();
            foreach ($similars as $p)
                if (empty($idsExists) || !in_array($p["id"], $idsExists))
                    foreach ($ids as $idSection)
                        $data[] = array('id_collection' => $idSection, 'id_item' => $p["id"]);
            if (!empty($data))
                DB::insertList('app_section_collection_similar', $data);
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить связи!";
            throw new Exception($this->error);
        }
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
            $u = new DB('app_section_collection_permission', 'app');
            if ($idsExistsStr)
                $u->where("((NOT id_permission IN ({$idsExistsStr})) AND id_collection IN (?))", $idsStr)->deleteList();
            else $u->where('id_collection IN (?)', $idsStr)->deleteList();

            $idsExists = array();
            if ($idsExistsStr) {
                $u->select("id_collection, id_permission");
                $u->where("((id_permission IN ({$idsExistsStr})) AND id_collection IN (?))", $idsStr);
                $objects = $u->getList();
                foreach ($objects as $item) {
                    $idsExists[] = $item["idPermission"];
                }
            };
            $data = array();
            foreach ($permissions as $p)
                if (empty($idsExists) || !in_array($p["id"], $idsExists))
                    foreach ($ids as $idSection)
                        $data[] = array('id_collection' => $idSection, 'id_permission' => $p["id"]);
            if (!empty($data))
                DB::insertList('app_section_collection_permission', $data);
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить права доступа!";
            throw new Exception($this->error);
        }
    }

    private function saveCustomFields()
    {
        if (!isset($this->input["customFields"]) && !$this->input["customFields"])
            return true;

        try {
            $idCollection = $this->input["id"];
            $groups = $this->input["customFields"];
            $customFields = array();
            foreach ($groups as $group)
                foreach ($group["items"] as $item)
                    $customFields[] = $item;
            foreach ($customFields as $field) {
                if (!in_array($field['type'], array('file', 'image'))) {
                    $field["idCollection"] = $idCollection;
                    $u = new DB('app_section_collection_values', 'cu');
                    $u->setValuesFields($field);
                    $id = $u->save();

                    if (empty($field['id'])) $field['id'] = $id;
                    $value = array('id' => $field['idTranslate'], 'idValues' => $field['id'], 'idLang' => $this->input['idLang'], 'value' => $field['value']);
                    if ($this->input['idLang']) {
                        $u = new DB('app_section_collection_values_translate', 'cu');
                        $u->setValuesFields($value);
                        $u->save();
                    }

                }

            }
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить доп. информацию о товаре!";
            throw new Exception($this->error);
        }
    }

    private function saveFiles()
    {
        $files = $this->input['files'];
        $idsCollection = $this->input["ids"];
        if (!isset($files))
            return true;

        try {
            if ($this->isNew) {
                foreach ($files as &$file)
                    unset($file["id"]);
                unset($file);
            }
            // обновление изображений
            $idsStore = "";

            foreach ($files as $file) {
                if ($file["id"] > 0) {
                    if (!empty($idsStore))
                        $idsStore .= ",";
                    $idsStore .= $file["id"];
                    $u = new DB('app_section_collection_files', 'si');
                    $file["file"] = str_replace("http://{$this->hostname}/", '', $file["fileUrl"]);
                    //$file["sort"] = $file["sortIndex"];
                    $file["name"] = $file["fileText"];
                    $u->setValuesFields($file);
                    $u->save();
                }
            }
            $idsStr = implode(",", $idsCollection);
            if (!empty($idsStore)) {
                $u = new DB('app_section_collection_files', 'si');
                $u->where("id_collection IN ($idsStr) AND NOT (id IN (?))", $idsStore)->deleteList();
            } else {
                $u = new DB('app_section_collection_files', 'si');
                $u->where('id_collection IN (?)', $idsStr)->deleteList();
            }

            $data = array();
            foreach ($files as $file)
                foreach ($idsCollection as $idCollection) {
                    if (empty($file["id"]) || ($file["id"] <= 0)) {
                        $data[] = array(
                            'id_collection' => $idCollection,
                            'file' => str_replace("http://{$this->hostname}/", '', $file["fileUrl"]),
                            'sort' => (int)$file["sortIndex"],
                            'name' => $file["fileText"]
                        );
                    }
                }

            if (!empty($data))
                DB::insertList('app_section_collection_files', $data);
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить файлы!";
            throw new Exception($this->error);
        }
    }

    protected function saveAddInfo()
    {
        $this->input['idCollection'] = $this->result['id'];
        return $this->saveTranslate() && $this->saveListImages() && $this->savePermission() && $this->saveCategory() && $this->saveCustomFields() && $this->saveFiles() && $this->saveSimilars();
    }
}
