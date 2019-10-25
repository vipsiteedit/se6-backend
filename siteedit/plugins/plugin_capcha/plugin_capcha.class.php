<?php

require_once __DIR__. '/../curlAbstract.class.php';

class plugin_capcha extends CurlAbstract 
{
    private $key;
    private $keySite;
    
    public function __construct($keySite = false, $key = false)
    {
        
        
        $this->keySite = ($keySite) ? $keySite : '6LdB9GgUAAAAAN9lNozYlqm06arG4wJfmDiKcSPu';
        $this->key = ($key) ? $key : '6LdB9GgUAAAAAF1eZwvFvPRs2-I6LpL87Nw3rVII';
        
        if ($this->keySite && $this->key) {
            echo '<script src="https://www.google.com/recaptcha/api.js"></script>';
        }
        
        $this->errstpin = '';
    }
    
    public function getCapcha($title = '', $error = '')
    {
        if (!$this->keySite) return;
        if (!$title) 
            $title = 'Введите цифры с картинки';
        
        if (!$error) 
            $error = 'Не верно введено число с картинки';
        
        return '<div class="g-recaptcha" data-sitekey="' . $this->keySite  . '"></div>';
    }
    
    private function detect_ip()
    {
        $ip = false;
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) and preg_match("#^[0-9.]+$#", $_SERVER["HTTP_X_FORWARDED_FOR"]))
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        elseif (isset($_SERVER["HTTP_X_REAL_IP"]) and preg_match("#^[0-9.]+$#", $_SERVER["HTTP_X_REAL_IP"]))
            $ip = $_SERVER["HTTP_X_REAL_IP"];
        elseif (preg_match("#^[0-9.]+$#", $_SERVER["REMOTE_ADDR"]))
            $ip = $_SERVER["REMOTE_ADDR"];
        return $ip;
    }

    public function getError()
    {
        return $this->errstpin;
    }

    public function check()
    {
        if (!isset($_REQUEST['g-recaptcha-response'])){
            return;
        } else {
            $data = array(
                'secret' => $this->key,
                'response' => $_REQUEST['g-recaptcha-response'],
                'remoteip' => $this->detect_ip(),
            );
        
            $result = $this->queryJSON('https://www.google.com/recaptcha/api/siteverify', $data, 'POST');
            return $result["success"];
        }
        return;
    }
}