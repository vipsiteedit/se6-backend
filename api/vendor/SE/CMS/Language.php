<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class Language extends Base
{
    protected $tableName = "app_language";

    public function fetch()
    {
        parent::fetch();
        //writeLog('idLang:'.$this->idLang);
        //writeLog($result);
        foreach($this->result['items'] as &$item) {
            $item['isMain'] = ($item['id'] == $this->idLang);
        }
        //writeLog($result);
        return $this->result;
    }
}
