<?php

class SEditEngine
{
    protected $uri;         // current URI
    protected $app;         // current APP
    protected $proj;
    protected $apps;
    protected $app_controller;
    protected $request = array();

    public function __construct()
    {
        $post = (!empty($_POST)) ? $_POST : array();
        $get = (!empty($_GET)) ? $_GET : array();
        $this->request = array('get'=>$get, 'post'=>$post);

        $this->uri = str_replace('\\', '', urldecode(preg_replace('/\?.*/iu', '', $_SERVER['REQUEST_URI'])));
        if (!preg_match('/^[A-z0-9\-\.@_\/\?]{1,255}$/', $this->uri)){
            $this->uri = '';
        }
        if (isset($get['af'])) {
            $_SESSION['SE_AFFILIATE'] = intval($get['af']);
            $this->go301($this->uri);
        }
        $this->app = false;
        $this->proj = new plugin_export_robots();
        $this->robots();  // Обработка robots.txt и favicon.ico
        $this->apps = $this->proj->getApp();

        $this->sitemap();


        $this->process_path();
        $this->process_defines();
        $this->controller_request();
        $this->process_controllers();
    }

    private function sitemap()
    {
        if (strpos($this->uri, 'sitemap.xml')) {
            $sitemap = new plugin_export_sitemap();
            header("Content-type: text/xml");
            $sitemap->execute();
            exit;
        }
    }

    private function robots()
    {
        if (strpos($this->uri, 'robots.txt')) {
            $this->proj->getRobots();
        }
        if (strpos($this->uri, 'favicon.ico')) {
            $this->proj->getFavicon();
        }
    }

    public function getAppActive()
    {
        return $this->apps;
    }

    private function getThisPagePattern($alinks, $pageId)
    {
        foreach($alinks as $urs) {
            if ($urs['idPage'] == $pageId && $urs['type'] == 'page') {
                return $urs['pattern'];
            }
        }
    }

    private function setHandler()
    {
        if (isset($_GET['ajax'])) {
            $this->handlers_controller($_GET['ajax']);
        }
        if (isset($_GET['logout'])) {
            if (!empty($_SESSION['AUTH_USER'])) {
                unset($_SESSION['AUTH_USER']);
            }
            header('Location: ' . _HOST_);
            exit;
        }
    }

    private function openPage($uri, $alinks)
    {
        foreach($alinks as $url) {
            $urs = convert_url($url['pattern']);
            $url['originalPattern'] = $url['pattern'];
            $url['pattern'] = $urs['pattern'];

            if (preg_match($url['pattern'], $uri, $req)) {
                $this->metaTitle = $url['metaTitle'];
                $this->metaDescription = $url['metaDescription'];
                $this->metaKeywords = $url['metaKeywords'];
                $this->pageTitle = $url['pageTitle'];
                if (!empty($url['permissions'])) {
                    $auth = new auth();
                    $access = false;
                    foreach(explode(',', $url['permissions']) as $perm) {
                        if ($auth->checkPermission($perm)) {
                            $access = true;
                            break;
                        }
                    }
                    if (!$access) {
                        $this->go301('/');
                    }
                }
                if (!empty($url['idLang']) && empty($_SESSION['SE_APP_LANG'])) {
                    $_SESSION['SE_APP_LANG'] = $url['idLang'];
                } else {
                    $url['idLang'] = !empty($_SESSION['SE_APP_LANG']) ? $_SESSION['SE_APP_LANG'] : false;
                }


                $args = array();
                $args['uri'] = $req[0];
                $args['get'] = $this->request['get'];
                $args['post'] = $this->request['post'];

                foreach($urs['vars'] as $i=>$name) {
                    $value = isset($req[$i + 1]) ? $req[$i + 1] : '';
                    $args[$name] = $value;
                }
                $args['page'] = $this->getThisPagePattern($alinks, $url['idPage']);

                // Переменные для TWIG

                $section_off = false;
                if ($url['type'] == 'item' || $url['type'] == 'group') {
                    if(!$this->checkSection($url, $args)) {
                        $section_off = true;
                    }
                }
                if (!$section_off) {
                    $url['metaTitle'] = $this->metaTitle;
                    $url['metaDescription'] = $this->metaDescription;
                    $url['metaKeywords'] = $this->metaKeywords;
                    $url['pageTitle'] = $this->pageTitle;
                    $this->setApp($url, $args);
                } else {
                    $this->app = false;
                }
                $this->setHandler();
                break;
            }
        }
    }

    public function process_path()
    {
        $urllistfile = CACHE_DIR . '/app_urls.json';
        if (!file_exists($urllistfile) || filemtime($urllistfile) + 10 < time()) {
            try {
                $alinks = get_app_urls($this->apps);
                if (!is_dir(dirname($urllistfile))) {
                    mkdir(dirname($urllistfile), 0775, 1);
                }
                file_put_contents($urllistfile, json_encode($alinks));
            }
            catch (Exception $e) {
                print_r($e);
            }
        } else {
            $alinks = json_decode(file_get_contents($urllistfile), true);
        }

        // Отработка запроса URI
        $this->openPage($this->uri, $alinks);

        if(!$this->app && $url = get_app_collection_uri($this->uri)) {
            $url['pattern'] = $this->uri;
            $args = array();
            $args['uri'] = $this->uri;
            $args['get'] = $this->request['get'];
            $args['post'] = $this->request['post'];
            $args['page'] = $this->getThisPagePattern($alinks, $url['idPage']);
            $args['code'] = $url['code'];
            $this->setApp($url, $args);
        }
        // page_404.html
        if (!$this->app) {
            $this->openPage('/page404', $alinks);
            if ($this->app) header('HTTP/1.0 404 File not found');
        }

        if (!$this->app) {
            $this->go404();
        } else {
            $this->setHandler();
        }
    }

    private function setApp($url, $args)
    {
        // Переменные для TWIG
        $vars = array(
            'pageTitle' => ($url['pageTitle']) ? $url['pageTitle'] : $url['title'],
            'metaTitle' => ($url['metaTitle']) ? $url['metaTitle'] : $url['title'],
            'metaKeywords' => $url['metaKeywords'],
            'metaDescription' => htmlspecialchars(($url['metaDescription']) ? $url['metaDescription'] : ''),
            'url' => array('alias' => $url['alias'], 'this' => $args['uri'], 'page' => $args['page']),
            'req' => $args,
            'section' => $url['idSection'],
            'user' => (!empty($_SESSION['AUTH_USER'])) ? $_SESSION['AUTH_USER'] : array(),
        );

        $this->app = array(
            $url['app'], array(
                'lang' => $url['lang'],
                'pattern' => $url['pattern'],
                'method' => array(
                    'template' => $url['template'],
                    'vars' => $vars
                ),
                'section' => $url['idSection'],
                'alias' => $url['alias'],
                'args' => $args,
            )
        );

        App::set(array(
            'app' => $url['app'],
            'idApp' => $this->apps,
            'type' => $url['type'],
            'section' => $url['idSection'],
            'args' => $args,
            'vars' => $vars,
            'main' => array('email' => $url['fromEmail'], 'smsPhone' => $url['smsPhone'], 'smsSender' => $url['smsSender']),
            'idLang' => (!empty($url['idLang'])) ? $url['idLang'] : 0,
        ));
    }

    public function process_defines()
    {
        define('ACTIVE_APP', $this->app[0]);
    }

    private function checkSection($url, $arg = array())
    {
        foreach ($arg as $name => $val) {
            if ($name == 'code' || $name == 'id') {
                $fields[] = array(
                    'name' => $name,
                    'value' => $val,
                );
            }
        }
        $idLang = intval($_SESSION['SE_APP_LANG']);
        if ($url['type'] == 'item') {
            $u = new DB('app_section_collection', 'asc');
            $u->select('asc.id, asc.code, asct.name, asct.note,  asct.meta_title, asct.meta_description, asct.meta_keywords, asct.page_title');
            $u->leftJoin('app_section_collection_translate asct', 'asct.id_collection=`asc`.id AND asct.id_lang='.$idLang);
        } else {
            $u = new DB('app_section_groups', 'asg');
            $u->select('asg.id, asg.code, asct.name, asct.note,  asct.meta_title, asct.meta_description, asct.meta_keywords, asct.page_title');
            $u->leftJoin('app_section_groups_translate asct', 'asct.id_group=`asg`.id AND asct.id_lang='.$idLang);
        }

        if (!empty($fields)) {
            $u->where('TRUE');
            foreach ($fields as $field) {
                $u->andWhere("{$field['name']}='?'", $field['value']);
            }

            if ($result = $u->fetchOne()) {
                if (convert_pattern_url($url['originalPattern'], $result) == $arg['uri']) {
                    $note = trim(strip_tags(str_replace(array('&nbsp;', "\r\n"), ' ', $result['note'])));
                    if (utf8_strlen($note) > 250) $note = utf8_substr($note, 0, 250) . '...';
                    $this->metaTitle = ($result['metaTitle']) ? $result['metaTitle'] : strip_tags($result['name']);
                    $this->metaDescription = ($result['metaDescription']) ? $result['metaDescription'] : $note;
                    $this->metaKeywords = $result['metaKeywords'];
                    $this->pageTitle = ($result['pageTitle']) ? $result['pageTitle'] : $result['name'];
                    return true;
                }
            }
        }
        return false;
    }

    private function handlers_controller($name = '')
    {
        if ($this->app) {
            $name = strtolower($name);
            if (file_exists(BASE_DIR . '/apps/units/' . $this->app[0] . '/' . $name . '.unit/controller.php')) {
                require(BASE_DIR . '/apps/units/' . $this->app[0] . '/' .  $name . '.unit/controller.php');
                if (strpos($name, '_')!== false) {
                    $newname = '';
                    foreach(explode('_', $name) as $n) {
                        $newname .= ucfirst($n);
                    }
                    $name = $newname;
                } else
                    $name = ucfirst($name);
                $controller_name = $name . '_Controller';
                $this->app_controller = new $controller_name();
                $this->app_controller->Handler($this->app[1]['args']);
                exit;
            }
        }
    }

    public function process_controllers()
    {
        if ($this->app) {
            //dump($this->app);
            if (is_array($this->app[1]['method'])) {
                render($this->app[1]['method']['template'], $this->app[1]['method']['vars']);
            } else {
                if (file_exists(BASE_DIR . '/apps/' . $this->app['0'] . '/controller.php')) {
                    require(BASE_DIR . '/apps/' . $this->app['0'] . '/controller.php');
                    $controller_name = $this->app[0] . '_Controller';
                    $this->app_controller = new $controller_name();
                    $this->app_controller->{$this->app[1]['method']}($this->app[1]['args']);
                }
            }
        }
    }

    public function go301($url)
    {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $url);
        exit;
    }

    public function go302($url)
    {
        header("HTTP/1.1 302 Moved Temporarily");
        header("Location: " . $url);
        exit;
    }

    public function go404()
    {
        header('HTTP/1.0 404 File not found');
        exit('Error 404 not found!');
    }

    public function controller_request()
    {
        // Авторизация
        if (isset($_POST['controller-authorize'])) {
            echo json_encode(array('result'=>'success'));
            //print_r($_POST);
            exit;
        }
        if (isset($_POST['controller-egistration'])) {
            exit;
        }
        /*
        if (isset($_POST))
            switch (){
            'controller-registration'
            }
        */
    }
}