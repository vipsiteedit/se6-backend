<?php
if (file_exists(BASE_DIR . '/apps/config/config.db.php')) {
    require_once dirname(__FILE__) . '/core/db.php';
    $connection = require BASE_DIR . '/apps/config/config.db.php';
    DB::initConnection($connection);
    if (!empty($connection['SMSId'])) {
        define('SMSId', $connection['SMSId']);
    }
}
require_once dirname(__FILE__) . '/core/base.php';
require_once dirname(__FILE__) . '/core/system/base_controller.php';
require_once dirname(__FILE__) . '/core/system/controller.php';

$MEM = new Memcache;
$MEM->pconnect('127.0.0.1', 11211);


// Contants
define('CACHE_FOLDER', '/cache');
define('APPS_DIR', BASE_DIR . '/apps');
define('CACHE_DIR', APPS_DIR . CACHE_FOLDER);
define('TEMPLATES_DIR', APPS_DIR . '/templates');
define('UNITS_DIR', APPS_DIR . '/units');
define('IMAGES_DIR', APPS_DIR . '/images');
define('IMAGES_CACHE_DIR', APPS_DIR . CACHE_FOLDER);
define('APP_PLUGIN_DIR', APPS_DIR . '/plugins');
define('MODULES_DIR', FRAME_WORK_DIR . '/modules');
define('PLUGINS_DIR', FRAME_WORK_DIR . '/plugins');
define('INCLUDES_DIR', FRAME_WORK_DIR . '/includes');
define('TWIG_EXTENSIONS_DIR', MODULES_DIR . '/twig_extensions');
define('VENDORS_CORE_DIR', FRAME_WORK_DIR . '/core/vendor');
define('WWW_DIR', '/www');
if (!empty($_SERVER['REDIRECT_HTTPS']) && $_SERVER['REDIRECT_HTTPS'] == 'on')
    define('_HTTP_', 'https://');
else
    if (!empty($_SERVER['REQUEST_SCHEME'])) {
        define('_HTTP_', $_SERVER['REQUEST_SCHEME'].'://');
    } else {
        define('_HTTP_', ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http://' : 'https://'));
    }
define('_HOST_', _HTTP_ . $_SERVER['HTTP_HOST']);


// Require Vendors
//require FRAME_WORK_DIR . '/core/vendor/Twig/Autoloader.php';

// Connect modules
function requireTwigClass($psClassName)
{
    if (is_file($file = VENDORS_CORE_DIR.'/'.str_replace(array('_', "\0"), array('/', ''), $psClassName).'.php')) {
        require_once $file;
    }
}

spl_autoload_register(function($psClassName)
{
    if (0 === strpos($psClassName, 'Twig')) {
        requireTwigClass($psClassName);
        return true;
    } else
        if (strpos($psClassName, 'plugin_') !== false) {
            if (file_exists(APP_PLUGIN_DIR . '/' . strtolower($psClassName) . '.class.php')) {
                require_once APP_PLUGIN_DIR . '/' . strtolower($psClassName) . '.class.php';
            }
            elseif ($handle = opendir(PLUGINS_DIR)) {
                $isFindPlugin = false;
                while (false !== ($file = readdir($handle))) {
                    if (is_dir(PLUGINS_DIR . '/' . $file)
                        && strpos($file, "plugin_") !== false && strpos(strtolower($psClassName), $file) !== false
                    ) {
                        if (file_exists(PLUGINS_DIR . '/' . $file . '/' . strtolower($psClassName) . '.class.php')) {
                            require_once PLUGINS_DIR . '/' . $file . '/' . strtolower($psClassName) . '.class.php';
                            $isFindPlugin = true;
                        }
                    }
                }
                closedir($handle);
                if ($isFindPlugin) {
                    return true;
                }
            }
        }
    return;

});

$modules = glob(MODULES_DIR . '/*.module.php');
foreach ($modules as $module) {
    require_once $module;
}
require VENDORS_CORE_DIR . '/protection/GoogleAuthenticator.php';

// Start
require_once dirname(__FILE__) . '/core/render.php';
require_once dirname(__FILE__) . '/core/starter.php';