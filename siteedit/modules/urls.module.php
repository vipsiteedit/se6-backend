<?php

function url($pattern, $method, $alias)
{
    return array(
        'pattern' => $pattern,
        'method' => $method,
        'alias' => $alias

    );
}


// Преобразование паттерна в ссылку
function convert_pattern_url($pattern, $vars = array())
{
    global $__pl;
    $__pl = $vars;
    return preg_replace_callback('#\{[A-z0-9]+\}#', function($match){
        global $__pl;
        $key = str_replace(array('{','}'), '', $match[0]);
        if (isset($__pl[$key])) {
            $value = $__pl[$key];
            //unset($__pl[$key]);
            return $value;
        }
        return '';
    }, $pattern);
}

function convert_url($pattern)
{
    global $__vars;
    $__vars = array();
    $patt = '#^';
    $patt .= preg_replace_callback('#\{([A-z0-9]+)\}#', function($m){
        global $__vars;
        $__vars[] = $m[1];
        return '([^/]+?)';
    }, $pattern);
    $patt .= '/*$#i';
    return array('pattern'=>$patt, 'vars'=>$__vars);
}

function get_app_urls($appId = false)
{
    global $__BASE_URLS;
    if (!isset($__BASE_URLS)) {
        $db = new DB('app_urls', 'au');
        //$db->addField('from_email', 'varchar(255)');
        //$db->addField('from_phone', 'varchar(255)');

        $db->select('au.id_page, al.code AS lang, apt.id_lang, au.id_section, au.pattern, 
        au.alias, aps.app_name app, 
        au.template, apt.title, apt.page_title, apt.meta_title, apt.meta_keywords, apt.meta_description, 
        au.type, GROUP_CONCAT(app.id_permission) permissions, aps.sms_phone, aps.sms_sender, aps.from_email');
        $db->innerJoin('apps aps', 'aps.id=au.id_app');
        $db->leftjoin('app_pages ap', 'ap.id=au.id_page');
        $db->leftjoin('app_language al', 'al.id=aps.id_lang');
        $db->leftJoin('app_pages_translate apt', 'ap.id=apt.id_page AND apt.id_lang=aps.id_lang');
        $db->leftJoin('app_page_permission app', 'app.id_page=ap.id');
        $db->groupBy('au.id');
        if ($appId)
            $db->where('au.id_app=?', $appId);
        // echo $db->getSql();
        $__BASE_URLS = $db->getList();
    }
    return $__BASE_URLS;
}

function get_app_collection_uri($uri)
{
    if (!preg_match('/^[A-z0-9\.@_\/\?]{2,255}$/', $uri)){
        return array();
    }
    $u = new DB('app_section_collection', 'ascl');
    $u->select('ascl.id id_collection, `a`.id_lang, ascl.code, au.id_page, au.alias, au.template, ascl.id_section, act.page_title, act.meta_title, act.meta_keywords, act.meta_description,
        au.type, GROUP_CONCAT(app.id_permission) permissions, a.sms_phone, a.sms_sender, a.from_email, a.app_name app');
    $u->innerJoin('app_section `as`', 'as.id=ascl.id_section');
    $u->innerJoin('app_urls au', "au.id_section=`ascl`.id_section AND au.type='item'");
    $u->innerJoin('app_pages ap', 'ap.id=`as`.id_page');
    $u->innerJoin('apps a', 'a.id=au.id_app');
    $u->leftJoin('app_section_collection_translate act', "act.id_collection=`ascl`.id AND act.id_lang=a.id_lang");
    $u->leftJoin('app_section_collection_permission app', 'app.id_collection=ascl.id');
    $u->where("ascl.url='?'", $uri);
    $result = $u->fetchOne();
    if (!empty($result['idCollection'])) {
        return $result;
    }
}


function get_app_alias_url($aliasname, $placefolders = array())
{
    global $__pl;
    $__pl = $placefolders;
    // Если в ссылку передаем URL, то безоговорочное выполнение
    if (!empty($__pl['url'])) {
        return $__pl['url'];
    }

    $urls = get_app_urls();
    foreach($urls as $url) {
        if ($url['alias'] == $aliasname) {
            if (strpos($url['pattern'], '://')!==false) {
                return $url['pattern'];
            }

            return preg_replace_callback('#\{[A-z0-9]+\}#', function($match){
                global $__pl;
                $key = str_replace(array('{','}'), '', $match[0]);
                if (isset($__pl[$key])) {
                    $value = $__pl[$key];
                    //unset($__pl[$key]);
                    return $value;
                }
                return '';
            }, $url['pattern']);
        }
    }
}

//функция определения ip адреса клиента
function detect_ip()
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

// Получить расширение файла
function getExtFile($filename)
{
    $res = explode(".", $filename);
    return end($res);
}

// Удалить расширение
function delExtFile($filename)
{
    $ext = getExtFile($filename);
    if (!empty($ext)) {
        $len = 0 - (strlen($ext) + 1);
        $filename = substr($filename, 0, $len);
    }
    return $filename;
}

// Установить префикс в файл
function setPrefFile($filename, $pref = '')
{
    return delExtFile($filename) . $pref . '.' . getExtFile($filename);
}

function getUrlHostCheme()
{
    if (!empty($_SERVER['REDIRECT_HTTPS']) && $_SERVER['REDIRECT_HTTPS'] == 'on')
        return 'https://';
    else
        if (!empty($_SERVER['REQUEST_SCHEME'])) {
            return $_SERVER['REQUEST_SCHEME'] . '://';
        } else {
            return (!$_SERVER['HTTPS'] || $_SERVER['HTTPS'] == 'off') ? 'http://' : 'https://';
        }
}

function getUrlHost()
{
    return getUrlHostCheme() . $_SERVER['HTTP_HOST'];
}

function get_url_contents($url)
{
    $i = 0;
    $cookie = "foo=bar";
    do {
        $context = stream_context_create(
            array(
                "http" => array(
                    "follow_location" => false,
                    'method' => "GET",
                    'header' => "Accept-language: en\r\n" .
                        "upgrade-insecure-requests:1\r\n" .
                        "cookie: {$cookie}\r\n" .
                        "user-agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36\r\n"
                ),
            )
        );
        //$idna = new idna_convert();
        //$url = $idna->encode($url);
        $result = file_get_contents($url, false, $context);
        $pattern_location = "/^Location:\s*(.*)$/i";
        $location_headers = preg_grep($pattern_location, $http_response_header);
        $first_location = array_values($location_headers);
        $pattern2 = "/^Content-Encoding:\s*(.*)$/i";
        $encoding_headers = preg_grep($pattern2, $http_response_header);
        $encoding = array_values($encoding_headers);
        $location_headers = false;
        $encoding = false;
        if (!empty($location_headers) &&
            preg_match($pattern_location, $first_location[0], $matches)
        ){
            $url = $matches[1];
            unset($http_response_header);
            $repeat = true;
        } else {
            $repeat = false;
        }
        $i++;
        if ($i > 5) break;
    } while ($repeat);
    return $result;
}