<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class UserFieldGroup extends Base
{
    protected $tableName = "se_userfields_group";
    protected $sortBy = "sort";
    protected $sortOrder = "asc";
}
