<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class RequestOrder extends Base
{
    protected $tableName = "app_request_order";

    private function convertFields($str)
    {
        $str = str_replace('id ', '`aro`.id ', $str);
        $str = str_replace('idRequest', '`aro`.id_request', $str);
        //$str = str_replace('newsDate', 'n.news_date', $str);

        return $str;
    }

    protected function getSettingsFetch()
    {
        $joins = array();
        $result["select"] = 'aro.*,su.person_name `manager`';
        $joins[] = array(
            "type" => "left",
            "table" => 'se_user su',
            "condition" => "su.id=aro.id_manager"
        );
        $result["joins"] = $joins;
        return $result;
    }

    public function fetch($isId = false)
    {
        $u = new DB('app_request_order');
        $u->addField('commentary', 'text');
        $u->addField('id_manager', 'int(10)');
        return parent::fetch();
    }

    public function save()
    {
        $this->input['isShowing'] = 1;
        parent::save();
    }



    public function info($id = null)
    {
        $this->result = parent::info($id);
        $this->result["customFields"] = $this->getCustomFields();
        $this->result["manager"] = $this->getManager();
        if ($this->result['idObject']) {
           $this->result["object"] = $this->getObject($this->result['idObject']);
        }
        return $this->result;
    }

    private function getObject($id)
    {

    }

    private function getCustomFields()
    {
        $idOrder = $this->input["id"];
        try {
            $u = new DB('app_request_fields', 'su');
            $u->select("cu.id, cu.id_collection, cu.value, su.id id_field, su.min, su.max, su.defvalue,
                      su.name, su.required, su.enabled, su.type, su.placeholder, su.description, su.values, sug.id id_group, sug.name name_group");
            $u->innerJoin('app_request_order asl', 'su.id_request=asl.id_request');
            $u->leftJoin('app_request_order_values cu', "cu.id_field = su.id AND cu.id_order=asl.id");
            $u->leftJoin('app_request_fieldsgroup sug', 'su.id_group = sug.id');
            $u->where('asl.id=?', $idOrder);
            $u->groupBy('su.id');
            $u->orderBy('sug.sort');
            $u->addOrderBy('su.sort');
            $result = $u->getList();

            $groups = array();
            foreach ($result as $item) {
                $groups[intval($item["idGroup"])]["id"] = $item["idGroup"];
                $groups[intval($item["idGroup"])]["name"] = empty($item["nameGroup"]) ? "" : $item["nameGroup"];
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

    private function getManager($id = false)
    {
        $id = ($id) ? $id : $this->input["idManager"];
        try {
            $u = new DB('se_user');
            $u->select('person_name');
            $result = $u->find(intval($this->input["idManager"]));
            return $result['personName'];
        } catch (Exception $e) {
        }
    }

    private function saveCustomFields()
    {
        if (!isset($this->input["customFields"]) && !$this->input["customFields"])
            return true;

        try {
            $idOrder = $this->input["id"];
            $groups = $this->input["customFields"];
            $customFields = array();
            foreach ($groups as $group)
                foreach ($group["items"] as $item)
                    $customFields[] = $item;
            foreach ($customFields as $field) {
                if (!in_array($field['type'], array('file', 'image'))) {
                    $field["idOrder"] = $idOrder;
                    $u = new DB('app_ewquest_order_values', 'cu');
                    $u->setValuesFields($field);
                    $u->save();
                }

            }
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить доп. информацию о товаре!";
            throw new Exception($this->error);
        }
    }


    protected function saveAddInfo()
    {
        return $this->saveCustomFields();
    }
}
