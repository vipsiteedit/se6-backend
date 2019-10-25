<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

ini_set('log_errors', 'On');
ini_set('error_log', $_SERVER["DOCUMENT_ROOT"] . '/api/php_errors.log');

$allowedMethods = array('FETCH', 'POST', 'DELETE', 'SAVE', 'INFO', 'GET', 'ADDPRICE', 'TRANSLIT', 'UPLOAD',
        'CHECKNAMES', 'SORT', 'EXPORT', 'IMPORT', 'LOGOUT', 'EXEC','ITEMS');
$allowedMethods = implode(",", $allowedMethods);

$headers = getallheaders();

if (!empty($headers['Secookie'])) {
    session_id($headers['Secookie']);
}
define("HOSTNAME", $_SERVER["HTTP_HOST"]);
define('DOCUMENT_ROOT', $_SERVER["DOCUMENT_ROOT"]);

chdir(DOCUMENT_ROOT);
date_default_timezone_set("Europe/Moscow");


define('API_ROOT', DOCUMENT_ROOT . '/api/');
define('API_ROOT_URL', "http://" . $_SERVER['SERVER_NAME'] . "/api");

function writeLog($data)
{
    if (!is_string($data)) {
        $data = print_r($data, true);
    }
    $file = fopen(API_ROOT. "debug.log", "a+");
    $data = date('[Y-m-d H:i:s] ') . $data . "\n";
    fputs($file, $data);
    fclose($file);
}

require_once DOCUMENT_ROOT . '/siteedit/inc.php';

require_once API_ROOT . "vendor/autoload.php";

include API_ROOT . "version.php";

$apiMethod = $_SERVER['REQUEST_METHOD'];
$apiClass = parse_url($_SERVER["REQUEST_URI"]);
$apiClass = str_replace("api/", "", trim($apiClass['path'], "/"));

if (strpos($apiClass, "/Auth"))
    $apiClass = "Auth";


$origin = !empty($headers['Origin']) ? $headers['Origin'] : $headers['origin'];
if (!empty($origin)) {
    $url = parse_url($origin);
    if ($url) {
        header("Access-Control-Allow-Origin: http://{$url['host']}");
        if ($url['host'] == 'localhost' && $url['port'] == 1337)
            header("Access-Control-Allow-Origin: http://localhost:1337");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Project, Secookie");
        header("Access-Control-Allow-Methods: $allowedMethods");
    }
    if ($apiMethod == "OPTIONS")
        exit;
}

$update = new \SE\Update();

if ($update->checkBuild()) {
    $update->forceUpdate();
}

if ($apiClass == "Auth" && strtolower($apiMethod) == "logout") {
    $_SESSION = array();
    session_destroy();
    echo "Session destroy!";
    exit;
}

if ($apiClass == "Auth" && strtolower($apiMethod) == "get") {
    if (empty($_SESSION['isAuth'])) {
        header("HTTP/1.1 401 Unauthorized");
        echo 'Сессия истекла! Необходима авторизация!';
        exit;
    }
}

$dbConfig = require DOCUMENT_ROOT . '/apps/config/config.db.php';

if (empty($dbConfig)){
    header("HTTP/1.1 401 Unauthorized");
    echo 'Сессия истекла! Необходима авторизация!';
    exit;
}

if ($apiClass != "Auth" && empty($_SESSION['isAuth']) && !($apiClass == "CMS/Image" && strtolower($apiMethod) == "get")) {
    header("HTTP/1.1 401 Unauthorized");
    echo 'Сессия истекла! Необходима авторизация!';
    exit;
}

$phpInput = file_get_contents('php://input');

$apiObject = $apiClass;
$apiClass = "\\SE\\" . str_replace("/", "\\", $apiClass);

if (!class_exists($apiClass)) {
    header("HTTP/1.1 501 Not Implemented");
    echo "Объект '{$apiObject}' не найден!";
    exit;
}

if (!method_exists($apiClass, $apiMethod)) {
    header("HTTP/1.1 501 Not Implemented");
    echo "Метод'{$apiMethod}' не поддерживается!";
    exit;
}

$apiObject = new $apiClass($phpInput);

if ($apiObject->initConnection($dbConfig)) {
    if ($apiClass && $apiClass != "\SE\Auth"){
        $apiObject->initPath();
    }
    $apiObject->$apiMethod();
}

$apiObject->output();
