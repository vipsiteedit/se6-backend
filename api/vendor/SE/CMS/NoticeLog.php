<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class NoticeLog extends Base
{
    protected $tableName = "se_notice_log";

    public function fetch($isId = false)
    {
        $t = new DB("se_notice_log", "snl");
        $t->select("snl.*, DATE_FORMAT(snl.created_at, '%d.%m.%Y %H:%i') date_display");
        $items = $t->getList($this->limit, $this->offset);
        $count = $t->getListCount();
        foreach ($items as &$item)
            $item["content"] = strip_tags($item["content"]);
        $this->result["items"] = $items;
        $this->result["count"] = $count;
        return $items;
    }

}