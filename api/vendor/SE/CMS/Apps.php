<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class Apps extends Base
{
    protected $tableName = "apps";

    public function info($id = null)
    {
        $ap = new DB('apps');
        $ap->addField('caption', 'varchar(255)');
        $ap->addField('from_email', 'varchar(255)');
        $ap->addField('sms_phone', 'varchar(20)');
        $ap->addField('sms_sender', 'varchar(40)');
        parent::info($id);

    }
}