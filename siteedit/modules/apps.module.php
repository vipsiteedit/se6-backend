<?php

class App
{
    static $appname;
    public function __construct()
    {
        App::$appname = null;
    }

    public static function set($new_appname = null)
    {
        App::$appname = $new_appname;
    }

    public static function get()
    {
        return App::$appname;
    }
}
