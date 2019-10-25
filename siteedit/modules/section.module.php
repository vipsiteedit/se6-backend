<?php

// Функции разделов

class section
{

    //rivate $apps;
    private $alias;
    private $params;
    private $section = array();
    private $paramers = array();
    private $groups = array();
    private $idLang = false;

    private $default_image_size = '800';
    private $default_prev_image_size = '250x250';

    public function __construct($alias, $params = array())
    {
        $this->alias = $alias;
        $this->params = $params;
        $this->idLang = intval($_SESSION['SE_APP_LANG']) ? intval($_SESSION['SE_APP_LANG']) : 0;
        if ($alias) {
            $u = new DB('app_section', 'as');
            $u->select('as.id, as.typename, ast.title, ast.description, ap.name AS access_group, 
                ap.level AS access_level, as.visible, au.pattern, au1.pattern AS pattern_group');
            $u->leftJoin('app_section_translate `ast`', "(ast.id_section=as.id AND ast.id_lang={$this->idLang})");
            $u->leftJoin('app_section_permission asp', 'as.id=asp.id_section');
            $u->leftJoin('app_permission ap', 'asp.id_permission=ap.id');
            $u->leftJoin('app_urls au', "as.id=au.id_section AND au.type='item'");
            $u->leftJoin('app_urls au1', "as.id=au1.id_section AND au1.type='group'");
            $u->where("as.alias='?'", $alias);
            $this->section = $u->fetchOne();
            $this->paramers = $this->getParametrList();
            $this->groups = $this->getSectionGroup();

        } else {
            $this->section['visible'] = true;
        }

        if (empty($this->paramers['image-size'])) {
            $this->paramers['image-size'] = $this->default_image_size;
        }

        if (empty($this->paramers['image-prev-size'])) {
            $this->paramers['image-prev-size'] = $this->default_prev_image_size;
        }


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
        $u->select('ac.id, ac.url, ac.code, act.name, act.note, act.page_title, act.meta_title, act.meta_keywords, act.meta_description');
        $u->leftJoin('app_section_collection_translate act', "act.id_collection=ac.id AND act.id_lang={$this->idLang}");
        $u->where('ac.id_section=?', $this->section['id']);
        $count = $u->getListCount();
        return array('count' => $count, 'items' => $u->getList($limit, $offset));
    }

    public function getShortItemCollection($arg)
    {
        $u = new DB('app_section_collection', 'asc');
        $u->select('`asc`.id, `act`.name, `asc`.code, GROUP_CONCAT(DISTINCT ascg.id_group) AS groups');
        $u->leftJoin('app_section_collection_group ascg', '`asc`.id=ascg.id_collection');
        $u->leftJoin('app_section_collection_translate act', "act.id_collection=`asc`.id AND (act.id_lang IS NULL OR act.id_lang={$this->idLang})");
        if (!empty($arg['code']))
            $u->where("`asc`.code LIKE '?'", $arg['code']);
        else
            $u->where("`asc`.id = ?", intval($arg['id']));
        return $u->fetchOne();
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
        foreach ($plist as $item) {
            $result[$item['field']] = $item['value'];
        }
        return $result;
    }

    private function fetchSectionFields()
    {
        return array();
    }

    public function getCollectionList($idSection = false, $params = array(), $limit = false, $offset = false)
    {
        if (!$this->isVisible()) return array();
        $idSection = intval(($idSection) ? $idSection : $this->section['id']);
        $fieldsLang = array('name', 'note', 'page_title', 'meta_title', 'meta_keywords', 'meta_description');
        $fieldsString = array('url', 'code', 'date_public', 'all');
        $fieldsInt = array('id', 'id_parent', 'id_section');

        $sortField = '`asc`.sort';

        $sortDirection = 'asc';

        if (!empty($params['sort']['field']) && in_array($params['sort']['field'], array_merge($fieldsString, $fieldsInt, $fieldsLang))) {
            if (in_array($params['sort']['field'], array_merge($fieldsString, $fieldsInt)))
                $sortField = '`asc`.';
            else $sortField = '`act`.';

            $sortField .= $params['sort']['field'];
        }

        if (!empty($params['sort']['direction']) && in_array($params['sort']['direction'], array('asc', 'desc'))) {
            $sortDirection = $params['sort']['direction'];
        }

        $u = new DB('app_section_collection', 'asc');
        //$u->addField('visits','int(10)', '0');
        $u->select('`asc`.id,  `asc`.url, `asc`.code, `asc`.date_public, act.name, act.note, 
            (SELECT CONCAT(IFNULL(CONCAT(aif.name, "/"), \'\'), ai.name) FROM app_section_collection_image AS `asci` 
            INNER JOIN app_image AS `ai` ON `asci`.id_image=`ai`.id LEFT JOIN app_image_folder AS `aif` ON `ai`.id_folder=`aif`.id 
            WHERE `asci`.id_collection=`asc`.id AND `asci`.is_main LIMIT 1) AS image,  
            GROUP_CONCAT(DISTINCT ascg.id_group) AS groups');
        $u->leftJoin('app_section_collection_translate act', "act.id_collection=`asc`.id AND (act.id_lang IS NULL OR act.id_lang={$this->idLang})");
        $u->leftJoin('app_section_collection_values ascv', '`asc`.id=ascv.id_collection');

        // Рейтинг
        //$u->leftJoin('app_section_collection_reviews ascr', '`asc`.id=ascr.id_collection');
        //$u->leftJoin('app_section_collection_comments ascc', '`asc`.id=ascc.id_collection');

        $u->groupBy('`asc`.id');
        $u->orderBy($sortField, $sortDirection == 'desc');
        $u->where('`asc`.id_section=?', $idSection);
        if (!empty($params['search'])) {
            foreach ($params['search'] as $search) {
                if (empty($search['sign'])) $search['sign'] = 'IN';
                $fields = (strpos($search['field'], ',') !== false) ? explode(',', $search['field']) : array($search['field']);
                $or = array();
                foreach ($fields as $field) {
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

        $filter_object = array(
            'groups' => array('alias' => '`asg`', 'fields' => array('id', 'code')),
            'collections' => array('alias' => '`asc`', 'fields' => array('id', 'code', 'name', 'url', 'note', 'page_title', 'meta_title', 'meta_keywords', 'meta_description')),
        );

        $tree = Tree::GI();

        if (!empty($params['filter'])) {
            foreach ($params['filter'] as $object => $item) {
                if ($object == 'collections' && !empty($item)) {
                    $filter = $filter_object[$object];
                    foreach ($item as $field => $value) {
                        if (in_array($field, $filter['fields'])) {
                            $sign = '= "?"';
                            if (is_array($value)) {
                                $value = '"' . join('","', $value) . '"';
                                $sign = 'IN (?)';
                            }
                            $u->andWhere($filter['alias'] . '.' . $field . ' ' . $sign, $value);
                        }
                    }
                } elseif ($object == 'groups' && !empty($item) && !isset($params['group'])) {
                    $filter = $filter_object[$object];
                    foreach ($item as $field => $value) {
                        if (is_array($value)) {
                            foreach ($value as $val) {
                                $params['group'][] = $tree->getId($val);
                            }
                        }
                        else {
                            $params['group'][] = $tree->getId($value);
                        }
                    }
                } elseif ($object == 'fields' && !empty($item)) {
                    $fields = $this->fetchSectionFields();
                    foreach ($item as $field => $value) {
                        //
                    }
                }
            }
        }

        if ($params['group']) {
            $u->innerJoin('app_section_collection_group ascg', '`asc`.id=ascg.id_collection');
            if ($params['groupTree']) {
                $u->innerJoin('app_section_group_tree asgt', 'ascg.id_group=asgt.id_child');
                $field = 'asgt.id_parent';
            }
            else {
                $field = 'ascg.id_group';
            }
            if (is_array($params['group'])) {
                $u->andWhere($field . ' IN (?)', join(',', $params['group']));
            }
            else {
                $u->andWhere($field . '=?', $params['group']);
            }
        }
        else {
            $u->leftJoin('app_section_collection_group ascg', '`asc`.id=ascg.id_collection');
        }
        $u->leftJoin('app_section_groups asg', '`asg`.id=ascg.id_group');

        $count = $u->getListCount();

        $sql = $u->getSql();

        $items = array();

        if ($params['pagination']) {
            if (!$limit) {
                $limit = 10;
            }
            $pagination = $this->getPagination($limit, $count);

            $offset = $pagination['offset'];
        }

        foreach ($u->getList($limit, $offset) as $item) {
            $item['url'] = ($item['url']) ? $item['url'] : convert_pattern_url($this->section['pattern'], $item);
            $item['groups'] = explode(',', $item['groups']);
            $item['imagePrev'] = $item['image'];
            if ($item['image']) {
                if (!empty($this->paramers['image-prev-size']) && strpos($item['image'], '//') === false) {
                    $item['imagePrev'] = se_getDImage(IMAGES_DIR . '/' . $item['image'], $this->paramers['image-prev-size'], 'm');
                }

                if (!empty($this->paramers['image-size']) && strpos($item['image'], '//') === false) {
                    $t = (strpos($this->paramers['image-size'], 'x') !== false) ? 's' : 'm';
                    $item['image'] = se_getDImage(IMAGES_DIR . '/' . $item['image'], $this->paramers['image-size'], $t);
                }
            }
            $items[] = $item;
        }
        $collects = array();
        foreach ($items as $rec) {
            $collects[] = $rec['id'];
        }
        $fields = $this->getCollectionValues($collects);
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

        return array('count' => $count, 'items' => $items, 'pagination' => $pagination);
    }


    private function getPagination($limit = 10, $count = 0)
    {

        if (!($limit > 0)) {
            return;
        }
        $countPages = ceil($count / $limit);

        $page = max($_GET['page'], 1);

        if ($page > $countPages && $page > 1) {
            App::getEngine()->go301('/page404');
        }

        $offset = ($page - 1) * $limit;

        //$pagination = se_Navigator($count, $limit, $page);

        $nav = array();

        if ($countPages > 1) {
            if ($page == 1) {
                $nav['prev'] = array('value' => 'prev');
            } else {
                if ($page != 2)
                    $params['page'] = $page - 1;
                $nav['prev'] = array('value' => 'prev', 'link' => '?' . http_build_query($params));
            }

            $cnpw = 9;
            $in = 1;
            $ik = $countPages;
            if ($countPages > $cnpw) {
                $in = $page - floor($cnpw / 2);
                $ik = $page + floor($cnpw / 2);
                if ($in <= 1) {
                    $in = 1;
                    $ik = $page + ($cnpw - $page);
                }

                if ($ik > $countPages) {
                    $in = $page - (($cnpw - 1) - ($countPages - $page));
                    $ik = $countPages;
                }
                if ($in > 1) {
                    $in = $in + 2;
                    unset($params['page']);
                    $nav[] = array('value' => '1', 'link' => '?');
                    $nav[] = array('value' => '...');
                }
                if ($ik < $countPages) {
                    $params['page'] = $countPages;
                    $ik = $ik - 2;
                    $r_nav[] = array('value' => '...');
                    $r_nav[] = array('value' => $countPages, 'link' => '?' . http_build_query($params));

                }
            }

            for ($i = $in; $i <= $ik; $i++) {
                unset($params['page']);
                if ($i == $page)
                    $nav[] = array('value' => $i, 'active' => true);
                else {
                    if ($i != 1) {
                        $params['page'] = $i;
                        $nav[] = array('value' => $i, 'link' => '?' . http_build_query($params));
                    } else {
                        $nav[] = array('value' => $i, 'link' => '?' . http_build_query($params));
                    }
                }
            }

            if (!empty($r_nav))
                $nav = array_merge($nav, $r_nav);

            if ($page == $countPages) {
                $nav['next'] = array('value' => 'next');
            } else {
                $params['page'] = $page + 1;
                $nav['next'] = array('value' => 'next', 'link' => '?' . http_build_query($params));
            }
        }

        return array(
            'list' => $nav,
            'next' => ($page + 1) > $countPages ? 0 : $page + 1,
            'prev' => $page - 1,
            'current' => $page,
            'count' => $countPages,
            'offset' => $offset,
        );

    }

    public function getCollectionListId($idSection = false, $params = array(), $limit = false, $offset = false)
    {
        if (!$this->isVisible()) return array();
        $fieldsString = array('url', 'code', 'date_public', 'all');
        $fieldsInt = array('id', 'id_parent', 'id_section');
        $idSection = intval(($idSection) ? $idSection : $this->section['id']);
        $u = new DB('app_section_collection', 'asc');
        //$u->addField('visits','int(10)', '0');
        $u->select('`asc`.id, `asc`.code');
        $u->leftJoin('app_section_collection_group ascg', '`asc`.id=ascg.id_collection');
        $u->groupBy('`asc`.id');
        $u->orderBy('`asc`.sort', 0);
        $u->where('asc.id_section=?', $idSection);
        if (!empty($params['search'])) {
            foreach ($params['search'] as $search) {
                if (empty($search['sign'])) $search['sign'] = 'IN';
                $fields = (strpos($search['field'], ',') !== false) ? explode(',', $search['field']) : array($search['field']);
                $or = array();
                foreach ($fields as $field) {
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

        return array('count' => $count, 'items' => $items);
    }

    public function getCollectionItems($idCollections = array(), $isValues = false)
    {
        if (empty($idCollections)) return array();
        if (!$this->isVisible()) return array();
        $u = new DB('app_section_collection', 'asc');
        //$u->addField('visits','int(10)', '0');
        $u->select('`asc`.id, `asc`.code, ast.name, ast.note, ast.page_title,
                `au`.pattern,(SELECT CONCAT(IFNULL(CONCAT(aif.name, "/"), \'\'), ai.name) 
                 FROM app_section_collection_image AS `asci` 
                 INNER JOIN app_image AS `ai` ON `asci`.id_image=`ai`.id 
                 LEFT JOIN app_image_folder AS `aif` ON `ai`.id_folder=`aif`.id 
                 WHERE `asci`.id_collection=`asc`.id AND `asci`.is_main LIMIT 1) AS image, 
                 GROUP_CONCAT(DISTINCT ascg.id_group) AS groups,
                 GROUP_CONCAT(DISTINCT ascs.id_item) AS similars,
        ROUND(IFNULL((SUM(ascr.mark)/COUNT(ascr.id)),0)) AS rating,
        COUNT(ascc.id) commentsCount');
        $u->leftJoin('app_urls `au`', '`au`.id_section=`asc`.id_section');
        $u->leftJoin('app_section_collection_translate ast', '`asc`.id=ast.id_collection AND ast.id_lang=' . $this->idLang);
        $u->leftJoin('app_section_collection_group ascg', '`asc`.id=ascg.id_collection');
        $u->leftJoin('app_section_collection_values ascv', '`asc`.id=ascv.id_collection');
        $u->leftJoin('app_section_collection_translate act', "`act`.id_collection=`asc`.id AND (act.id_lang IS NULL OR act.id_lang={$this->idLang})");

        // Рейтинг
        $u->leftJoin('app_section_collection_reviews ascr', '`asc`.id=ascr.id_collection');

        $u->leftJoin('app_section_collection_comments ascc', '`asc`.id=ascc.id_collection');
        $u->leftJoin('app_section_collection_similar ascs', '`asc`.id=ascs.id_collection');
        $u->where('`asc`.id IN (?)', join(',', $idCollections));
        $u->groupBy('`asc`.id');

        $items = array();
        foreach ($u->getList() as $item) {
            $item['url'] = ($item['url']) ? $item['url'] : convert_pattern_url($item['pattern'], $item);
            $item['groups'] = explode(',', $item['groups']);
            $item['similars'] = (!empty($item['similars'])) ? explode(',', $item['similars']) : array();
            $item['imagePrev'] = $item['image'];
            if ($item['image']) {
                if (!empty($this->paramers['image-prev-size']) && strpos($item['image'], '//') === false) {
                    $item['imagePrev'] = se_getDImage(IMAGES_DIR . '/' . $item['image'], $this->paramers['image-prev-size'], 'm');
                }

                if (!empty($this->paramers['image-size']) && strpos($item['image'], '//') === false) {
                    $t = (strpos($this->paramers['image-size'], 'x') !== false) ? 's' : 'm';
                    $item['image'] = se_getDImage(IMAGES_DIR . '/' . $item['image'], $this->paramers['image-size'], $t);
                }
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

    public function getCollectionImages($idCollection, $params = array())
    {
        if (empty($params['small-size'])) $params['small-size'] = '100x100';
        if (empty($params['big-size'])) $params['big-size'] = '1024';

        $result = array();
        if (!$idCollection) return array();
        $u = new DB('app_section_collection_image', 'si');
        $u->select('si.id, IF(f.name IS NOT NULL, CONCAT(f.name, "/", img.name), img.name) image,
        img.name image_name, si.is_main, ait.alt, ait.title, si.sort');
        $u->leftJoin("app_section_collection_image_translate ait", "si.id = ait.id_image AND ait.id_lang=" . intval($this->input['idLang']));
        $u->innerJoin("app_image img", "img.id = si.id_image");
        $u->leftJoin("app_image_folder f", "f.id = img.id_folder");
        $u->where('si.id_collection = ?', intval($idCollection));
        $u->groupBy('si.id');
        $u->orderBy("si.sort", 0);
        $list = $u->getList();

        foreach ($list as $item) {
            $t1 = (strpos($params['small-size'], 'x') !== false) ? 's' : 'm';
            $t2 = (strpos($params['big-size'], 'x') !== false) ? 's' : 'm';
            $result[] = array(
                'id' => $item['id'],
                'small' => se_getDImage(IMAGES_DIR . '/' . $item['image'], $params['small-size'], $t1),
                'big' => se_getDImage(IMAGES_DIR . '/' . $item['image'], $params['big-size'], $t2),
                'title' => $item['title'],
                'alt' => $item['alt']
            );
        }
        return array('count' => count($result), 'items' => $result);
    }

    public function getGroupsImages($idCollection, $params = array())
    {
        if (empty($params['small-size'])) $params['small-size'] = '100x100';
        if (empty($params['big-size'])) $params['big-size'] = '1024';

        $result = array();
        if ($idCollection)
            return array();
        $u = new DB('app_section_groups_image', 'si');
        $u->select('si.id, IF(f.name IS NOT NULL, CONCAT(f.name, "/", img.name), img.name) image,
        img.name image_name, ait.alt, ait.title');
        $u->leftJoin("app_section_groups_image_translate ait", "si.id = ait.id_image AND ait.id_lang=" . intval($this->input['idLang']));
        $u->innerJoin("app_image img", "img.id = si.id_image");
        $u->leftJoin("app_image_folder f", "f.id = img.id_folder");
        $u->where('si.id_collection = ?', $idCollection);
        $u->orderBy("si.sort");
        $list = $u->getList();

        foreach ($list as $item) {
            $t1 = (strpos($params['small-size'], 'x') !== false) ? 's' : 'm';
            $t2 = (strpos($params['big-size'], 'x') !== false) ? 's' : 'm';
            $result[] = array(
                'id' => $item['id'],
                'small' => se_getDImage(IMAGES_DIR . '/' . $item['image'], $params['small-size'], $t1),
                'big' => se_getDImage(IMAGES_DIR . '/' . $item['image'], $params['big-size'], $t2),
                'title' => $item['title'],
                'alt' => $item['alt']
            );
        }
        return array('count' => count($result), 'items' => $result);
    }


    public function getCollectionFiles($idCollection, $idField = false)
    {
        $u = new DB('app_section_collection_files', 'si');
        $u->select('si.id, si.file, name AS title, description, icon');
        $u->leftJoin('se_user_file suf', 'si.id=suf.id_file');
        $u->where('si.id_collection = ?', $idCollection);
        if ($idField)
            $u->andwhere('si.id_field = ?', $idField);
        $u->orderBy("sort");
        $u->groupBy("si.id");
        $files = $u->getList();
        foreach ($files as &$file) {
            $file['url'] = $file['file'];
            if (strpos($file['file'], '.mp3')) {
                if (file_exists(VENDORS_CORE_DIR . '/MP3_Id/Id.php')) {
                    require_once VENDORS_CORE_DIR . '/MP3_Id/Id.php';
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
        return array('count' => count($files), 'items' => $files);

    }

    public function GetCollectionValues($collection = array(), $field = false)
    {
        if (!$this->isVisible()) return array();
        $u = new DB('app_fields', 'asf');
        $u->select('ascv.id, `ascv`.id_field, ascv.id_collection, `asf`.code AS field, acv.value, asf.defvalue, asf.type');
        $u->leftJoin('app_section_collection_values ascv', '`ascv`.id_field=asf.id');
        $u->leftJoin('app_section_collection_values_translate acv', "acv.id_values=`ascv`.id AND (acv.id_lang IS NULL OR acv.id_lang={$this->idLang})");
        //$u->where('asf.id_section=?', $this->section['id']);
        if ($collection) {
            if (is_array($collection))
                $u->Where('ascv.id_collection IN (?)', join(',', $collection));
            else
                $u->Where('ascv.id_collection = ?', intval($collection));
        }
        if ($field) {
            $u->andWhere("asf.code='?'", $field);
        }

        $items = $u->getList();
        return array('count' => count($items), 'items' => $items);
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
        $u->addField('visits', 'int(10)', '0');

        $u->select('`asc`.id, asc`.code, act.id_translate,
                `au`.pattern,(SELECT CONCAT(IFNULL(CONCAT(aif.name, "/"), \'\'), ai.name) 
                 FROM app_section_collection_image AS `asci` INNER 
                 JOIN app_image AS `ai` ON `asci`.id_image=`ai`.id 
                 LEFT JOIN app_image_folder AS `aif` ON `ai`.id_folder=`aif`.id 
                 WHERE `asci`.id_collection=`asc`.id AND `asci`.is_main LIMIT 1) AS image, 
                 GROUP_CONCAT(DISTINCT ascg.id_group) AS groups,
                 GROUP_CONCAT(DISTINCT ascs.id_item) AS similars,
        ROUND(IFNULL((SUM(ascr.mark)/COUNT(ascr.id)),0)) AS rating,
        COUNT(ascc.id) commentsCount');

        //$u->select('`asc`.*, GROUP_CONCAT(ascg.id_group) AS groups');

        $u->leftJoin('app_section_groups_translate `act`', "`asc`.id = `act`.id_collection AND `act`.id_lang={$this->idLang}");
        $u->leftJoin('app_urls `au`', '`au`.id_section=`asc`.id_section');
        $u->leftJoin('app_section_collection_group ascg', '`asc`.id=ascg.id_collection');
        $u->leftJoin('app_section_collection_values ascv', '`asc`.id=ascv.id_collection');
        $u->leftJoin('app_section_collection_reviews ascr', '`asc`.id=ascr.id_collection');
        $u->leftJoin('app_section_collection_comments ascc', '`asc`.id=ascc.id_collection');
        $u->leftJoin('app_section_collection_similar ascs', '`asc`.id=ascs.id_collection');
        $u->where('asc.id_section=?', $this->section['id']);
        $u->andWhere('asc.id = ?', intval($idCollection));
        $result = $u->fetchOne();
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
        $tree = Tree::GI();
        $id = $tree->getId($uri['code'] ? $uri['code'] : (int)$uri['id']);

        return $id;

        if (!$this->isVisible()) return null;
        //$idSection = ($idSection) ? $idSection : $this->section['id'];
        foreach ($uri as $key => $value) {
            if (!in_array($key, array('code', 'id'))) {
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
        foreach ($items as $item) {
            foreach ($arr as $name => $val) {
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
        foreach ($items as $item) {
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
        $u->select("asg.id, asg.id_parent, asg.code, act.name, act.note, act.page_title, act.meta_title, act.meta_description, act.meta_keywords, (SELECT CONCAT(IFNULL(CONCAT(aif.name, '/'), ''), ai.name) FROM app_section_groups_image AS `asgi`
              INNER JOIN app_image AS `ai` ON `asgi`.id_image=`ai`.id
              LEFT JOIN app_image_folder AS `aif` ON `ai`.id_folder=`aif`.id
              WHERE `asgi`.id_group=`asg`.id AND `asgi`.is_main LIMIT 1) AS image");
        $u->leftJoin('app_section_groups_translate `act`', "`asg`.id = `act`.id_group AND `act`.id_lang={$this->idLang}");
        $u->where('asg.id_section=?', $idSection);
        $u->orderBy('asg.sort', false);
        //echo $u->getSql();
        $items = $u->getList();

        $groups = array();
        foreach ($items as &$item) {
            $item['url'] = convert_pattern_url($this->section['patternGroup'], $item);
            $item['imagePrev'] = $item['image'];
            if ($item['image']) {
                if (!empty($this->paramers['image-prev-size']) && strpos($item['image'], '//') === false) {
                    $item['imagePrev'] = se_getDImage(IMAGES_DIR . '/' . $item['image'], $this->paramers['image-prev-size'], 'm');
                }

                if (!empty($this->paramers['image-size']) && strpos($item['image'], '//') === false) {
                    $t = (strpos($this->paramers['image-size'], 'x') !== false) ? 's' : 'm';
                    $item['image'] = se_getDImage(IMAGES_DIR . '/' . $item['image'], $this->paramers['image-size'], $t);
                }
            }
            $groups[intval($item['idParent'])][] = $item;
        }
        $items = $this->setTree($groups);
        return array('count' => count($items), 'items' => $items);
    }

    private function setTree($groups, $idParent = 0)
    {
        $items = array();
        if (!isset($groups[$idParent])) return array();
        foreach ($groups[$idParent] as $item) {
            if (!empty($groups[$item['id']])) {
                $item['items'] = $this->setTree($groups, $item['id']);
            }
            $items[] = $item;
        }
        return $items;
    }

    // Заливка списка записей
    public function setCollectionList($idSection = false, $input = array())
    {
        $idSection = intval(($idSection) ? $idSection : $this->section['id']);
        $fieldsLang = array('name', 'note', 'page_title', 'meta_title', 'meta_keywords', 'meta_description');
        $fieldsString = array('url', 'code', 'date_public', 'all');
        $fieldsInt = array('id', 'id_parent', 'id_section');

        //  Получим список доп-полей раздела
        $fields = $this->getSectionFields($idSection);

        // Получим список записей раздела по кодам
        $codearr = array();
        foreach($input['items'] as $item) {
            if (!empty($item['code'])) {
                $codearr[] = $item['code'];
            }
        }
        $codeIds = array();
        if (count($codearr)) {
            $u = new DB('app_section_collection', 'sc');
            $u->select('sc.id, sc.code, sct.id AS idt');
            $u->leftJoin('app_section_collection_translate `sct`', "`sc`.id = `ct`.id_collection AND `sct`.id_lang={$this->idLang}");
            $u->where('sc.id_section=?', $idSection);
            $u->andWhere("sc.code IN ('?')", join("','", $codearr));
            $codearr = array();
            foreach ($u->getList() as $code) {
                $codearr[$code['code']] = array('id' => $code['id'], 'idt'=>$code['idt']);
            }
        }


        $u = new DB('app_section_collection', 'asc');
        foreach($input['items'] as $item) {
            if (empty($item['id']) && !empty($codearr[$item['code']])) {
                $item['id'] = intval($codearr[$item['code']]['id']);
            }
            $u->setValuesFields($item);
            $idCollection = $u->save();

            // Добавляем в коллекцию транслитирацию
            if ($item['idLang']) {
                $ut = new DB('app_section_collection_translate', 'ast');
                $tti = array('idCollection'=>$idCollection, 'idLang'=>$this->idLang);
                if (isset($item['name'])) $tti['name'] = $item['name'];
                if (isset($item['note'])) $tti['note'] = $item['note'];
                if (isset($item['pageTitle'])) $tti['pageTitle'] = $item['pageTitle'];
                if (isset($item['metaTitle'])) $tti['metaTitle'] = $item['metaTitle'];
                if (isset($item['metaKeywords'])) $tti['metaKeywords'] = $item['metaKeywords'];
                if (isset($item['metaDescription'])) $tti['metaDescription'] = $item['metaDescription'];
                if (isset($item['metaTitle'])) $tti['metaTitle'] = $item['metaTitle'];
                if (!empty($codearr[$item['code']]['idt'])) $tti['id'] = intval($codearr[$item['code']]['idt']);
                $ut->setValuesFields($tti);
                $ut->save();
            }
            if (!empty($item['fields'])) {
                // Заполнение дополнительных полей
                $fieldat = $this->setCollectionValues($idCollection = false, $fields, $item['fields']);
            }
        }
    }

    private function getSectionFields($idSection)
    {
        $uf = new DB('app_fields_service', 'ufs');
        $uf->select('uf.code, uf.type');
        $uf->innerJoin('app_fields uf', '`ufs`.id=uf.id_service');
        $uf->where('ufs.id_section=?', $idSection);
        $fields = array();
        foreach($uf->getList() as $it) {
            $fields[$it['code']] = array('id'=>$it['type'], 'type'=> $it['type']);
        }
        return $fields;
    }

    private function setCollectionValues($idCollection = false, $fields = array(), $values = array())
    {
        $fieldat = array();
        foreach($values as $fld=>$val) {
            if (!empty($fields[$fld])) { // Проверка существования поля
                $av = new DB('app_section_collection_values', 'av');
                $dval = array('idCollection'=>$idCollection, 'idField'=>$fields[$fld]['id']);
                if (in_array($fields[$fld], array('integer'))) {
                    $dval['intvalue'] = $val;
                };
                $av->setValuesFields($dval);
                $idValue = $av->save();

                if (in_array($fields[$fld], array('string', 'text', 'edit'))) {
                    $at = new DB('app_section_collection_translate', 'ast');
                    $at->setValuesFields($dval);
                    $at->save();
                }
            }
        }
    }



}

function GetSectionElementEx($alias = '', $params = array())
{
    return new section($alias, $params);
}