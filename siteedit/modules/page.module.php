<?php

class page {
    private $pagename;
    private $content;
    private static $instance = null;

    public function __construct($pagename)
    {
        $this->pagename - $pagename;
        $this->loadPage();
    }

    private function loadPage()
    {
        $dpage = new DB('app_pages', 'ap');
        $dpage->select('ap.id, pm.name permission');
        $dpage->leftJoin('app_permission pm', 'ap.id_permission=pm.id');
        $dpage->where("ap.name='?'", $this->pagename);
        $this->content = $dpage->fetchOne();
    }

    public static function getInstance($namepage = '')
    {
        if (self::$instance === null) {
            self::$instance = new self($namepage);
        }
        return self::$instance;
    }

    public function getPermission()
    {
        return array('name'=>$this->content['permission'], 'level'=>$this->content['level']);
    }


}