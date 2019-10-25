<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class Links extends Base
{
    protected $tableName = "app_urls";

    public function fetch($isId = false)
    {
        $u = new DB($this->tableName, 'au');
        $u->orderBy($this->sortBy, $this->sortOrder == 'desc');
        $u->where("au.type='link'");
        $u->andWhere("au.id_app=?", $this->input['seIdApp']);
        $this->result["items"] = $this->correctValuesBeforeFetch($u->getList($this->limit, $this->offset));
        $this->result["count"] = $u->getListCount();

        return $this->result;
    }

    public function save() {
        $this->input['type'] = 'link';
        if (!$this->input['id']) $this->input['alias'] = 'l' . $this->input['alias'];
        $this->input['id_app'] = $this->input['seIdApp'];
        return parent::save();
    }
}
