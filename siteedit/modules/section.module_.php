<?php

// Функции разделов

class section {

    //rivate $apps;
    private $alias;
    private $params;
    private $section = array();
    private $paramers = array();
    private $groups = array();

    public function __construct($alias, $params = array())
    {
        $this->alias = $alias;
        $this->params = $params;
        $u = new DB('app_section', 'as');
        $u->select('as.id, as.typename, as.name, ap.name AS access_group, ap.level AS access_level, as.visible, au.pattern, au1.pattern AS pattern_group');
        $u->leftJoin('app_section_permission asp', 'as.id=asp.id_section');
        $u->leftJoin('app_permission ap', 'asp.id_permission=ap.id');
        $u->leftJoin('app_urls au', "as.id=au.id_section AND au.type='item'");
        $u->leftJoin('app_urls au1', "as.id=au1.id_section AND au1.type='group'");
        $u->where("as.alias='?'", $alias);
        $this->section = $u->fetchOne();
        $this->paramers = $this->getParametrList();
        $this->groups = $this->getSectionGroup();
        return $this;
    }

    public function isVisible()
    {
        // обработка прав доступа пользователя
        return $this->section['visible']; // && $this->section['access_group'] && $this->section['access_level'];
    }

    public function getSection()
    {
        return $this->section;
    }



    public function GetCollection($limit = NULL, $offset = NULL)
    {
        if (!$this->isVisible()) return array();

        $u = new DB('app_section_collection', 'ac');
        $u->where('ac.id_section=?', $this->section['id']);
        $count = $u->getListCount();
        return array('count'=>$count, 'items' => $u->getList($limit, $offset));
    }

    public function getParametrList($idSection = false)
    {
        if (!$this->isVisible()) return array();
        $idSection = ($idSection) ? $idSection : $this->section['id'];

        $u = new DB('app_section_parametrs', 'asз');
        $u->select('field, value');
        $u->where('id_section=?', $idSection);
        $plist = $u->getList();
        $result = array();
        foreach($plist as $item)
        {
            $result[$item['field']] = $item['value'];
        }
        return $result;
    }

    public function getCollectionList($idSection = false, $params = array(), $limit = false, $offset = false, $sortBy = array())
    {
        if (!$this->isVisible()) return array();
        $idSection = intval(($idSection) ? $idSection : $this->section['id']);
        $fieldsString = array('url','title', 'image', 'note', 'name', 'date_public', 'seo_keywords', 'seo_description', 'all');
        $fieldsInt = array('id', 'id_parent', 'id_section');


        $u = new DB('app_section_collection', 'asc');
        //$u->addField('visits','int(10)', '0');
        $u->select('`asc`.*, GROUP_CONCAT(DISTINCT ascg.id_group) AS groups, 
        ROUND(IFNULL((SUM(ascr.mark)/COUNT(ascr.id)),0)) AS rating,
        COUNT(ascc.id) comments');
        $u->leftJoin('app_section_collection_group ascg', '`asc`.id=ascg.id_collection');
        $u->leftJoin('app_section_collection_values ascv', '`asc`.id=ascv.id_collection');
        // Рейтинг
        $u->leftJoin('app_section_collection_reviews ascr', '`asc`.id=ascr.id_collection');
        $u->leftJoin('app_section_collection_comments ascc', '`asc`.id=ascc.id_collection');

        $u->groupBy('`asc`.id');
            if (!empty($sortBy) && (in_array($sortBy['field'], $fieldsString) || in_array($sortBy['field'], $fieldsInt))) {
                $asc = ($sortBy['asc']) ? 1 : 0;
                $u->orderBy('`asc`.' . $sortBy['field'], $asc);
            } else {
                $u->orderBy('`asc`.sort', 0);
            }
        $u->where('asc.id_section=?', $idSection);
        if (!empty($params['search'])) {
            foreach($params['search'] as $search) {
                if (empty($search['sign'])) $search['sign'] = 'IN';
                $fields = (strpos($search['field'], ',') !== false) ? explode(',', $search['field']) : array($search['field']);
                $or = array();
                foreach($fields as $field) {
                    $field = trim($field);
                    $where = array();
                    $value = str_replace("'", "\'", $search['value']);
                    if (in_array($field, $fieldsString)) {
                        $value = (is_array($value)) ? implode("','", $value) : $value;
                            $where[] = "`asc`.`{$field}` {$search['sign']} ('{$value}')";
                    } else {
                        if (!empty($field) && in_array($field, $fieldsInt)) {
                            $value = (is_array($value)) ? implode(',', $value) : $value;
                            $where[] = "`asc`.`{$field}` {$search['sign']} ('{$value}')";
                        }
                    }
                    if (!empty($where)) {
                        $or[] = join(" AND ", $where);
                    }
                }
                if (!empty($or)) {
                    $u->andWhere('(' . join(" OR ", $or) . ')');
                }

            }
            // Проверить в переменных
        }
        if (!empty($params['groups'])) {
            $u->andWhere('ascg.id_group IN (?)', join(',', $params['groups']));
        }

        $count = $u->getListCount();
        $items = array();
        foreach($u->getList($limit, $offset) as $item) {
            $item['groups'] = explode(',', $item['groups']);
            $item['image_prev'] = $item['image'];
            if ($item['image']) {
                if (!empty($this->paramers['image-prev-size']) && strpos($item['image'], '//') === false) {
                    $item['image_prev'] = se_getDImage(WWW_DIR . '/' . ACTIVE_APP . '/' . $item['image'], $this->paramers['image-prev-size'], 'm');
                }
                if (!empty($this->paramers['image-size']) && strpos($item['image'], '//') === false) {
                    $t = (strpos($this->paramers['image-size'], 'x')!==false) ? 's' : 'm';
                    $item['image'] = se_getDImage(WWW_DIR . '/' . ACTIVE_APP . '/' . $item['image'], $this->paramers['image-size'], $t);
                }
            }
            $app = App::get();
            if ($app['type'] == 'item' && empty($_SESSION['APP_SECTION_VISITS'][$idSection][$item['id']])) {
                $_SESSION['APP_SECTION_VISITS'][$idSection][$item['id']] = true;
                $u = new DB('app_section_collection');
                $u->addField('visits','int(10)', '0');
                DB::query("UPDATE app_section_collection SET `visits`=`visits`+1 WHERE id={$item['id']}");
            }
            $items[] = $item;
        }
       // print_r($items);
        return array('count'=>$count, 'items' => $items);
    }

    public function getCollectionListId($idSection = false, $params = array(), $limit = false, $offset = false)
    {
        if (!$this->isVisible()) return array();
        $idSection = intval(($idSection) ? $idSection : $this->section['id']);
        $u = new DB('app_section_collection', 'asc');
        //$u->addField('visits','int(10)', '0');
        $u->select('`asc`.id, `asc`.`name`');
        $u->leftJoin('app_section_collection_group ascg', '`asc`.id=ascg.id_collection');
        $u->groupBy('`asc`.id');
        $u->orderBy('`asc`.sort', 0);
        $u->where('asc.id_section=?', $idSection);
        if (!empty($params['search'])) {
            foreach($params['search'] as $search) {
                if (empty($search['sign'])) $search['sign'] = 'IN';
                $fields = (strpos($search['field'], ',') !== false) ? explode(',', $search['field']) : array($search['field']);
                $or = array();
                foreach($fields as $field) {
                    $field = trim($field);
                    $where = array();
                    $value = str_replace("'", "\'", $search['value']);
                    if (in_array($field, $fieldsString)) {
                        $value = (is_array($value)) ? implode("','", $value) : $value;
                        $where[] = "`asc`.`{$field}` {$search['sign']} ('{$value}')";
                    } else {
                        if (!empty($field) && in_array($field, $fieldsInt)) {
                            $value = (is_array($value)) ? implode(',', $value) : $value;
                            $where[] = "`asc`.`{$field}` {$search['sign']} ('{$value}')";
                        }
                    }
                    if (!empty($where)) {
                        $or[] = implode(" AND ", $where);
                    }
                }
                if (!empty($or)) {
                    $u->andWhere('(' . implode(" OR ", $or) . ')');
                }

            }
            // Проверить в переменных
        }
        if (!empty($params['groups']) && is_array($params['groups'])) {
            $u->andWhere('ascg.id_group IN (?)', implode(',', $params['groups']));
        }

        //echo $u->getSql();
        $count = $u->getListCount();
        $items = $u->getList($limit, $offset);

        return array('count'=>$count, 'items' => $items);
    }

    /*
     *  вывод контента по ID записей
     */
    public function getCollectionItems($idCollections = array(), $isValues = false)
    {
        if (!$this->isVisible()) return array();
        $u = new DB('app_section_collection', 'asc');
        //$u->addField('visits','int(10)', '0');
        $u->select('`asc`.*, GROUP_CONCAT(DISTINCT ascg.id_group) AS groups, 
        ROUND(IFNULL((SUM(ascr.mark)/COUNT(ascr.id)),0)) AS rating,
        COUNT(ascc.id) comments');
        $u->leftJoin('app_section_collection_group ascg', '`asc`.id=ascg.id_collection');
        $u->leftJoin('app_section_collection_values ascv', '`asc`.id=ascv.id_collection');
        // Рейтинг
        $u->leftJoin('app_section_collection_reviews ascr', '`asc`.id=ascr.id_collection');
        $u->leftJoin('app_section_collection_comments ascc', '`asc`.id=ascc.id_collection');
        $u->where('asc.id IN (?)', join(',', $idCollections));
        $items = array();
        foreach($u->getList() as $item) {
            $item['url'] = ($item['url']) ? $item['url'] : convert_pattern_url($this->section['pattern'], $item);
            $item['groups'] = explode(',', $item['groups']);
            $item['image_prev'] = $item['image'];
            if ($item['image']) {
                if (!empty($this->paramers['image-prev-size']) && strpos($item['image'], '//') === false) {
                    $item['image_prev'] = se_getDImage(WWW_DIR . '/' . ACTIVE_APP . '/' . $item['image'], $this->paramers['image-prev-size'], 'm');
                }
                if (!empty($this->paramers['image-size']) && strpos($item['image'], '//') === false) {
                    $t = (strpos($this->paramers['image-size'], 'x')!==false) ? 's' : 'm';
                    $item['image'] = se_getDImage(WWW_DIR . '/' . ACTIVE_APP . '/' . $item['image'], $this->paramers['image-size'], $t);
                }
            }
            $app = App::get();
            if ($app['type'] == 'item' && empty($_SESSION['APP_SECTION_VISITS'][$item['idSection']][$item['id']])) {
                $_SESSION['APP_SECTION_VISITS'][$item['idSection']][$item['id']] = true;
                $u = new DB('app_section_collection');
                $u->addField('visits','int(10)', '0');
                DB::query("UPDATE app_section_collection SET `visits`=`visits`+1 WHERE id={$item['id']}");
            }
            $items[] = $item;
        }
        if ($isValues) {
            $fields = $this->getCollectionValues($idCollections);
            foreach ($items as &$rec) {
                $rec['files'] = $this->getCollectionFiles($rec['id']);
                foreach ($fields['items'] as $fld) {
                    if ($rec['id'] == $fld['idCollection']) {
                        if ($fld['type'] == 'image') {
                            $rec['images'][$fld['field']] = $fld['value'];
                        } else {
                            $rec['fields'][$fld['field']] = $fld['value'];
                        }
                    }
                }
            }
        }
        return $items;
    }

    public function getCollectionFiles($idCollection, $idField = false)
    {
        $u = new DB('app_section_collection_files', 'si');
        $u->select('si.id, si.file, name AS title, description, icon, COUNT(suf.id) AS `downloaded`');
        $u->leftJoin('se_user_file suf', 'si.id=suf.id_file');
        $u->where('si.id_collection = ?', $idCollection);
        if($idField)
            $u->andwhere('si.id_field = ?', $idField);
        $u->orderBy("sort");
        $u->groupBy("si.id");
        $files = $u->getList();
        foreach($files as &$file) {
            $file['url'] = $file['file'];
            //$file['url'] = '/lib/file.php?id=' . $file['id'];
            if (strpos($file['file'], '.mp3')) {
                if (file_exists(VENDORS_CORE_DIR.'/MP3/Id.php')) {
                    require_once VENDORS_CORE_DIR.'/MP3/Id.php';
                    $id3 = new MP3_Id();
                    $result = $id3->read('./' . $file['file']);
                    if (PEAR::isError($result)) {
                        //die($result->getMessage() . "\n");
                    }
                    $result = $id3->study();
                    if (PEAR::isError($result)) {
                        //die($result->getMessage() . "\n");
                    }
                    $file['duration']['name'] = $id3->getTag('name');
                    $file['duration']['artists'] = $id3->getTag('artists');
                    $file['duration']['album'] = $id3->getTag('album');
                    $file['duration']['year'] = $id3->getTag('year');
                    $file['duration']['comment'] = $id3->getTag('comment');
                    $file['duration']['genre'] = $id3->getTag('genre');
                    $file['duration']['genreno'] = $id3->getTag('genreno');
                    $file['duration']['track'] = $id3->getTag('track');

                    $file['duration']['mpeg_ver'] = $id3->getTag('mpeg_ver');
                    $file['duration']['layer'] = $id3->getTag('layer');

                    $file['duration']['mode'] = $id3->getTag('mode');
                    $file['duration']['filesize'] = $id3->getTag('filesize');
                    $file['duration']['bitrate'] = $id3->getTag('bitrate');
                    $file['duration']['length'] = $id3->getTag('length');
                    $file['duration']['frequency'] = $id3->getTag('frequency');
                }
            }
            unset($file['id']);
        }
        return array('count'=>count($files), 'items'=>$files);
    }

    public function GetCollectionValues($collection = array(), $field = false)
    {
        if (!$this->isVisible()) return array();
        $u = new DB('app_section_fields', 'asf');
        $u->select('ascv.id, `ascv`.id_field, ascv.id_collection, `asf`.code AS field, ascv.value, asf.defvalue, asf.type');
        $u->leftJoin('app_section_collection_values ascv', '`ascv`.id_field=asf.id');
        $u->where('asf.id_section=?', $this->section['id']);
        if ($collection) {
            if (is_array($collection))
                $u->andWhere('ascv.id_collection IN (?)', join(',', $collection));
            else
                $u->andWhere('ascv.id_collection = ?', intval($collection));
        }
        if ($field) {
            $u->andWhere("asf.field='?'", $field);
        }
        $items = $u->getList();
        return array('count'=>count($items), 'items'=>$items);
    }

    /**
     * @return mixed
     */
    public function getIdFromName($name)
    {
        if (!$this->isVisible()) return false;
        $u = new DB('app_section_collection', 'asc');
        $u->select('id');
        $u->where('asc.id_section=?', $this->section['id']);
        $u->andWhere("asc.name='?'", $name);
        $result = $u->fetchOne();
        return $result['id'];
    }

    public function getCollectionItem($idCollection)
    {
        if (!$this->isVisible()) return array();
        $u = new DB('app_section_collection', 'asc');
        $u->addField('visits','int(10)', '0');
        $u->select('`asc`.*, GROUP_CONCAT(ascg.id_group) AS groups');
        $u->leftJoin('app_section_collection_group ascg', '`asc`.id=ascg.id_collection');
        $u->leftJoin('app_section_collection_values ascv', '`asc`.id=ascv.id_collection');
        $u->where('asc.id_section=?', $this->section['id']);
        $u->andWhere('asc.id = ?', intval($idCollection));
        $result =  $u->fetchOne();
        $values = $this->GetCollectionValues($idCollection);
        $result['fields'] = $values['items'];

        // Добавляем просмотры
        if ($idCollection && empty($_SESSION['APP_SECTION_VISITS'][$this->section['id']][$idCollection])) {
            $_SESSION['APP_SECTION_VISITS'][$this->section['id']][$idCollection] = true;
            DB::query("UPDATE app_section_collection SET `visits`=`visits`+1 WHERE id={$idCollection}");
        }
        return $result;
    }

    public function getSectionGroupsId($uri = array())
    {
        if (!$this->isVisible()) return array();
        //$idSection = ($idSection) ? $idSection : $this->section['id'];
        foreach ($uri as $key=>$value) {
            if (!in_array($key, array('name', 'id'))) {
                unset($uri[$key]);
            }
        }
        return $this->findGroupId($this->groups['items'], $uri);
    }

    public function getSectionGroupItem($id)
    {
        if (!$this->isVisible()) return array();
        return $this->findGroupItem($this->groups['items'], $id);
    }



    private function findGroupId($items, $arr, $childs = false)
    {
        $find = array();
        foreach($items as $item)
        {
            foreach($arr as $name=>$val) {
                if ((!empty($item[$name]) && $item[$name] == $val) || $childs) {
                    $find[] = $item['id'];
                }
                if (!empty($item['items'])) {
                    $res = $this->findGroupId($item['items'], $arr, !empty($find));
                    if (!empty($res)) {
                        foreach ($res as $it) {
                            $find[] = $it;
                        }
                    }
                }
                if (!empty($find) && !$childs)
                    return $find;
            }
        }
        return $find;
    }

    private function findGroupItem($items, $id)
    {
        foreach($items as $item)
        {
                if ($item['id'] == $id) {
                    return $item;
                }
                if (!empty($item['items']) && $res = $this->findGroupItem($item['items'], $id)) {
                    return $res;
                }
        }
        return array();
    }

    public function getGroups()
    {
        return $this->groups;
    }

    private function getSectionGroup()
    {
        if (!$this->isVisible()) return array();
        $idSection = $this->section['id'];


        $u = new DB('app_section_groups', 'asg');
        $u->select("asg.*");
        $u->where('asg.id_section=?', $idSection);
        $u->orderBy('sort', false);
        //echo $u->getSql();
        $items = $u->getList();

        $groups = array();
        foreach($items as &$item) {
            $item['url'] = convert_pattern_url($this->section['patternGroup'], $item);
            $groups[intval($item['idParent'])][] = $item;
        }
        $items = $this->setTree($groups);
        return array('count'=>count($items), 'items' => $items);

    }

    private function setTree($groups, $idParent = 0)
    {
        $items = array();
        if (!isset($groups[$idParent])) return array();
        foreach($groups[$idParent] as $item){
            if (!empty($groups[$item['id']])) {
                $item['items'] = $this->setTree($groups, $item['id']);
            }
            $items[] = $item;
        }
        return $items;
    }
}



function GetSectionElementEx($alias = '', $params = array())
{
    return new section($alias, $params);
}

//// Выводим список параметров для раздела
//function getSectionParametrList($section_id)
//{
//    $u = new DB('app_section_parametrs', 'asз');
//    $u->select('field, value');
//    $u->where('id_section=?', $section_id);
//    $plist = $u->getList();
//    $result = array();
//    foreach($plist as $item)
//    {
//        $result[$item['field']] = $item['value'];
//    }
//    return $result;
//}
//
//function getSectionGroupList($section_id, $parent_id = false, $limit = false, $offset = false)
//{
//    $u = new DB('app_section_groups', 'asg');
//    $u->select("asg.id, asg.id_parent, asg.name, asg.alias");
//    $u->where('asg.id_section=?', $section_id);
//    if ($parent_id)
//        $u->andWhere('asg.id_parent=?', $parent_id);
//    else
//        $u->andWhere('asg.id_parent IS NULL');
//
//    $u->orderBy('sort', false);
//    $count = $u->getListCount();
//    $items = $u->getList();
//    return array('count'=>$count, 'items' => $items);
//}
//
///**
// * @param $section_id - ID раздела
// * @param array $params - параметры поиска array('search' = array(field =>'', sign => 'IN', value => ''), groups = array(1, 2))
// * @param bool $limit
// * @param bool $offset
// * @return array
// */
//function getSectionCollectionList($section_id, $params = array(), $limit = false, $offset = false)
//{
//    $fieldsString = array('url','title', 'image', 'note', 'name', 'date_public', 'seo_keywords', 'seo_description', 'all');
//    $fieldsInt = array('id', 'id_parent', 'id_section');
//    $parametrs = getSectionParametrList($section_id);
//    $u = new DB('app_section_collection', 'asc');
//    $u->select('`asc`.*, GROUP_CONCAT(ascg.id_group) AS groups');
//    $u->leftJoin('app_section_collection_group ascg', '`asc`.id=ascg.id_collection');
//	$u->leftJoin('app_section_collection_values ascv', '`asc`.id=ascv.id_collection');
//
//    $u->groupBy('`asc`.id');
//    $u->orderBy('`asc`.sort', 0);
//    $u->where('asc.id_section=?', $section_id);
//    if (!empty($params['search'])) {
//        if (in_array($params['field'], $fieldsString)) {
//            $value =  (is_array($params['search']['value'])) ? implode("','", $params['search']['value']) : $params['search']['value'];
//            $u->andWhere("`ascg`.`{$params['search']['field']}` {{$params['search']['sign']}} ('?')", $value);
//        } else
//        if (in_array($params['field'], $fieldsInt)) {
//            $value =  (is_array($params['search']['value'])) ? implode(',', $params['search']['value']) : $params['search']['value'];
//            $u->andWhere("`ascg`.`{$params['search']['field']}` {{$params['search']['sign']}} ('?')", $value);
//        }
//        // Проверить в переменных
//    }
//    if (!empty($params['groups'])) {
//        $u->where('ascg.id_group IN (?)', join(',', $params['groups']));
//    }
//
//    $count = $u->getListCount();
//    $items = array();
//    foreach($u->getList($limit, $offset) as $item) {
//        $item['groups'] = explode(',', $item['groups']);
//        $item['image_prev'] = $item['image'];
//        if ($item['image']) {
//            if (!empty($parametrs['image-prev-size']) && strpos($item['image'], '//') === false) {
//                $item['image_prev'] = se_getDImage(WWW_DIR . '/' . ACTIVE_APP . '/' . $item['image'], $parametrs['image-prev-size'], 'm');
//            }
//            if (!empty($parametrs['image-size']) && strpos($item['image'], '//') === false) {
//                $t = (strpos($parametrs['image-size'], 'x')!==false) ? 's' : 'm';
//                $item['image'] = se_getDImage(WWW_DIR . '/' . ACTIVE_APP . '/' . $item['image'], $parametrs['image-size'], $t);
//            }
//        }
//        $items[] = $item;
//    }
//
//    return array('count'=>$count, 'items' => $items);
//}
//
//function getSectionCollectionValues($idSection, $collection = false, $field = false)
//{
//	$u = new DB('app_section_collection_values', 'ascv');
//	$u->select('ascv.id, ascv.id_collection, `asf`.code AS field, ascv.value, asf.defvalue');
//	$u->innerJoin('app_section_fields asf', '`ascv`.id_field=asf.id');
//	$u->where('asf.id_section=?', $idSection);
//	if ($collection) {
//		$u->andWhere('ascv.id_collection IN (?)', join(',', $collection));
//	}
//	if ($field) {
//		$u->andWhere("asf.field='?'", $field);
//	}
//	$items = $u->getList();
//	return array('count'=>count($items), 'items'=>$items);
//}