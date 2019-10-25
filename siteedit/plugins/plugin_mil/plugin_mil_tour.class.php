<?php

class plugin_mil_tour {

    // Календарь туров
    public function getTourCalendar()
    {
        $mtd = new DB('mil_tour_days', 'mtd');
        $mtd->select("DATE_FORMAT(mtd.date_event, '%Y-%m') AS events");
        $mtd->innerJoin('mil_tours mt', 'mtd.id_tour=mt.id');
        $mtd->where('mtd.date_event>=CURDATE()');
        $mtd->orderBy('mtd.date_event', 0);
        $mtd->groupBy("DATE_FORMAT(mtd.date_event, '%Y%m')");
        return $mtd->getList();
    }

    // Список туров в определенном месяце
    public function getTourMonth($month = '2018-09', $idLang)
    {
        $mtd = new DB('mil_tour_days', 'mtd');
        $mtd->select("mt.id, mtt.name, mt.image, mtt.note, mtd.date_event");
        $mtd->innerJoin('mil_tours mt', 'mtd.id_tour=mt.id');
        $mtd->leftJoin('mil_tours_translate mtt', 'mtt.id_tour=mt.id');
        $mtd->where("DATE_FORMAT(mtd.date_event, '%Y-%m')='?'", $month);
        $mtd->andWhere('mtt.id_lang=?', intval($idLang));
        $mtd->orderBy('mtd.date_event', 0);
        $mtd->groupBy("mt.id");
        return $mtd->getList();
    }

    public function getTours($idRegion, $idLang = 2, $search = false, $filter = array())
    {
        $mtd = new DB('mil_tours', 'mt');
        $mtd->select("mt.id, mt.code, mt.date_event, mtt.name, mt.image, mtt.note, mtt.description, mt.is_show, 
          COUNT(mtd.id) AS days, GROUP_CONCAT(mtd.date_event) AS dates");
        $mtd->leftJoin('mil_tour_days mtd', 'mtd.id_tour=mt.id');
        $mtd->leftJoin('mil_tours_translate mtt', 'mtt.id_tour=mt.id');
        $mtd->where('mtt.id_lang=?', intval($idLang));
        if ($idRegion) {
            $mtd->andWhere('mt.id_region=?', intval($idRegion));
            //$mtd->andWhere('mt.is_show=1');
        }
        if ($search) {
            $mtd->andWhere("mt.name LIKE '%?%' OR mt.code LIKE '%?%')", $search);
        }
        if (!empty($filter)) {
            if (!empty($filter['datestart']))
            $mtd->andWhere("mt.date_event>'?'", $filter['datestart']);
            if (isset($filter['isShow']))
                $mtd->andWhere("mt.is_show='?'", (int) $filter['isShow']);
            $mtd->orderBy('mt.date_event', 0);
        } else {
            $mtd->orderBy('mt.date_event', 1);
        }


        $mtd->groupBy('mt.id');
        $list = $mtd->getList();
        foreach ($list as &$item) {
            $item['dateEvent'] = date('d.m.Y', strtotime($item['dateEvent']));
            $datestart = strtotime($item['dateEvent']);
            $dateend = strtotime($item['dateEvent']) + $item['days'] * 86400;
            if (date('m', strtotime($item['dateEvent'])) == date('m', $dateend)) {
                $item['events'] = date('d', $datestart) . '-' . date('d.m.Y', $dateend);
            }
            $item['note'] = str_replace("\r\n", '<br>', trim($item['note']));
        }
        return $list;
    }

    public function getTour($id)
    {
        $mtd = new DB('mil_tours', 'mt');
        $mtd->select("mt.id, mt.date_event, mt.code,  mtt.id AS id_translate, mtt.id_lang,
         mt.image, mtt.name, mtt.note, mtt.description");
        $mtd->leftJoin('mil_tours_translate mtt', 'mtt.id_tour=mt.id');
        $mtd->where('mt.id=?', intval($id));
        //$mtd->andWhere('mtt.id_lang=?', intval($idLang));
        $mtd->groupBy('mtt.id_lang');
        return $mtd->getList();
    }

    public function setTour($idRegion, $datalist = array())
    {
        $datah = array();
        if (!count($datalist)) return;

        file_put_contents('test.log', print_r($datalist, true));


        if ($datalist[0]['image'])
            $datah['image'] = $datalist[0]['image'];
        if ($datalist[0]['id'])
            $datah['id'] = $datalist[0]['id'];
        if ($datalist[0]['dateEvent'])
            $datah['dateEvent'] = $datalist[0]['dateEvent'];
        if ($datalist[0]['code'])
            $datah['code'] = $datalist[0]['code'];
        $datah['idRegion'] = $idRegion;

        $mtd = new DB('mil_tours');
        $data['idRegion'] = $idRegion;
        $mtd->setValuesFields($datah);
        $idH = $mtd->save();
        if (!empty($datah['id']))
            $idH = $datah['id'];
        foreach($datalist as $data) {
            $data['idTour'] = $idH;
            $data['id'] = $data['idTranslate'];
            $mht = new DB('mil_tours_translate');
            $mht->setValuesFields($data);
                //return $mht->save();
            $mht->save();
        }
        return $idH;
    }

    public function setTourShow($idRegion, $id, $show=0)
    {   
        $mtd = new DB('mil_tours');
        $datah['idRegion'] = $idRegion;
        $datah['id'] = $id;
        $datah['isShow'] = $show;
        $mtd->setValuesFields($datah);
        $idH = $mtd->save();
        return $idH;
    }

    public function deleteTour(array $ids)
    {
        $mtd = new DB('mil_tours');
        $mtd->where('id IN (?)', join(',', $ids));
        $mtd->deleteList();
    }


    public function getTourDays($id, $idLang = 2)
    {
        $mtd = new DB('mil_tour_days', 'mtd');
        $mtd->select("mtd.id, mtd.day, mtdt.id AS `id_translate`, mtdt.id_lang, mtdt.name, mtdt.description, mtd.image, mtd.date_event, mtd.day_data");
        $mtd->leftJoin('mil_tour_days_translate mtdt', 'mtdt.id_day=mtd.id AND mtdt.id_lang='.intval($idLang));
        $mtd->innerJoin('mil_tours mt', 'mtd.id_tour=mt.id');
        $mtd->where('mtd.id_tour=?', intval($id));
        $mtd->orderBy('mtd.day', 0);
        $mtd->groupBy('mtd.id');
        //echo $mtd->getSql();
        $list = $mtd->getList();
        foreach($list as &$item) {
            $item['data'] = (!empty($item['dayData'])) ? json_decode($item['dayData'], true) : array();
            unset($item['dayData']);
        }
        return $list;
    }

    public function getDayTour($idDay)
    {
        $mtd = new DB('mil_tour_days', 'mtd');
        $mtd->select("mtd.id, mtd.day, mtdt.id AS `id_translate`, mtdt.id_lang, mtdt.name, mtdt.description, mtd.image, mtd.date_event, mtd.day_data");
        $mtd->where('mtd.id=?', intval($idDay));
        $mtd->leftJoin('mil_tour_days_translate mtdt', 'mtdt.id_day=mtd.id');
        $mtd->groupBy('mtdt.id_lang');
        //echo $mtd->getSql();
        $items = $mtd->getList();
        $daydata = array();
        foreach($items as &$item) {
            if (empty($daydata))
                $daydata = (!empty($item['dayData'])) ? json_decode($item['dayData'], true) : array();
            unset($item['dayData']);
        }
        return array('langs'=>$items, 'dayData'=>$daydata);
    }

    public function setDayTour($idTour, $inputdata)
    {
        if (!count($inputdata)) return;
        $data = array();
        if (!empty($inputdata['dayData'])) {
            $data['dayData'] = json_encode($inputdata['dayData']);
        }
        if (!empty($inputdata['langs'][0]['id'])) {
            $data['id'] = $inputdata['langs'][0]['id'];
        }
        $data['idTour'] = $idTour;
        if (!$inputdata['langs'][0]['id']) {
            if (!$inputdata[0]['dateEvent']) {
                $newEvent = date('Y-m-d');
                $item = end($this->getTourDays($idTour));
                $newEvent = date('Y-m-d', strtotime($item['dateEvent']) + 86400);
            } else $newEvent = $inputdata[0]['dateEvent'];
            $data['dateEvent'] = $newEvent;
        } else if($inputdata['langs'][0]['dateEvent']) {
            $data['dateEvent'] = $inputdata['langs'][0]['dateEvent'];            
        }

        if (!empty($inputdata['langs'][0]['day'])) {
            $data['day'] = $inputdata['langs'][0]['day'];
        }

        $mtd = new DB('mil_tour_days');
        $mtd->setValuesFields($data);
        $idH =  $mtd->save();
        if ($inputdata['langs'][0]['id']) {
            $idH = $inputdata['langs'][0]['id'];
        }

        foreach($inputdata['langs'] as $data) {
            $data['idDay'] = $idH;
            $data['id'] = $data['idTranslate'];
            $mht = new DB('mil_tour_days_translate');
            $mht->setValuesFields($data);
            $mht->save();
        }
        return $idH;
    }

    public function deleteDayTour(array $ids)
    {
        $mtd = new DB('mil_tour_days');
        $mtd->where('id IN (?)', join(',', $ids));
        $mtd->deleteList();
    }
}