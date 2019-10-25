<?php

class Tree
{
	private static $instance = null;
	private $cache_dir = ''; 
	private $cache_groups;
	private $cache_tree;
	private $cache_count;
	private $groups = array();
	private $tree = array();
	private $count = 0;
	private $treepath = array();
	

    public function __construct()
    {
		$this->cache_dir = CACHE_DIR . '/groups/';
		$this->cache_groups = $this->cache_dir . 'groups.json';
		$this->cache_tree = $this->cache_dir . 'tree.json';
		$this->cache_count = $this->cache_dir . 'count.txt';
		
		if (!is_dir($this->cache_dir)) {      
			mkdir($this->cache_dir);				
		} 
        
        $this->updateGroupTable();

		if (!$this->cacheActual()) {
			$this->fetchFromDB();
		}
		else {
			$this->fetchFromCache();
		}
    }
	
	public function updateGroupTable()
	{
		
		if (!file_exists(CACHE_DIR . '/app_section_group_tree.upd')) {
			$sql = "CREATE TABLE IF NOT EXISTS app_section_group_tree (
				id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				id_parent int(10) UNSIGNED NOT NULL,
				id_child int(10) UNSIGNED NOT NULL,
				level tinyint(4) NOT NULL,
				updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
				created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE INDEX UK_app_section_group_tree (id_parent, id_child),
				CONSTRAINT FK_group_tree_category_id_child FOREIGN KEY (id_child)
				REFERENCES app_section_groups (id) ON DELETE CASCADE ON UPDATE CASCADE,
				CONSTRAINT FK_group_tree_category_id_parent FOREIGN KEY (id_parent)
				REFERENCES app_section_groups (id) ON DELETE CASCADE ON UPDATE RESTRICT
				)
				ENGINE = INNODB
				CHARACTER SET utf8
				COLLATE utf8_general_ci;";
			
			DB::query($sql);
			
			file_put_contents(CACHE_DIR . '/app_section_group_tree.upd', date('Y-m-d H:i:s'));
			
		}
	}
	
	private function fillTreeFromParentId()
	{
		$tree = array();
		$t = new DB('app_section_groups');
		$t->select('id_parent, id');
		
		$list = $t->getList();
		
		foreach ($list as $val){
			$tree[(int)$val['idParent']][] = $val['id']; 
		}

		$records = $this->getTreeRecords($tree);        
		
		if ($records ) {
			DB::query('TRUNCATE TABLE app_section_group_tree');
			DB::insertList('app_section_group_tree', $records);
		}
	}
	
	private function getTreeRecords($tree , $parent = 0, $level = 0)
	{
        $data = array();
	    if ($level == 0) {
			$this->treepath = array();
		} else 
			$this->treepath[$level] = $parent;
			
		foreach($tree[$parent] as $id) {
			$data[] = array('id_parent'=>$id, 'id_child'=>$id, 'level'=>$level);
		    if ($level > 0)
			for ($l=1; $l <= $level; $l++){
					$data[] = array('id_parent'=>$this->treepath[$l], 'id_child'=>$id, 'level'=>$level);
			}
			if (!empty($tree[$id])) {
				$data = array_merge ($data, $this->getTreeRecords($tree , $id, $level + 1));
			}
		}
		return $data;
	}
	
	private function fillParentIdFromTree()
	{
		return false;
        if (!file_exists(CACHE_DIR . '/fill_parent_id_group.upd')) {
			
			$records = $this->getParentIdRecords();
			
			if (!empty($records)) {
				DB::insertList('shop_group', $records, 'id', true);
				file_put_contents(CACHE_DIR . '/fill_parent_id_group.upd', print_r($records, 1));
			}
		}
	}
	
	private function getParentIdRecords($parent_id = 0, $level = 0)
	{
		$t = new DB('app_section_groups', 'c');
		$t->select('c.id');
		$t->innerJoin('app_section_group_tree ct', 'c.id = ct.id_child');
		$t->where('ct.level = ?', $level);
		if ($parent_id) {
			$t->andWhere('ct.id_parent = ?', $parent_id);
		} 
		
		$records = array();
		
		if ($list = $t->getList()) {
			foreach ($list as $val) {
				$records[$val['id']] = array(
					'id' => $val['id'],
					'id_parent' => $parent_id
				);
				$records += $this->getParentIdRecords($val['id'], $level+1);
			}
		}
		
		return $records;
	}

    private function fetchFromDB()
    {
        $this->fillTreeFromParentId();
        $this->tree = $this->parseTreeGroups();
        $this->checkCount($this->tree);
        $this->saveCache();
    }
	
	private function fetchFromCache()
    {
		$this->groups = json_decode(file_get_contents($this->cache_groups), 1);
		$this->tree = json_decode(file_get_contents($this->cache_tree), 1);
	}
	
	private function checkCount(&$tree = array())
    {
		foreach ($tree as &$val) {
			$val['count'] = $this->groups[$val['id']]['count'];
			if (!empty($val['list']))
				$this->checkCount($val['list']);
		}
	}
	
	private function saveCache()
    {
		$file = fopen($this->cache_groups, "w+");
		fwrite($file, json_encode($this->groups));
		fclose($file);
		
		$file = fopen($this->cache_tree, "w+");
		fwrite($file, json_encode($this->tree));
		fclose($file);
		
		$file = fopen($this->cache_count, "w+");
		fwrite($file, $this->count);
		fclose($file);
		
	}
	
	private function cacheActual()
    {		
        $t = new DB('app_section_groups', 'c');
        $t->select('COUNT(*) AS count, UNIX_TIMESTAMP(GREATEST(MAX(c.updated_at), MAX(c.created_at))) AS time');
        
		list($this->count, $time) = $t->fetchOne();
		
		$cache_count = file_exists($this->cache_count) ? (int)file_get_contents($this->cache_count) : -1;
		
		$time = max(filemtime(__FILE__), $time);
		
		if (!file_exists($this->cache_groups) || !file_exists($this->cache_tree) || filemtime($this->cache_groups) < $time || $cache_count != $this->count) {
			return false;
		}
		else {
			return true;
		}
	}
	
	public function getTree($code = '')
    {
		$id = $this->getId($code);
		if (!empty($this->groups[$id])) {
			$parents = $this->getParentsId($id, true);
			if (!empty($parents)) {
				$tree = $this->tree;
				while(!empty($parents)) {
					$id = array_pop($parents);
					$tree = $tree[$id]['list'];
				}
			}
			else {
				$tree = $this->tree[$id]['list'];
			}
			return $tree;
		}
			
		return $this->tree;
	}
	
	
	public function getId($key = 0)
    {
		if (is_string($key) && !empty($key)) {
			if (!empty($this->groups['cat_' . $key]))
				$key = (int)$this->groups['cat_' . $key];
			else
				$key = 0;
		}
		else {
			$key = (int)$key;
		}
			
		return $key;
	}
	
	public function getAllGroups()
    {
		return $this->groups;
	}
	
	public function getGroup($id)
    {
		$id = $this->getId($id);
		if (empty($this->groups[$id])) return null;
		return $this->groups[$id];
	}
	
	public function getGroupId($code = '')
    {
		$cat = $code;
		$id = $this->getId((string)$cat);
		if (isset($this->groups[$id])) {
			return $id;
		}
		return null;
	}
	
	public function getChildrensId($id, $add_current = true)
    {
		$id = $this->getId($id);
		if (empty($this->groups[$id])) return null;
		$childrens = $this->groups[$id]['childrens'];
		if ($add_current)
			array_push($childrens, $id);
		return $childrens;
	}
	
	public function getChildrens($id, $add_current = true)
    {
		$childrens_id = $this->getChildrensId($id, $add_current);
		if (empty($childrens_id)) return null;
		$groups = array();
		foreach ($childrens_id as $val) {
			$groups[] = $this->groups[$val];
		}
		return $groups;
	}
	
	public function getChildsId($id, $add_current = false)
    {
		$id = $this->getId($id);
		if (empty($this->groups[$id])) return null;
		$childs = $this->groups[$id]['children'];
		if ($add_current)
			array_push($childs, $id);
		return $childs;
	}
	
	public function getChilds($id, $add_current = false)
    {
		$childs_id = $this->getChildsId($id, $add_current);
		if (empty($childs_id)) return null;
		$groups = array();
		foreach ($childs_id as $val) {
			$groups[] = $this->groups[$val];
		}
		return $groups;
	}
	
	public function getParentsId($id, $add_current = false)
    {
		$id = $this->getId($id);
		if (empty($this->groups[$id])) return null;
		$parents = $this->groups[$id]['parents'];
		if ($add_current)
			array_unshift($parents, $id);
		return $parents;
	}
	
	public function getParents($id, $add_current = false)
    {
		$parents_id = $this->getParentsId($id, $add_current);
		if (empty($parents_id)) return null;
		$groups = array();
		foreach ($parents_id as $val) {
			$groups[] = $this->groups[$val];
		}
		return $groups;
	}
	
	public function getSiblingsId($id)
    {
		$id = $this->getId($id);
		if (!empty($this->groups[$id]['parent'])) {
			$parent_id = $this->groups[$id]['parent'];
			$id_list = $this->groups[$parent_id]['children'];
		}
		elseif (!empty($this->groups[$id])) {
			$id_list = $this->getMainGroupsId();
		}
		else
			return null;
		return $id_list;
	}

	public function getSiblings($id)
    {
		$siblings_id = $this->getSiblingsId($id);
		if (empty($siblings_id)) return null;
		$groups = array();
		foreach ($siblings_id as $val) {
			$groups[] = $this->groups[$val];
		}
		return $groups;
	}

	public function getMainGroupsId()
    {
		$list = array();
		foreach ($this->tree as $val) {
			$list[] = $val['id'];
		}
		return $list;
	}
	
	public function getMainGroups()
    {
		$groups_id = $this->getMainGroupsId();
		if (empty($groups_id)) return null;
		$groups = array();
		foreach ($groups_id as $val) {
			$groups[] = $this->groups[$val];
		}
		return $groups;
	}

	private function parseTreeGroups($upid = 0, $level = 0)
    {
        $groups = array();
		$t = new DB('app_section_groups', 'c');
		$t->select('c.id, c.code, c.id_section, ct.name, ct.note, ct.page_title, ct.meta_title, ct.meta_keywords, ct.meta_description');
		$t->leftJoin('app_section_groups_translate ct', 'c.id = ct.id_group');
		if ($upid) {
			$t->andWhere('c.id_parent = ?', $upid);
		}
        else {
            $t->andWhere('c.id_parent = 0 OR c.id_parent IS NULL');
        }
		$t->orderBy('c.sort');
        $t->groupBy('c.id');
		$list = $t->getList();
		
		if (!empty($list)) {
			foreach ($list as $val) {
				$this->groups['cat_' . $val['code']] = $val['id'];
				$this->groups[(int)$val['id']] = array(
					'id' => (int)$val['id'],
                    'id_section' => (int)$val['idSection'],
					'name' => $val['name'],
                    'level' => $level,
					'page_title' => $val['pageTitle'],
					'meta_title' => $val['metaTitle'],
					'meta_keywords' => $val['metaKeywords'],
					'meta_description' => $val['metaDescription'],
					'note' => $val['note'],
					'children' => array(),
					'childrens' => array(),
					'parent' => $upid,
					'parents' => array(),
				);
				if (!empty($upid)) {
					$this->groups[$upid]['children'][] = $val['id'];
					$this->groups[$val['id']]['parents'] = $this->groups[$upid]['parents'];
					array_unshift($this->groups[$val['id']]['parents'], $upid);
					$this->recursiveChildren($upid, $val['id']);
				}
				$groups[$val['id']] = array(
					'id' => $val['id'],
					'name' => $val['name'],
                    'code' => $val['code'],
					'list' => $this->parseTreeGroups($val['id'], $level+1),
				);
			}
		}
		return $groups;
	}

	private function recursiveChildren($parent, $child)
    {
		$this->groups[$parent]['childrens'][] = $child;
		$this->groups[$parent]['count'] += $this->groups[$child]['count'];
		if (!empty($this->groups[$parent]['parent']) && $this->groups[$parent]['parent'] != $parent) {
			$this->recursiveChildren($this->groups[$parent]['parent'], $child);
		}
	}

	public static function GI() 
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}