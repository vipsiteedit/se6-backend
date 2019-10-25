<?php

class Macros
{
    public $user;
    public $order;

    private $vars;
    private $currency;
    private $language;
    private $app;


    public function __construct($vars = array())
    {
        $this->language = 'rus';
        $this->vars = $vars;
        $this->app = App::get();

        if (!empty($vars['id_user'])) {
            $this->user = $this->getUserById($vars['id_user']);
        }
    }

    public function exec($text)
    {
        $text = $this->parseSystem($text);
        $text = $this->parseSettings($text);
        $text = $this->parseUser($text);
        $text = $this->parseCurrency($text);

        $text = preg_replace('/\[(.+?)\]/i', '', $text);

        return $text;
    }

    public function getUserById($id)
    {
        if (empty($id))
            return null;

        $u = new DB('se_user');
        $u->where('id = ?', $id);

        return $u->fetchOne();
    }

    public function getMonth($m)
    {
        if ($this->language == 'rus' || $this->language == 'blr')
            $month = array('января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября',
                'октября', 'ноября', 'декабря');
        else
            $month = array('January', ' February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
                'October', 'November', 'December');
        return $month[$m - 1];
    }

    private function parseSystem($text)
    {
        $text = str_replace('[NAME_SITE]', $_SERVER['HTTP_HOST'], $text);
        $text = str_replace('[DATE]', date("d.m.Y"), $text);
        $text = str_replace('[TIME]', date("H:i"), $text);
        $text = str_replace('[DATETIME]', date("d.m.Y H:i:s"), $text);

        foreach ($this->vars as $k => $v) {
            $text = str_replace('[' . strtoupper($k) . ']', stripslashes($v), $text);
        }

        return $text;
    }

    private function parseSettings($text)
    {
        $u = new DB('apps', 'a', false);
        $u->select('*');
        $u->where('id=?', $this->app['idApp']);
        $items = $u->fetchOne();

        $settings = array();

        foreach ($items as $code => $value) {
            $settings[strtoupper($code)] = $value;
        }

        foreach ($settings as $k => $v) {
            $text = str_replace('[APP.' . $k . ']', stripslashes($v), $text);
        }

        $text = preg_replace('/\[APP\.(.+?)\]/i', '', $text);

        return $text;
    }

    private function parseUser($text)
    {
        if (!$this->user)
            return $text;

        if (!empty($this->vars["password"])) {
            $text = str_replace('[USER.PASSWORD]', $this->vars["password"], $text);
        }
        
        foreach ($this->user as $k => $v) {
            $text = str_replace('[USER.' . strtoupper($k) . ']', stripslashes($v), $text);
        }

        $text = preg_replace('/\[USER\.(.+?)\]/i', '', $text);

        return $text;
    }

    private function parseCurrency($text)
    {
        // Парсим пост выбора валюты с дефолными данными
        $res_ = '';
        while (preg_match('/\[POST\.(\w{1,}\:\w{1,})\]/i', $text, $res_math)) {
            $res_ = $res_math[1];
            $def = explode(':', $res_);
            if (isset($_POST[strtolower($def[0])])) {
                $res_ = htmlspecialchars(stripslashes(@$_POST[strtolower($def[0])]));
            } else if (!empty($def[1])) {
                $res_ = $def[1];
            }
            $text = str_replace($res_math[0], strtoupper($res_), $text);
        }

        // Парсим команду SELECTED
        while (preg_match('/\[SELECTED\:(\w{1,})\]/i', $text, $res_math)) {
            if (strtolower($res_) == strtolower($res_math[1])) {
                $text = str_replace($res_math[0], "selected", $text);
            } else {
                $text = str_replace($res_math[0], '', $text);
            }
        }

        // Парсим команду IF
        while (preg_match('/\[IF\((.+?)\)\]/m', $text, $res_math)) {
            list($def, $res) = explode(':', $res_math[1]);
            $sel = explode(',', $def);
            foreach ($sel as $if) {
                $if = explode('=', $if);
                if (strtolower($res_) == strtolower($if[1])) $res = $if[0];
            }
            $text = str_replace($res_math[0], $res, $text);
        }

        // Парсим команду выбор валюты и запиь ее в сессию
        while (preg_match('/\[SETCURRENCY\:(\w{1,})\]/m', $text, $res_math)) {
            if (isset($res_math[1])) {
                $this->currency = $res_math[1];
                $_SESSION['THISCURR'] = $this->currency;
            }
            $text = str_replace($res_math[0], '', $text);
        }

        // Парсим запросы
        while (preg_match('/\[POST\.(\w{1,})\]/i', $text, $res_math)) {
            if (isset($_POST[$res_math[1]])) {
                $res_ = htmlspecialchars(stripslashes($_POST[$res_math[1]]));
            } else {
                $res_ = '';
            }
            $text = str_replace($res_math[0], $res_, $text);
        }

        while (preg_match('/\[GET\.(\w{1,})\]/i', $text, $res_math)) {
            if (isset($_GET[$res_math[1]])) {
                $res_ = htmlspecialchars(stripslashes($_GET[$res_math[1]]));
            } else $res_ = '';
            $text = str_replace($res_math[0], $res_, $text);
        }

        return $text;
    }
}