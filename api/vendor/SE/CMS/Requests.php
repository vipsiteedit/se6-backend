<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class Requests extends Base
{
    protected $tableName = "app_requests";


    public function fetch($isId = false)
    {
        $u = new DB($this->tableName, 'ar');
        $cnrec = "(SELECT COUNT(*) FROM app_request_order WHERE id_request=`ar`.id) AS req_count";
        $newrec = "(SELECT COUNT(*) FROM app_request_order WHERE id_request=`ar`.id AND is_showing=0) AS new_count";

        $u->select("`ar`.*, $cnrec, $newrec");
        $u->groupBy('`ar`.id');
        $u->orderBy('sort');
        //$u->where('id_app=?', intval($this->input['seIdApp']));
        $this->result['items'] = $u->getList();
    }
}
