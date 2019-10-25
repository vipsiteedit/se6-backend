<?php

class plugin_export_robots
{
    private $app = array();

    public function __construct()
    {
        $domain = getUrlHost();
        $u = new DB('apps', 'ap');
        $u->select('ap.*');
        $apps = $u->getList();
        foreach($apps as $app) {
            $alias = explode("\r\n", $app['alias']);
            if ($domain == $app['domain'] || in_array($domain, $alias) || !$app['domain']) {
                $app['domain'] = $domain;
                $this->app = $app;
                break;
            }
        }
    }

    public function getRobots()
    {
        header("Content-type: text/plain");
        echo str_replace('{host}', $this->app['domain'], $this->app['robots']);
        exit;
    }

    public function getFavicon()
    {
        header("Content-type: image/x-icon");
        echo $this->app['favicon'];
        exit;
    }

    public function getApp()
    {
        if (!empty($this->app['id']))
        return $this->app['id'];
    }
}