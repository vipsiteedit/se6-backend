<?php

class Cache {
    static $appname = null;

    private static function init()
    {
        if (empty(self::$appname)) {
            self::$appname = new Memcache;
            self::$appname->pconnect('127.0.0.1', 11211);
        }
    }

    public static function set($name, $value, $expire = 30)
    {
        self::init();
        $hashkey = md5(__DIR__.$name);
        self::$appname->set($hashkey, serialize($value), false, $expire);
    }
    public static function get($name)
    {
        self::init();
        $hashkey = md5(__DIR__.$name);
        return @unserialize(self::$appname->get($hashkey));
    }
    public static function setSession($name, $value, $expire = 30)
    {
        self::init();
        $hashkey = md5(__DIR__.session_id().$name);
        self::$appname->set($hashkey, serialize($value), false, $expire);
    }
    public static function getSession($name)
    {
        self::init();
        $hashkey = md5(__DIR__.session_id().$name);
        return @unserialize(self::$appname->get($hashkey));
    }

}