<?php

namespace SE\CMS;

class Urls extends Base
{

    protected $tableName = "app_urls";
    protected $sortBy = "pattern";
    protected $sortOrder = "asc";

    protected function getSettingsFetch()
    {
        $result["select"] = 'au.id, au.alias, au.pattern, apt.title, au.type';
        $joins[] = array(
            "type" => "left",
            "table" => 'app_pages ap',
            "condition" => 'au.id_page=ap.id'
        );
        $joins[] = array(
            "type" => "left",
            "table" => 'app_pages_translate apt',
            "condition" => "(ap.id = apt.id_page AND `apt`.id_lang={$this->idLang})"
        );
        $result["joins"] = $joins;
        return $result;
    }

    protected function correctValuesBeforeFetch($items = array())
    {
        foreach ($items as &$item) {
            if ($item['type'] == 'item' || $item['type'] == 'group') {
                $url = convert_url($item['pattern']);

                if (!empty($url['vars'])) {
                    $item['args'] = json_encode(array_combine($url['vars'], array_fill(0, count($url['vars']), '')));
                }
            }
        }
        return $items;
    }
}