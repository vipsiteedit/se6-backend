<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class RequestField extends Base
{
    protected $tableName = "app_request_fields";
    protected $sortBy = "sort";
    protected $sortOrder = "asc";

    protected function getSettingsFetch()
    {

        $result["select"] = '`asf`.*, `asfg`.`name` AS `group_name`';
        $result["joins"][] = array(
            "type" => "left",
            "table" => 'app_request_fieldsgroup `asfg`',
            "condition" => '`asfg`.id = `asf`.id_group'
        );
        return $result;
    }

    public function correctValuesBeforeSave()
    {
        /*
        $u = new DB($this->tableName);
        $field = $u->getField('data');

        if ($field['Type'] !== "enum('contact','order','company','productgroup','product','public')") {
            DB::query("ALTER TABLE `{$this->tableName}` CHANGE `data` `data` ENUM('contact','order','company','productgroup','product','public') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        }
        */
        $this->input["idGroup"] = empty($this->input["idGroup"]) ? null : $this->input["idGroup"];
    }
}
