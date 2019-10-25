<?php
require_once INCLUDES_DIR . '/parser/php-html-css-js-minifier.php';

function render($view, $vars = array())
{
    $loader = new Twig_Loader_Filesystem(TEMPLATES_DIR);

    // Extend path
    $loader->addPath(TEMPLATES_DIR . '/' . ACTIVE_APP);

    $twig = new Twig_Environment($loader, array(
        //'cache' => TWIG_CACHE_DIR,
        'autoescape' => false,
        'debug' => true
    ));

    //$twig->addExtension(new \Twig_Extension_Debug());

    // Connect Twig Extensions
    $extensions = glob(TWIG_EXTENSIONS_DIR . '/*.twig.php');
    foreach ($extensions as $extension) {
        require_once $extension;
        $ext_class_name = '\\Twig_Extensions\\' . ucfirst(strtolower(str_replace('.twig.php', '', basename($extension)))) . '_Twig_Extension';
        $twig->addExtension(new $ext_class_name());
    }
    render_extend_vars($vars);

    $template = $twig->loadTemplate($view . '.html');
    $render = $template->render($vars);
    $App = App::get();
    $renderJs = '';
    if (!empty($App['footer_js']))
        foreach ($App['footer_js'] as $jsUrl) {
            $renderJs .= '<script type="text/javascript" src="' . $jsUrl . '"></script>' . "\r\n";
        }
    //$render = str_replace('<head>', '<head><meta name="generator" content="CMS EDGESTILE SiteEdit" />', $render);
    if ($renderJs)
        $render = str_replace('</body>', $renderJs . '</body>', $render);

    $render = preg_replace_callback('#<base[^\>]+>#', function ($match) {
        //return '<base href="/www/'.ACTIVE_APP . '/">';
        return '<base href="/">';
    }, $render);
    echo $render;
}

function render_url($view, $vars = array())
{
    return array(
        'view' => $view,
        'vars' => $vars
    );
}

function render_module($alias, $sectionAlias = false, $arguments = array())
{
    $App = App::get();

    $alias = strtolower($alias);
    if (strpos($alias, '.') !== false) {
        list($alias, $templ) = explode('.', $alias);
    } else {
        $templ = ($App['type'] == 'page' || $App['type'] == 'group') ? 'main' : 'item';
    }

    if (empty($arguments)) {
        foreach ($App['args'] as $key => $val) {
            $arguments[$key] = $val;
        }
    }

    if (!$alias) $alias = 'text';

    $css = '';
    $cssfile = UNITS_DIR . '/' . ACTIVE_APP . '/' . $alias . '.unit/tpl/' . $templ . '.css';
    $csscache = CACHE_DIR . '/' . md5(ACTIVE_APP . '/' . $alias . '.unit/tpl/' . $templ . '.css') . '.css';
    if (file_exists($cssfile)) {
        if (!file_exists($csscache) || filemtime($cssfile) > filemtime($csscache)) {
            $css = fn_minify_css(file_get_contents($cssfile));
            file_put_contents($csscache, $css);
        } else {
            $css = file_get_contents($csscache);
        }
        $css = ($css) ? '<style type="text/css">' . $css . '</style>' : '';
    }

    /*if (file_exists(UNITS_DIR . '/' . ACTIVE_APP . '/' . $alias .'.unit/tpl/'.$templ.'.css')) {
        $css = file_get_contents(UNITS_DIR . '/' . ACTIVE_APP . '/' . $alias .'.unit/tpl/'.$templ.'.css');
        $css = ($css) ? '<style type="text/css">' . fn_minify_css($css) . '</style>' : '';
    }
    */
    $htmlfile = '/' . ACTIVE_APP . '/' . $alias . '.unit/tpl/' . $templ . '.html';
    if (file_exists(UNITS_DIR . $htmlfile) && filesize(UNITS_DIR . $htmlfile) > 0) {
        if (!isset($App['footer_js'])) $App['footer_js'] = array();
        while (preg_match("/<footer:js>(.+?)<\/footer:js>/usim", file_get_contents(UNITS_DIR . $htmlfile), $m)) {
            if (!in_array($m[1], $App['footer_js'])) {
                $App['footer_js'][] = $m[1];
                App::set($App);
            }
        }
    }
    $jsfile = '/' . ACTIVE_APP . '/' . $alias . '.unit/tpl/' . $templ . '.js';
    if (file_exists(UNITS_DIR . $jsfile) && filesize(UNITS_DIR . $jsfile) > 0) {
        $time = filemtime(UNITS_DIR . $jsfile);
        if (!isset($App['footer_js'])) $App['footer_js'] = array();
        $jsfile = '/apps/units' . $jsfile . '?' . $time;
        if (!in_array($jsfile, $App['footer_js'])) {
            $App['footer_js'][] = $jsfile;
            App::set($App);
        }
    }

    if (file_exists(UNITS_DIR . '/' . ACTIVE_APP . '/' . $alias . '.unit/controller.php')) {
        require_once UNITS_DIR . '/' . ACTIVE_APP . '/' . $alias . '.unit/controller.php';

        $typeArr = explode('_', $alias);
        $unit = '';
        foreach ($typeArr as $t) {
            $unit .= ucfirst(strtolower($t));
        }
        //dump($unit);
        $controller_name = $unit . '_Controller';
        if (!$sectionAlias)
            $sectionAlias = $alias;

        $app_controller = new $controller_name($alias, $sectionAlias);
        $method = ucfirst(strtolower($templ));

        if (method_exists($app_controller, $method)) {
            $app_controller->{$method}($arguments);
        }
        //echo $css;
        return $css . $app_controller->execute($templ);
    }
}

// Расширение переменных
function render_extend_vars(&$vars)
{

    /*
    $vars['app']['settings'] = require BASE_DIR . '/settings.php';
    foreach ($vars['app']['settings']['apps'] as $iterable_apps){
        $twig_app_extend = BASE_DIR . '/apps/' . $iterable_apps . '/twig.php';
        if(file_exists($twig_app_extend)) {
            App::set($iterable_apps);
            $vars['apps'][$iterable_apps] = require $twig_app_extend;
        }

    }
    */
}