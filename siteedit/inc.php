<?php
error_reporting(E_PARSE | E_ERROR);
session_start();
// UTM
if (isset($_GET['utm_source'])) {
    foreach($_GET as $gname=>$gvalue) {
        if (strpos($gname, 'utm_')===0) {
            $_SESSION['_UTM_'][$gname] = $gvalue;
        }
    }
}
define('BASE_DIR', $_SERVER['DOCUMENT_ROOT']);
define ('FRAME_WORK_DIR', BASE_DIR . '/siteedit');
require FRAME_WORK_DIR . '/loader.php';
