<?php


class plugin_mil_data {

    private $idRegion;
    private $idType;

    // idType (1 - Проживание, 2 - Питание, 3 - развлечение, 4 - оружие, 5 - транспорт, 6 - другие)
    public function __construct($idRegion=false, $idType=false)
    {
        $this->idRegion = $idRegion;
        $this->idType = $idType;
    }

    public function getList($idParent = false, $limit = 0, $offset = 0)
    {
        $mtd = new DB('mil_data', 'md');
        $mtd->select("md.*, GROUP_CONCAT(md1.id) AS `items`");
        $mtd->where('md.id_region=?', intval($this->idRegion));
        $mtd->leftJoin('mil_data md1', 'md1.id_parent=md.id');
        $mtd->andWhere('md.id_type=?', intval($this->idType));
        if ($idParent) {
            $mtd->andWhere('md.id_parent=?', intval($idParent));
        } else {
            $mtd->andWhere('md.id_parent IS NULL');
        }
        $mtd->groupBy('md.id');
        $mtd->orderBy('md.sort', 0);
        $list = $mtd->getList($limit, $offset);
        foreach ($list as &$item) {
            $item['items'] = (!empty($item['items'])) ? explode(',', $item['items']) : array();
            $item['data'] = json_decode($item['data'], true);
        }
        return $list;
    }

    public function getData($id = false)
    {
        if (!$id) return array();
        $mtd = new DB('mil_data', 'md');
        $mtd->select("md.*, GROUP_CONCAT(md1.id) AS `items`");
        $mtd->leftJoin('mil_data md1', 'md1.id_parent=md.id');
        $mtd->where('md.id=?', intval($id));
        $item = $mtd->fetchOne();
        $item['items'] = (!empty($item['items'])) ? explode(',', $item['items']) : array();
        $item['data'] = json_decode($item['data'], true);
        return $item;
    }

    public function setData($data = array(), $idParent = false)
    {
        if ($idParent) $data['idParent'] = $idParent;
        $data['idRegion'] = $this->idRegion;
        $data['idType'] = $this->idType;
        $data['data'] = json_encode($data['data']);
        $mtd = new DB('mil_data');
        $mtd->setValuesFields($data);
        return $mtd->save();
    }

    public function delete($ids)
    {
        $mtd = new DB('mil_data');
        $mtd->where('id IN (?)', join(',', $ids));
        return $mtd->deleteList();
    }
}