<?php

spl_autoload_register(function ($class) {
    if (0 === strpos($class, 'Twig')) {
        requireTwigClass($class);
    } else
    if (strpos($class, 'plugin_') !== false) {
        if ($handle = opendir(PLUGINS_DIR)) {
            while (false !== ($file = readdir($handle))) {
                if (is_dir(PLUGINS_DIR . '/' . $file)
                    && strpos($file, "plugin_") !== false && strpos(strtolower($class), $file) !== false
                ) {
                    if (file_exists(PLUGINS_DIR . '/' . $file . '/' . strtolower($class) . '.class.php'))
                        require_once PLUGINS_DIR . '/' . $file . '/' . strtolower($class) . '.class.php';
                }
            }
        }
        closedir($handle);
    } else {
		$file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', '/', $class) . '.php';
		if (file_exists($file)) {
			include_once $file;
		}
	}
});
