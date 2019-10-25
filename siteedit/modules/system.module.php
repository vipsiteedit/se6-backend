<?php

class Data
{
    private static $instance = null;
    private $urlList = array();

    private function __construct()
    {
        $db = new DB('app_urls', 'au');
        $db->select('au.pattern, au.alias, ap.name app, ap.template');
        $db->innerJoin('app_pages ap', 'ap.id=au.id_page');
        $this->urlList = $db->getList();
        unset($db);
    }

    public static function getInstance($namepage = '', $dir = '')
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getUrlList()
    {
        return $this->urlList;
    }
}