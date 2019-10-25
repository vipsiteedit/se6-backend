<?php

class Setting
{
    private static $instance = null;
    private $settings = array();

    public function __construct()
    {
        $this->app = App::get();
        $this->getSettingList();
    }

    private function getSettingList()
    {
        $t = new DB('app_setting', 'as');
        $t->innerJoin('app_setting_group asg', '`as`.id_group=asg.id');
        $t->leftJoin('app_setting_value asv', '`as`.id=asv.id_setting AND asv.id_app=' . $this->app['idApp']);
        $t->select('
            `as`.id,
            `as`.code,
            asg.code AS group_code,
            `as`.type,
            `as`.default,
            `as`.list_values,
            asv.value,
            asv.id AS id_value
        ');

        $list = $t->getList();

        if (!empty($list)) {
            foreach ($list as $val) {
                if (is_null($val['value'])) {
                    $val['value'] = $val['default'];
                } else {
                    $val['value'];
                }

                $this->settings[$val['code']] = $val;
            }
        }
    }

    public function getList()
    {
        return $this->settings;
    }

    public function getValue($code = '')
    {
        $value = null;
        if (!empty($code) && isset($this->settings[$code])) {
            $value = $this->settings[$code]['value'];
        }
        return $value;
    }

    public function addGroup()
    {
        return true;
    }

    public function addSetting()
    {
        return true;
    }

    public function setValue($key, $value = '')
    {
        if (!empty($key) && isset($this->settings[$key])) {
            if (isset($this->settings[$key]['idValue'])) {
                DB::query("UPDATE IGNORE `app_setting_value` SET `value` = '$value' WHERE `app_setting_value`.`id` = " . $this->settings[$key]['idValue']);
            } else {
                $id = $this->settings[$key]['id'];
                $idApp = $this->app['idApp'];
                DB::query("INSERT IGNORE INTO `app_setting_value` (`id_app`, `id_setting`, `value`) VALUES ($idApp, $id, '$value');");
            }
            $this->settings[$key]['value'] = $value;
        }
        return false;
    }

    public function __get($name)
    {
        return $this->getValue($name);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}