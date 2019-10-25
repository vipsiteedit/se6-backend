<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception as Exception;

class Trigger extends Base
{
    protected $tableName = "se_trigger";

    public function getAddInfo()
    {
        return array ("notices" => $this->getNotice());
    }

    private function getNotice()
    {
        $result = [];
        $notices = (new Notice())->fetch();
        $u = new DB("se_notice_trigger");
        $u->select("id_notice");
        $u->where("id_trigger = ?", $this->input["id"]);
        $items = $u->getList();
        foreach ($notices as $notice) {
            $isChecked = false;
            foreach ($items as $item)
                if ($isChecked = ($notice["id"] == $item["idNotice"]))
                    break;
            $notice["isChecked"] = $isChecked;
            $result[] = $notice;
        }
        return $result;
    }

    protected function saveAddInfo()
    {
        return $this->saveNotice();
    }

    private function saveNotice()
    {
        $notices = $this->input["notices"];
        $noticesNew = [];
        foreach ($notices as $notice)
            if ($notice["isChecked"])
                $noticesNew[] = $notice;
        try {
            foreach ($this->input["ids"] as $id)
                DB::saveManyToMany($id, $noticesNew,
                    array("table" => "se_notice_trigger", "key" => "id_trigger", "link" => "id_notice"));
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить уведомления триггера!";
            throw new Exception($this->error);
        }
    }
}