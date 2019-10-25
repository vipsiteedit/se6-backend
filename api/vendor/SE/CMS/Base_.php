<?php

namespace SE\CMS;

use SE\Base as CustomBase;

class Base extends CustomBase
{

    protected $isTableMode = true;
    protected $limit = 100;
    protected $offset = 0;
    protected $sortBy = "id";
    protected $groupBy = "id";
    protected $sortOrder = "desc";
    protected $availableFields;
    protected $filterFields;
    protected $search;
    protected $filters = [];
    protected $tableName;
    protected $tableAlias;
    protected $allowedSearch = true;
    protected $availableSigns = array("=", "<=", "<", ">", ">=", "IN");
    protected $isNew;

    protected $allMode = false;
    protected $whereStr = null;
    protected $sqlFilter = null;

    private $patterns = [];


    protected $pagesFile;
    protected $siteFolder;
    protected $pathContent;
    protected $pathEdit;
    protected $projectFolder;
    protected $pathTemplates;

    function __construct($input)
    {
        parent::__construct($input);
        $this->projectFolder = DOCUMENT_ROOT . '/projects' . (($this->seFolder) ? '/' . $this->seFolder : '');
        $this->pathEdit = $this->projectFolder . "/edit/";
        $this->pathTemplates = DOCUMENT_ROOT . '/templates/ru/';
        $this->pathContent = DOCUMENT_ROOT . (($this->seFolder) ? '/' . $this->seFolder : '');
        if (!is_dir($this->pathEdit . "pages/"))
            mkdir($this->pathEdit . "pages/", 0700, 1);
        //$this->siteFolder = 'www/';
    }

    public function fetch($isId = false)
    {
        $settingsFetch = $this->getSettingsFetch();

        $settingsFetch["select"] = $settingsFetch["select"] ? $settingsFetch["select"] : "*";
        if ($isId) {
            $settingsFetch["select"] = $this->tableAlias . '.id';
        }
        $this->patterns = $this->getPattensBySelect($settingsFetch["select"]);
        try {
            $u = $this->createTableForInfo($settingsFetch);
            $searchFields = $u->getFields();
            if (!empty($this->patterns)) {
                $this->sortBy = key_exists($this->sortBy, $this->patterns) ?
                    $this->patterns[$this->sortBy] : $this->sortBy;
                foreach ($this->patterns as $key => $field)
                    $searchFields[$key] = array("Field" => $field, "Type" => "text");
            }
            if (!empty($this->search) || !empty($this->filters))
                $u->where($this->getWhereQuery($searchFields));
            if ($this->groupBy)
                $u->groupBy($this->groupBy);
            $u->orderBy($this->sortBy, $this->sortOrder == 'desc');
            //writeLog($this);
            //writeLog($this->input);

            $this->result["items"] = $this->correctValuesBeforeFetch($u->getList($this->limit, $this->offset));
            $this->result["count"] = $u->getListCount();
            if (!empty($settingsFetch["aggregation"])) {
                if (!empty($settingsFetch["aggregation"]["type"]))
                    $settingsFetch["aggregation"] = array($settingsFetch["aggregation"]);
                foreach ($settingsFetch["aggregation"] as $aggregation) {
                    $query = "{$aggregation["type"]}({$aggregation["field"]})";
                    $this->result[$aggregation["name"]] = $u->getListAggregation($query);
                }
            }
        } catch (Exception $e) {
            $this->error = "Не удаётся получить список объектов!";
        }

        return $this->result["items"];
    }



    protected function parsePagesToJson($pageTo = 'edit')
    {
        $fileSource = $this->projectFolder . "/pages.xml";
        $filePages = $this->projectFolder . "/{$pageTo}/pages.json";
        $items = array();
        if (!file_exists($filePages) || filemtime($fileSource) > filemtime($filePages)) {
            $pages = simplexml_load_file($fileSource);
            $pagesArr = array();
            foreach ($pages->page as $page) {
                $item = $this->getObject($page, 'pages');
                $item['name'] = strval($page['name']);
                $pagesArr[] = $item;
            }
            if (!is_dir($this->projectFolder . '/' . $pageTo . "/pages/"))
                mkdir($this->projectFolder . '/' . $pageTo . "/pages/", 0700, 1);

            file_put_contents($filePages, json_encode($pagesArr));

        } else {
            $pagesArr = json_decode(file_get_contents($filePages), true);
        }
        return $pagesArr;
    }

    protected function parsePageToJson($name, $pageTo = 'edit')
    {
        if (!empty($name)) {
            if ($name == 'index') {
                // Глобальный контейнер
                $pathPages = $this->projectFolder . "/";
                $fileEdit = $this->projectFolder . '/' . $pageTo  . "/project.json";
                $fileSource = $this->projectFolder . "/project.xml";
                $isPage = false;
            } else {
                $pathPages = $this->projectFolder . "/pages/";
                $fileEdit = $this->projectFolder . '/' . $pageTo . "/pages/{$name}.json";
                $fileSource = $pathPages . "{$name}.xml";
                $isPage = true;
            }
            if (!is_dir($this->projectFolder . '/' . $pageTo . "/pages/"))
                mkdir($this->projectFolder . '/' . $pageTo . "/pages/", 0700, 1);

            if (!file_exists($fileEdit) && file_exists($fileSource)
                || filemtime($fileSource) > filemtime($fileEdit)
            ) {
                $xml = simplexml_load_file($fileSource);
                if ($isPage)
                    file_put_contents($fileEdit, json_encode($this->parsePageXmlArr($xml)));
                else
                    file_put_contents($fileEdit, json_encode($this->parseProjectXmlArr($xml)));
            }

            return $isPage;
        }
    }

    protected function parsePageXmlArr($xml)
    {
        $data = array();
        $data['title'] = (!empty($xml->title)) ? strval($xml->title) : '';
        $data['map'] = (!empty($xml->css)) ? strval($xml->css) : 'default';
        $data['header'] = (!empty($xml->head)) ? strval($xml->head) : '';
        $data['access']['groupsname'] = (!empty($xml->groupsname)) ? strval($xml->groupsname) : '';
        $data['access']['groupslevel'] = (!empty($xml->groupslevel)) ? strval($xml->groupslevel) : '';
        $data['seo']['title'] = (!empty($xml->titlepage)) ? strval($xml->titlepage) : '';
        $data['seo']['keywords'] = (!empty($xml->keywords)) ? strval($xml->keywords) : '';
        $data['seo']['description'] = (!empty($xml->description)) ? strval($xml->description) : '';
        $data['seo']['prioritypage'] = (!empty($xml->prioritypage)) ? intval($xml->prioritypage) : 5;
        $data['vars'] = array();
        if (!empty($xml->vars))
            foreach ($xml->vars[0] as $n => $v) {
                //if (!empty($v))
                $data['vars'][$n] = strval($v);
            }
        $data['containers'] = array();
        $items = array();
        if (count($xml->sections)) {
            foreach ($xml->sections as $value) {
                list($id_content,) = explode('.', strval($value['name']));
                $cont_id = floor($id_content / 1000);
                $items[$cont_id][] = $this->getObject($value);
            }
            foreach ($items as $cont_id => $item) {
                $data['containers'][] = array('id' => $cont_id, 'items' => $item);
            }
        }
        return $data;
    }

    protected function parseProjectXmlArr($xml)
    {
        $data = array();
        $data['documenttype'] = (isset($xml->documenttype)) ? strval($xml->documenttype) : '1';
        $data['adaptive'] = (!empty($xml->adaptive)) ? strval($xml->adaptive) : '0';
        $data['language'] = (!empty($xml->language)) ? strval($xml->language) : 'rus';
        $data['setools'] = (isset($xml->setools)) ? strval($xml->setools) : '1';
        $data['sitedomain'] = (!empty($xml->sitedomain)) ? strval($xml->sitedomain) : '';
        $data['siteredirect'] = (!empty($xml->siteredirect)) ? strval($xml->siteredirect) : '0';
        $data['groupusers'] = (!empty($xml->groupusers)) ? strval($xml->groupusers) : '';
        if (!isset($xml->bootstraptools) && $data['adaptive']) $xml->bootstraptools = 1;
        $data['bootstraptools'] = (!empty($xml->bootstraptools)) ? strval($xml->bootstraptools) : '0';
        $data['wmyandex'] = (!empty($xml->wmyandex)) ? strval($xml->wmyandex) : '';
        $data['wmgoogle'] = (!empty($xml->wmgoogle)) ? strval($xml->wmgoogle) : '';

        $data['vars'] = array();
        if (!empty($xml->vars))
            foreach ($xml->vars[0] as $n => $v) {
                //if (!empty($v))
                $data['vars'][$n] = strval($v);
            }
        $data['containers'] = array();
        $items = array();
        if (count($xml->sections)) {
            foreach ($xml->sections as $value) {
                list($id_content,) = explode('.', strval($value['name']));
                $cont_id = floor($id_content / 1000);
                $items[$cont_id][] = $this->getObject($value);
            }
            foreach ($items as $cont_id => $item) {
                $data['containers'][] = array('id' => $cont_id, 'items' => $item);
            }
        }
        return $data;
    }

    protected function parseArrPage($arr)
    {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8" ?><page></page>');
        $xml->groupsname = $arr['success']['groupsname'];
        $xml->groupslevel = $arr['success']['groupslevel'];
        $xml->css = $arr['map'];
        $xml->head = $arr['header'];
        $xml->title = $arr['title'];
        $xml->titlepage = $arr['seo']['title'];
        $xml->keywords = $arr['seo']['keywords'];
        $xml->description = $arr['seo']['description'];
        $xml->prioritypage = $arr['seo']['prioritypage'];
        foreach ($arr['vars'] as $n => $v) {
            $xml->vars->$n = $v;
        }
        $i = 0;
        foreach($arr['containers'] as $container) {
            foreach ($container['items'] as $section) {
                $xml->sections[$i]['name'] = strval($section['id']);
                $this->setObject($xml->sections[$i], $section);
                $i++;
            }
        }
        return $xml;
    }

    protected function parseArrProject($data)
    {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8" ?><site></site>');
        $xml->documenttype = $data['documenttype'];
        $xml->adaptive = $data['adaptive'];
        $xml->setools = $data['setools'];
        $xml->language = $data['language'];
        $xml->sitedomain = $data['sitedomain'];
        $xml->siteredirect = $data['siteredirect'];
        $xml->groupusers = $data['groupusers'];
        $xml->bootstraptools = $data['bootstraptools'];
        $xml->wmyandex = $data['wmyandex'];
        $xml->wmgoogle = $data['wmgoogle'];
        foreach ($data['vars'] as $n => $v) {
            $xml->vars->$n = $v;
        }
        $i = 0;
        foreach($data['containers'] as $container) {
            foreach ($container['items'] as $section) {
                $xml->sections[$i]['name'] = strval($section['id']);
                $this->setObject($xml->sections[$i], $section);
                $i++;
            }
        }
        return $xml;
    }

    private function setObject(&$xml, $arr)
    {
        //$xml['name'] = $arr['id'];
        $obj = $xml;
        foreach ($arr as $name => $val) {

            /* if ($name == 'images') {
                 if (!empty($val['images'][0])) {
                     $obj->image = $val['images'][0]['image'];
                     $obj->image_title = $val['images'][0]['image_title'];
                     $obj->image_alt = $val['images'][0]['image_title'];
                 }
                 continue;
             }
             */

            if ($name == 'objects') {
                $i = 0;
                foreach ($val as $id => $ob) {
                    //$objs = new stdClass;
                    $obj->objects[$i]['name'] = $ob['id'];
                    foreach ($ob as $n => $v) {
                        $obj->objects[$i]->$n = $v;
                    }
                    $i++;
                }
                continue;
            }


            if ($name == 'params') {
                //$objs = new stdClass;
                foreach ($val as $n => $v) {
                    $obj->parametrs->$n = $v;
                }
                continue;
            }
            if ($name == 'sources') {
                //$objs = new stdClass;
                foreach ($val as $v) {
                    $n = $v['name'];
                    $v = $v['value'];
                    $obj->sources->$n = $v;
                }
                continue;
            }

            if ($name == 'translates') {
                //$objs = new stdClass;
                foreach ($val as $n => $v) {
                    $obj->translates->$n = $v;
                }
                //$obj->translates = $objs;
                continue;
            }

            $obj->$name = strval($val);
        }
    }

    private function getObject($obj, $nobj = 'sections')
    {
        $arr = array();
        foreach ($obj as $name => $val) {
            if ($name == 'sources' && $nobj == 'sections') {
                $arr[$name] = array();
                foreach ($val as $n1 => $v1) {
                    $arr['sources'][] = array('name' => $n1, 'value' => strval($v1[0]));
                    //writeLog($arr);
                }
            }
            if ($name == 'modules' && $nobj == 'pages') {
                $arr['modules'][] = array('name' => strval($val['name']), 'id' => strval($val['id']));
            }
            if ($name == 'objects') {
                $arr['objects'][count($arr['objects'])] = $this->getObject($val, $name);
                continue;
            }
            if ($name == 'parametrs') {
                $arr['params'] = $this->getObject($val, $name);
                continue;
            }
            if ($name == 'translates') {
                $arr['translates'] = $this->getObject($val, $name);
                continue;
            }
            if ($val->children()) {
                foreach ($val as $n1 => $v1) {
                    $arr[$name][$n1] = $this->getObject($v1, $name);
                }
            } else {
                $arr[$name] = strval($val);
            }
        }
        /* if ($nobj == 'sections') {
             $arr['images'] = array();
             if (!empty($obj->image)) {
                 $arr['images'][] = array('image' => strval($obj->image), 'title' => strval($obj->image_title), 'alt' => strval($obj->image_alt));
             }
         }*/
        return $arr;
    }

}