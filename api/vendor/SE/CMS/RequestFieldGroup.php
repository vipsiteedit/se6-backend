<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class RequestFieldGroup extends Base
{
    protected $tableName = "app_request_fieldsgroup";
    protected $sortBy = "sort";
    protected $sortOrder = "asc";

}
