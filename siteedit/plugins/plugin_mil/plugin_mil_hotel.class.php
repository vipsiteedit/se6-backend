<?php

class plugin_mil_hotel {

    private $hotel;
    private $idLang;

    public function __construct()
    {
        $idRegion = ($_SESSION['region']) ? intval($_SESSION['region']) : 0;
        $this->hotel = new plugin_mil_data($idRegion, 1);
        $user = isset($_SESSION['AUTH_USER']) ? $_SESSION['AUTH_USER'] : array();
        $this->idLang = !empty($user['idLang']) ? $user['idLang'] : 1;
    }

    public function getHotels($idLang = 1)
    {
        $result = array();
        foreach($this->hotel->getList() as $item) {
            $result[] = array('id'=>$item['id'], 'name'=>$item['name'], 'address'=>strip_tags($item['data']['address'][$idLang]), 'rating'=>$item['data']['rating'], 'rooms'=>count($item['items']));
        }

        /*
        $idRegion = ($_SESSION['region']) ? intval($_SESSION['region']) : 0;

        $mhot = new DB('mil_hotel', 'mh');
        $mhot->select('mht.id, mht.name, mht.address, mht.note, mh.image, ');
        $mhot->leftJoin('mil_hotel_translate mht', 'mht.id=mh.id');
        $mhot->Where('mht.id_lang=?', intval($idLang));
        if ($idRegion)
            $mhot->andWhere('mh.id_region=?', intval($idRegion));

        */
        return $result;
    }

    public function getHotel($id)
    {
        $result = $this->hotel->getData($id);
        if (!empty($result)) {
            $result['name'] =  $result['data']['name'][$this->idLang];
            $result['address'] =  $result['data']['address'][$this->idLang];
            $result['note'] =  $result['data']['note'][$this->idLang];
            $result['rating'] =  $result['data']['rating'];
            $result['image'] =  $result['data']['image'];
            unset($result['data']);
        }
        return $result;

        /*
        $mhot = new DB('mil_hotel', 'mh');
        $mhot->select('mh.id, mht.id AS id_tr, mht.name, mht.address, mht.note, mh.image');
        $mhot->innerJoin('mil_hotel_translate mht', 'mht.id_hotel=mh.id');
        $mhot->where('mh.id=?', intval($id));
        $mhot->andWhere('mht.id_lang=?', intval($idLang));
        return $mhot->fetchOne();
        */
    }

    public function setHotel($input = array())
    {
        $data = array();
        $data = $this->hotel->getData($input['id']);
        $data['data']['name'][$this->idLang] = $input['name'];
        $data['data']['address'][$this->idLang] = $input['address'];
        $data['data']['note'][$this->idLang] = $input['note'];
        $data['data']['rating'] = $input['rating'];
        if ($input['image']) $data['data']['image'] = $input['image'];
        $data['name'] = $input['name'];
        return $this->hotel->setData($data);
    }

    public function getRoomList($idParent)
    {
        $result = array();
        foreach($this->hotel->getList($idParent) as $item) {
            $result[] = array('id'=>$item['id'],
                'name'=>$item['name'],
                'number'=>$item['data']['number'],
                'note'=>$item['data']['note'],
                'image'=>$item['data']['image'],
                'count'=>$item['data']['count'],
                'price'=>$item['data']['price'],
                'pricebase'=>$item['data']['pricebase'],
            );
        }
        return $result;
    }

    public function delete($ids)
    {
        $result = array();
        return $this->hotel->delete($ids);
    }

    public function getRoom($id)
    {
        $result = $this->hotel->getData($id);
        if (!empty($result)) {
            //$result['name'] =  $result['data']['name'];
            $result['number'] =  $result['data']['number'];
            $result['note'] =  $result['data']['note'];
            $result['info'] =  $result['data']['info'];
            $result['image'] =  $result['data']['image'];
            $result['count'] =  $result['data']['count'];
            $result['price'] =  $result['data']['price'];
            $result['pricebase'] =  $result['data']['pricebase'];
            unset($result['data']);
        }
        return $result;
    }

    public function setRoom($idParent, $input = array())
    {
        if (!$idParent) return;
        $data = array();
        $data = $this->hotel->getData($input['id']);
        $data['data']['name'] = $input['name'];
        $data['data']['number'] = $input['number'];
        $data['data']['note'] = $input['note'];
        $data['data']['info'] = $input['info'];
        $data['data']['count'] = intval($input['count']);
        $data['data']['price'] = floatval($input['price']);
        $data['data']['pricebase'] = floatval($input['pricebase']);
        if ($input['image']) $data['data']['image'] = $input['image'];
        $data['name'] = $input['name'];
        return $this->hotel->setData($data, $idParent);
    }

}