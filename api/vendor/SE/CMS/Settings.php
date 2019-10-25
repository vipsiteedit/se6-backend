<?php

namespace SE\CMS;

use SE\DB;
use SE\Exception;

class Settings extends Base
{
    protected $tableName = "app_setting";

    public function fetch($isId = false)
    {
        try {
            $db = new DB('app_setting', 'as');
            $db->select("`as`.*");
            $settings = $db->getList();

            $db = new DB('app_setting_group', 'asg');
            $db->select("asg.*");
            $this->result['groups'] = $db->getList();

            $db = new DB('app_setting_value', 'asv');
            $db->select("asv.*");
            $values = $db->getList();

            $this->result['settings'] = array();
            foreach ($settings as $setting) {
                foreach ($values as $value) {
                    if ($value['idSetting'] == $setting['id']) {
                        $setting['value'] = $value['value'];
                        $setting['valueID'] = $value['id'];
                    }
                }
                if (!isset($setting['value'])) {
                    $setting['value'] = $setting['default'];
                }
                if (!empty($setting['listValues'])) {
                    // value1|name1,value2|name2,value3|name3
                    $list = explode(',', $setting['listValues']);
                    $setting['listValues'] = array();
                    foreach ($list as $string) {
                        // value1|name1
                        $array = explode('|', $string);
                        $setting['listValues'][$array[0]] = $array[1];
                    }
                }
                $this->result['settings'][] = $setting;
            }
            return $this->result;

        } catch (Exception $e) {
            return $this->error = 'Не удалось получить настройки';
        }

    }

    public function save()
    {
        if (isset($this->input['settings'])) {
            foreach ($this->input['settings'] as $setting) {
                try {
                    DB::beginTransaction();

                    if ($setting['type'] == 'bool') {
                        $value = (int)$setting['value'];
                    } else {
                        $value = htmlspecialchars(trim($setting['value']));
                    }
                    $enabled = $setting['enabled'] ? (int)$setting['enabled'] : 0;

                    $id = $setting['id'];

                    DB::query("UPDATE IGNORE `app_setting` SET `enabled` = '$enabled' WHERE `app_setting`.`id` = " . $id);

                    if (isset($setting['valueID']))
                        DB::query("UPDATE IGNORE `app_setting_value` SET `value` = '$value' WHERE `app_setting_value`.`id` = " . $setting['valueID']);
                    else
                        DB::query("INSERT IGNORE INTO `app_setting_value` (`id_setting`, `value`) VALUES ($id, '$value');");

                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                }
            }
        }
        return $this->fetch();
    }
}