<?php
class Json{
    static public function json_encode($data){
        return preg_replace_callback('/\\\\ud([89ab][0-9a-f]{2})\\\\ud([c-f][0-9a-f]{2})|\\\\u([0-9a-f]{4})/i', function($val){
            return html_entity_decode(
                empty($val[3])?
                    sprintf('&#x%x;', ((hexdec($val[1])&0x3FF)<<10)+(hexdec($val[2])&0x3FF)+0x10000):
                    '&#x'.$val[3].';',
                ENT_NOQUOTES, 'utf-8'
            );
        }, json_encode($data));
    }
}


function ml($text, $s = '', $name)
{
    GLOBAL $__I18;
    // Читаем из таблицы app_translate

    $app = App::get();
    $app['idLang'] = (!empty($app['idLang'])) ? $app['idLang'] : '1';
    //$pagealias = (!empty($app['vars']['url']['alias'])) ? $app['vars']['url']['alias'] : '';
    $lfolder = APPS_DIR . '/translate/' . $app['app'];
    $lngf = $lfolder . '/' . str_replace('.','-',$name) .'.lng';

    if (!is_dir($lfolder)) {
        mkdir($lfolder, 0777, 1);
    }
    if (file_exists($lngf) && empty($__I18[$name])) {
        $__I18[$name] = json_decode(file_get_contents($lngf), true);
    }
    $hash = (!$s) ? md5($text) : $s;
    if (!empty($__I18[$name][$hash][$app['idLang']])) {
        $translation = $__I18[$name][$hash][$app['idLang']];
    } else {
        $translation = $text;
        $__I18[$name][$hash][$app['idLang']] = $text;
        file_put_contents($lngf, Json::json_encode($__I18[$name]));
    }
    return $translation;
}