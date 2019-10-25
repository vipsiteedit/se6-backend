<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;
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
    protected $filters = array();
    protected $tableName;
    protected $tableAlias;
    protected $allowedSearch = true;
    protected $availableSigns = array("=", "<=", "<", ">", ">=", "IN");
    protected $isNew;

    protected $allMode = false;
    protected $whereStr = null;
    protected $sqlFilter = null;

    private $patterns = array();


    protected $pagesFile;
    protected $siteFolder;
    protected $pathContent;
    protected $pathEdit;
    protected $projectFolder;
    protected $pathTemplates;
    protected $pathTranslate;
    protected $pathImages;
    protected $pathUnits;
    protected $apps = '';
    protected $appsId = 1;
    protected $idLang = 0;


    function __construct($input = null)
    {
        parent::__construct($input);
        $input = $this->input;
        $this->limit = !empty($input["limit"]) && $this->limit ? (int)$input["limit"] : $this->limit;
        $this->offset = !empty($input["offset"]) ? (int)$input["offset"] : $this->offset;
        $this->sortOrder = !empty($input["sortOrder"]) ? $input["sortOrder"] : $this->sortOrder;
        $this->sortBy = !empty($input["sortBy"]) ? $input["sortBy"] : $this->sortBy;
        $this->search = !empty($input["searchText"]) && $this->allowedSearch ? $input["searchText"] : null;
        $this->filters = empty($this->input["filters"]) || !is_array($this->input["filters"]) ?
            array() : $this->input["filters"];
        if (!empty($this->input["id"]) && empty($this->input["ids"]))
            $this->input["ids"] = array($this->input["id"]);
        $this->isNew = empty($this->input["id"]) && empty($this->input["ids"]);
        if (empty($this->tableAlias) && !empty($this->tableName)) {
            $worlds = explode("_", $this->tableName);
            foreach ($worlds as $world)
                $this->tableAlias .= $world[0];
        }
    }
	
	public function initPath()
	{
	    if (intval($this->input['seIdApp'])) {
			$app = new DB('apps', 'ap');
			$app->select('ap.app_name, ap.id_lang');
			$app->where('ap.id=?', intval($this->input['seIdApp']));
			$res = $app->fetchOne();
			$this->apps = $res['appName'];
			$this->idLang = $res['idLang'];
		}


        $this->pathUnits = DOCUMENT_ROOT . '/apps/units/' . $this->apps . '/';
		if (!is_dir($this->pathUnits)) mkdir($this->pathUnits, 0755, 1);
        $this->pathTemplates = DOCUMENT_ROOT . '/apps/templates/' . $this->apps . '/';
		if (!is_dir($this->pathTemplates)) mkdir($this->pathTemplates, 0755, 1);
        $this->pathTranslate = DOCUMENT_ROOT . '/apps/translate/' . $this->apps . '/';
        if (!is_dir($this->pathTranslate)) mkdir($this->pathTranslate, 0755, 1);
        $this->pathPlugins = DOCUMENT_ROOT . '/apps/plugins/';
        if (!is_dir($this->pathPlugins)) mkdir($this->pathPlugins, 0755, 1);

        //$this->pathContent = DOCUMENT_ROOT . (($this->seFolder) ? '/' . $this->seFolder : '');
        $this->pathContent = DOCUMENT_ROOT . '/www/'. $this->apps;
		if (!is_dir($this->pathContent)) mkdir($this->pathContent, 0755, 1);
	
	}

    public function setFilters($filters)
    {
        $this->filters = empty($filters) || !is_array($filters) ? array() : $filters;
    }

    protected function uniqueUrl($table, $field, $value = '', $id = false)
    {
        $u = new DB($table);
        $u->select('count(*) AS cnt');
        $u->where("`{$field}`='?'", $value);
        if ($id){
            $u->andWhere('id<>?', $id);
        }
        $result = $u->fetchOne();
        return ($result['cnt'] > 0);
    }

    private function createTableForInfo($settings)
    {
        $u = new DB($this->tableName, $this->tableAlias);
        $u->select($settings["select"]);

        if (!empty($settings["joins"])) {
            if (!empty($settings["joins"]["type"]))
                $settings["joins"] = array($settings["joins"]);
            foreach ($settings["joins"] as $join) {
                $join["type"] = strtolower(trim($join["type"]));
                if ($join["type"] == "inner")
                    $u->innerJoin($join["table"], $join["condition"]);
                if ($join["type"] == "left")
                    $u->leftJoin($join["table"], $join["condition"]);
                if ($join["type"] == "right")
                    $u->rightJoin($join["table"], $join["condition"]);
            }
        }
        return $u;
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

    public function info($id = null)
    {
        $id = empty($id) ? $this->input["id"] : $id;
        $this->input["id"] = $id;
        $settingsInfo = $this->getSettingsInfo();
        try {
            $u = $this->createTableForInfo($settingsInfo);
            $this->result = $u->getInfo($id);
            if (!$this->result["id"]) {
                $this->error = "Объект с запрошенными данными не найден!";
                $this->statusAnswer = 404;
            } else {
                if ($addInfo = $this->getAddInfo()) {
                    $this->result = array_merge($this->result, $addInfo);
                }
            }
        } catch (Exception $e) {
            $this->error = "Не удаётся получить информацию об объекте!";
        }
        return $this->result;
    }

    protected function getAddInfo()
    {
        return array();
    }

    public function delete()
    {
        $this->correctAll('del');


        try {
            $u = new DB($this->tableName,$this->tableAlias);
            if ($this->input["ids"] && !empty($this->tableName)) {
                $ids = implode(",", $this->input["ids"]);
                $u->where('id IN (?)', $ids)->deleteList();
            }
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся произвести удаление!";
        }
        return false;
    }

    /**
     *  correctAll - корректировка запроса с клиента, при использованиии AllMode-режима
     *
     * @param string $action - тип запроса AllMode
     *          * del - режим удаления
     *          * null (default) - обычный режим
     * @return boolean
     */
    public function correctAll($action = null){
        if(isset($this->input['allMode'])){
            $this->allMode = true;
            // Сбрасываем лимиты
            $this->limit = 10000;
            // Устанавливаем фильтры
            $this->setFilters($this->input['allModeLastParams']['filters']);
            $this->search = $this->input['allModeLastParams']['searchText'];

            $filter = $this->getFilterQuery();
            $result = $this->fetch(true);
            if($result){
                $ids = array();
                foreach ($result as $item){
                    array_push($ids,$item['id']);
                }
                if(!empty($ids)){unset($result);}
                //$input['ids'] = $ids;
                $this->input['ids'] = $ids;
            } else
                return $this->error = "Не выбрано ни одной записи!";
        }
        return true;
    }

    public function save()
    {

        try {
            $this->correctValuesBeforeSave();
            $this->correctAll();
            //writelog($this->input);
            DB::beginTransaction();
            $u = new DB($this->tableName);
            if (isset($this->input["imagePath"]))
                $this->input["idImage"] = $this->saveImage();
            /*
            if ($this->isNew && !isset($this->input[$this->sortFieldName]) &&
                in_array($this->sortFieldName, $u->getColumns())
            )
                $this->input[$this->sortFieldName] = $this->getNewSortIndex();

            if (($this->isNew || isset($this->input["url"])) &&
                in_array("url", $u->getColumns())
            ) {
                if (empty($this->input["url"]))
                    $this->input["url"] = $this->transliterationUrl($this->input["name"]);
                $this->input["url"] = $this->getUrl($this->input["url"], $this->input["id"]);
            }
            */
            $u->setValuesFields($this->input);
            $this->input["id"] = $u->save();
            if (empty($this->input["ids"]) && $this->input["id"])
                $this->input["ids"] = array($this->input["id"]);

            if ($this->input["id"] && $this->saveAddInfo()) {
                $this->info();
                DB::commit();
                return $this;
            } else throw new Exception();
        } catch (Exception $e) {
            DB::rollBack();
            $this->error = empty($this->error) ? "Не удаётся сохранить информацию об объекте!" : $this->error;
        }
    }

    public function getIdImage($imagePath = null)
    {
        $file = end(explode('/', $imagePath));
        $dir = dirname($imagePath);
        $idFolder = $this->getIdImageFolder($dir);

        $u = new DB("app_image");
        $u->where("name = '?'", $file);
        if ($idFolder)
            $u->andWhere("id_folder = ?", $idFolder);
        else $u->andWhere("id_folder IS NULL");
        $result = $u->fetchOne();

        return $result['id'];
    }
    
    public function saveImage($imagePath = null)
    {
        $imagePath = empty($imagePath) ? $this->input["imagePath"] : $imagePath;
        if (empty($imagePath))
            return null;
        //$file = basename($imagePath);
        $file = end(explode('/', $imagePath));
        $dir = dirname($imagePath);
        $idFolder = $this->getIdImageFolder($dir);

        $result["id"] = $this->getIdImage($imagePath);

        if (empty($result["id"])) {
            $u = new DB("app_image");
            $u->setValuesFields(array("name" => $file, "idFolder" => $idFolder));
            return $u->save();
        }
        return $result["id"];
    }
    
    public function getIdImageFolder($dir)
    {
        $dir = trim($dir, "/");
        if (empty($dir))
            return null;
        $u = new DB("app_image_folder", "imf");
        $u->where("name = '?'", $dir);
        $result = $u->fetchOne();
        if (empty($result["id"])) {
            $u = new DB("app_image_folder");
            $u->setValuesFields(array("name" => $dir));
            return $u->save();
        }
        return $result["id"];
    }

    public function saveListImages($tableImages = null, $fieldLinkName = null, $listname = 'images')
    {
        $tableImages = empty($tableName) ? $this->tableName . "_image" : $tableImages;
        $images = $this->input['images'];

        if (!isset($images) || !DB::existTable($tableImages))
            return true;
        try {
            if (empty($fieldLinkName)) {
                $t = new DB("{$tableImages}");
                $col = $t->getColumns();
                $fieldLinkName = $col[1];
            }
            $idsItems = $this->input["ids"];
            $idsStore = "";

            $is_main = false;

            foreach ($images as $key => $image) {
                $images[$key]["isMain"] = isset($image["isMain"]) ? ($image["isMain"] && !$is_main) : false;
                if (isset($image["isMain"]) && $image["isMain"]) $is_main = true;
            }

            if ($images && !$is_main) {
                $images[0]['isMain'] = true;
            }

            foreach ($images as $image) {
                if ($image["id"]) {
                    if (!empty($idsStore))
                        $idsStore .= ",";
                    $idsStore .= $image["id"];
                    $u = new DB("{$tableImages}", 'si');
                    $dimage = $image;
                    unset($dimage['title'], $dimage['alt']);
                    $u->setValuesFields($dimage);
                    $id = $u->save();
                    if (empty($this->input['idLang'])) $this->input['idLang'] = $this->idLang;
                    if ($this->input['idLang']) {
                        $limage = array('idImage'=>$image['id'], 'idLang' => $this->input['idLang'], 'title' => $image['title'], 'alt' => $image['alt']);
                        if ($image['idTranslate'])
                            $limage['id'] = $image['idTranslate'];
                        $sit = new DB("{$tableImages}_translate", 'sit');
                        $sit->setValuesFields($limage);
                        $sit->save();
                    }
                }
            }

            $idsStr = implode(",", $idsItems);
            if (!empty($idsStore)) {
                $u = new DB("{$tableImages}", 'sgi');
                $u->where("{$fieldLinkName} IN ($idsStr) AND NOT (id IN (?))", $idsStore)->deleteList();
            } else {
                $u = new DB("{$tableImages}", 'sgi');
                $u->where("{$fieldLinkName} IN (?)", $idsStr)->deleteList();
            }
            $data = array();
            $i = 0;
            foreach ($images as $image) {
                if (empty($image["id"])) {
                    foreach ($idsItems as $idItem) {
                        if ($idImage = $this->saveImage($image["imagePath"])) {
                            $data[] = array(
                                "{$fieldLinkName}" => $idItem,
                                'id_image' => $idImage,
                                //'title' => $image['title'],
                                //'alt' => $image['alt'],
                                'sort' => (int)$image["sort"],
                                'is_main' => $image["isMain"]
                            );
                        }
                    }
                }
            }

            //writelog($data);

            if (!empty($data))
                DB::insertList("{$tableImages}", $data);

            return true;
        } catch (Exception $e) {
            $this->error = "Can not save images!";
            throw new Exception($this->error);
        }
    }

    protected function getFileSize($filename, $lang = 'en')
    {
        $metrics['ru'] = array('б', 'Кб', 'Мб', 'Гб', 'Тб', 'Пб', 'Эб', 'Зб', 'Йб');
        $metrics['en'] = array('b', 'Kb', 'Mb', 'Gb', 'Tb');
        $i = 0;
        $size = filesize($filename);
        while ($size > 1024) {
            $i++;
            $size /= 1024;
        }
        return round($size, 1) . ' ' . $metrics[$lang][$i];
    }
    
    /*
    public function getIdFileFolder($dir)
    {
        $dir = trim($dir, "/")
        if (empty($dir))
            return null;
        $u = new DB("file_folder", "imf");
        $u->where("name = '?'", $dir);
        $result = $u->fetchOne();
        if (empty($result["id"])) {
            $u = new DB("file_folder");
            $u->setValuesFields(array("name" => $dir));
            return $u->save();
        }
        return $result["id"];
    }
    */

    public function sort()
    {
        if (empty($this->tableName))
            return;

        try {
            $sortIndexes = $this->input["indexes"];
            foreach ($sortIndexes as $index) {
                $u = new DB($this->tableName);
                $index["position"] = $index["sort"];
                $u->setValuesFields($index);
                $u->save();
            }
        } catch (Exception $e) {
            $this->error = "Не удаётся произвести сортировку элементов!";
        }
    }

    protected function correctValuesBeforeSave()
    {
        return true;
    }

    protected function correctValuesBeforeFetch($items = array())
    {
        return $items;
    }

    protected function saveAddInfo()
    {
        return true;
    }

    protected function getSettingsFetch()
    {
        return array();
    }

    protected function getSettingsFind()
    {
        return array();
    }

    protected function getSettingsInfo()
    {
        return array();
    }

    protected function getPattensBySelect($selectQuery)
    {
        $result = array();
        preg_match_all('/\w+[.]+\w+\s\w+/', $selectQuery, $matches);
        if (count($matches) && count($matches[0])) {
            foreach ($matches[0] as $match) {
                $match = explode(" ", $match);
                if (count($match) == 2) {
                    $key = DB::strToCamelCase($match[1]);
                    $result[$key] = $match[0];
                }
            }
        }
        return $result;
    }

    protected function getSearchQuery($searchFields = array())
    {
        $searchItem = trim($this->search);
        if (empty($searchItem))
            return array();
        $where = array();
        $searchWords = explode(' ', $searchItem);
        foreach($searchWords as $searchItem) {
            $result = array();
            if (!trim($searchItem)) continue;
            if (is_string($searchItem))
                $searchItem = trim(DB::quote($searchItem), "'");

            $finds = $this->getSettingsFind();
            $time = 0;
            if (strpos($searchItem, "-") !== false) {
                $time = strtotime($searchItem);
            }

            foreach ($searchFields as $field) {
                if (strpos($field["Field"], ".") === false)
                    $field["Field"] = $this->tableAlias . "." . $field["Field"];
                if (!empty($finds) && !in_array($field["Field"], $finds)) continue;

                // текст
                if ((strpos($field["Type"], "char") !== false) || (strpos($field["Type"], "text") !== false)) {
                    $result[] = "{$field["Field"]} LIKE '%{$searchItem}%'";
                    continue;
                }
                // дата
                if ($field["Type"] == "date") {
                    if ($time) {
                        $searchItem = date("Y-m-d", $time);
                        $result[] = "{$field["Field"]} = '$searchItem'";
                    }
                    continue;
                }
                // время
                if ($field["Type"] == "time") {
                    if ($time) {
                        $searchItem = date("H:i:s", $time);
                        $result[] = "{$field["Field"]} = '$searchItem'";
                    }
                    continue;
                }
                // дата и время
                if ($field["Type"] == "datetime") {
                    if ($time) {
                        $searchItem = date("Y-m-d H:i:s", $time);
                        $result[] = "{$field["Field"]} = '$searchItem'";
                    }
                    continue;
                }
                // число
                if (strpos($field["Type"], "int") !== false) {
                    if (intval($searchItem)) {
                        $result[] = "{$field["Field"]} = " . intval($searchItem);
                        continue;
                    }
                }
            }
            if (!empty($result))
                $where[] = '(' . implode(" OR ", $result) . ')';
        }
        return implode(" AND ", $where);
    }

    protected function getFilterQuery()
    {
        $where = array();
        $filters = array();
        if (!empty($this->filters["field"]))
            $filters[] = $this->filters;
        else $filters = $this->filters;
        foreach ($filters as $filter) {
            if (key_exists($filter["field"], $this->patterns))
                $field = $this->patterns[$filter["field"]];
            else {
                $field = DB::strToUnderscore($filter["field"]);
                $field = $this->tableAlias . ".`{$field}`";
            }
            $sign = empty($filter["sign"]) || !in_array($filter["sign"], $this->availableSigns) ?
                "=" : $filter["sign"];
            if ($sign == "IN") {
                $values = explode(",", $filter["value"]);
                $filter['value'] = null;
                foreach ($values as $value) {
                    if ($filter['value'])
                        $filter['value'] .= ",";
                    $value = trim($value);
                    $filter['value'] .= "'{$value}'";
                }
                $value = "({$filter['value']})";
            } else $value = !isset($filter["value"]) ? null : "'{$filter['value']}'";
            if (!$field || !$value)
                continue;
            $where[] = "{$field} {$sign} {$value}";
        }
        return implode(" AND ", $where);
    }

    protected function getWhereQuery($searchFields = array())
    {
        $query = null;
        $searchQuery = $this->getSearchQuery($searchFields);
        $filterQuery = $this->getFilterQuery();
        if ($searchQuery)
            $query = $searchQuery;
        if ($filterQuery) {
            if (!empty($query))
                $query = "({$query}) AND ";
            $query .= $filterQuery;
        }
        return $query;
    }

    public function getArrayFromCsv($file, $csvSeparator = ";")
    {
        if (!file_exists($file))
            return null;

        $result = array();
        if (($handle = fopen($file, "r")) !== FALSE) {
            $i = 0;
            $keys = array();
            while (($row = fgetcsv($handle, 10000, $csvSeparator)) !== FALSE) {
                if (!$i) {
                    foreach ($row as &$item)
                        $keys[] = iconv('CP1251', 'utf-8', $item);
                } else {
                    $object = array();
                    $j = 0;
                    foreach ($row as &$item) {
                        $object[$keys[$j]] = iconv('CP1251', 'utf-8', $item);
                        $j++;
                    }
                    $result[] = $object;
                }
                $i++;
            }
            fclose($handle);
        }
        return $result;
    }

    public function post()
    {
        $countFiles = count($_FILES);
        $ups = 0;
        $items = array();
        $dir = DOCUMENT_ROOT . "/files";
        $url = !empty($_POST["url"]) ? $_POST["url"] : null;
        if (!file_exists($dir) || !is_dir($dir))
            mkdir($dir);

        if ($url) {
            $content = file_get_contents($url);
            if (empty($content)) {
                $this->error = "Не удается загрузить данные по указанному URL!";
            } else {
                $items[] = array("url" => $url, "name" => array_pop(explode("/", $url)));
                $this->result['items'] = $items;
            }
        } else {
            for ($i = 0; $i < $countFiles; $i++) {
                $file = empty($_FILES["file"]) ? $_FILES["file$i"]['name'] : $_FILES["file"]['name'];
                $uploadFile = $dir . '/' . $file;
                $fileTemp = $_FILES["file$i"]['tmp_name'];
                $urlFile = 'http://' . HOSTNAME . "/files/{$file}";
                if (!filesize($fileTemp) || move_uploaded_file($fileTemp, $uploadFile)) {
                    $items[] = array("url" => $urlFile, "name" => $file);
                    $ups++;
                }
            }
            if ($ups == $countFiles)
                $this->result['items'] = $items;
            else $this->error = "Не удается загрузить файлы!";
        }

        return $items;
    }

    public function postRequest($shorturl, $data)
    {
        $url = "http://" . HOSTNAME . "/" . $shorturl;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        return curl_exec($ch);
    }


}