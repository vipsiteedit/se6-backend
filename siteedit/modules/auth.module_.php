<?php

// Класс авторизации
class auth
{
    private $session = array();
    private $IP;
    private $SID;
    private $max_time = 84000;
    private $user;

    public function __construct()
    {
        //$this->createDb();
        $this->IP = $_SERVER['REMOTE_ADDR'];
        $this->SID = session_id();
        if (!isset($_SESSION['AUTH_USER_TRY'])) {
            $_SESSION['AUTH_USER_TRY'] = 0;
        }
        $this->user = $this->getUser();
    }

    public function registration($params, $confirm = false)
    {
        if (!trim($params['username'])) {
            return array('status' => 'error', 'errorInfo'=>'Missing username', 'errorCode'=>2);
        }
        if (!trim($params['personName'])) {
            return array('status' => 'error', 'errorInfo'=>'Missing person name', 'errorCode'=>6);
        }
        if (isset($params['password']) && !trim($params['password'])) {
            return array('status' => 'error', 'errorInfo'=>'Missing password', 'errorCode'=>3);
        } else {
            if (empty($params['password'])) {
                $params['password'] = substr(md5(time()), 1, 8);
            }
        }
        if (strlen($params['password']) < 6) {
            return array('status' => 'error', 'errorInfo'=>'Short password', 'errorCode'=>5);
        }
        $params['isActive'] = ($confirm) ? 0 : 1;

        if ($this->checkNameUser($params['username'])) {
            return array('status' => 'error', 'errorInfo'=>'Person is already registered', 'errorCode'=>1);
        }
        if (empty($params['permissions'])) $params['permissions'] = array('user');
        DB::beginTransaction();
        $bd = new DB('se_user');
        $bd->addField('ip', 'varchar(15)');
        $password = $params['password'];
        $tmppassw = substr(md5($params['password'].time()), 0 ,8);
        $params['password'] = md5($params['username'].$params['password']);
        $params['ip'] = detect_ip();
        $tmppassw = md5($params['email'] . $params['password']);
        $params['tmppassw'] = $tmppassw;
        if (isset($_SESSION['SE_AFFILIATE'])) {
            $params['iAffiliate'] = $_SESSION['SE_AFFILIATE'];
        }
		$bd->setValuesFields($params);
        if ($id = $bd->save()) {
            if (!empty($params['permissions'])) {
                $this->setUserPermission($id, $params['permissions']);
            }
            DB::commit();
            unset($params['permissions']);
            $vars = array('password'=>$password);
            $vars['thisnamesite'] = _HOST_;

            $email = new plugin_emailtemplates();
            if ($confirm) {
                $verify = md5($tmppassw . $id);
                $vars['registration-confirm-link'] = _HOST_  . '/verify.php?email=' . $verify;
                $email->sendmail('regconfirm', $vars, $id);
            } else {
                $this->signIn($id);
            }
            //$email->sendmail('reguser', $vars, $id);
            //$email->sendmail('regadm', $vars, $id);


            return array('status'=>'success', 'idUser'=>$id);
        }
        DB::rollBack();
        return array('status' => 'error', );
    }

    public function remember($username)
    {
        if (empty($username)) {
            return array('status'=>'error', 'errorInfo'=>'Missing username', 'errorCode'=>2);
        }
        $uf = new DB('se_user');
        $uf->select('id');
        $uf->where("LOWER(`username`)='?'", $username);
        $result = $uf->fetchone();
        if (!empty($result)) {
            $id = $result['id'];
            $tmppassw = md5($username.time());
            DB::exec("UPDATE se_user SET `tmppassw`='{$tmppassw}' WHERE id='{$id}'");
            $verify = md5($tmppassw . $id);
            $email = new plugin_emailtemplates();
            $vars['registration-confirm-link'] = _HOST_  . '/verify.php?remember=' . $verify;
            $email->sendmail('accessrecovery', $vars, $id);
            return array('status'=>'success', 'result'=>1);
        } else {
            return array('status'=>'error', 'errorInfo'=>'Account not found', 'errorCode'=>1);
        }
    }

    public function validateEmail($hash)
    {
        $uf = new DB('se_user');
        $uf->select('id');
        $uf->where("MD5(CONCAT_WS('',`tmppassw`,`id`))='?'", $hash);
        $result = $uf->fetchOne();
        if (!empty($result)) {
            DB::exec("UPDATE `se_user` SET `email_confirm`=1, `is_active`=1, `tmppassw`='' WHERE `id`={$result['id']}");
            $this->signIn($result['id']);
            return true;
        }
    }

    public function editUser($idUser, $data = array())
    {
        $bd = new DB('se_user');
        $data['id'] = $idUser;
        $bd->setValuesFields($data);
        if ($id = $bd->save()) {
            if (!empty($data['personName']))
                $_SESSION['AUTH_USER']['personName'] = $data['personName'];
            return array('status'=>'success', 'result'=>1);
        } else {
            return array('status'=>'error', 'errorInfo'=>'Save error', 'errorCode'=>1);
        }
    }

    public function getUsersAffiliate($idUser)
    {
        $user = new DB('se_user', 'su');
        $user->select('su.id, su.username, su.person_name, su.email, su.phone, su.created_at');
       // $user->leftJoin('se_user_permission aup', 'su.id=aup.id_user');
        $user->where("`id_affiliate`=?", intval($idUser));
        $user->andWhere("is_active = 1");
        return $user->getList();
    }

    public function setUserValues($idUser, $values)
    {
        $uf = new DB('se_userfields');
        $uf->select('id, code');
        $uf->where("sect='person'");
        $fields = $uf->getList();
        $fdata = array();
        foreach($values as $name=>$value) {
            foreach($fields as $f) {
                if ($f['code'] == $name) {
                    $fdata[] = array('idUser'=>$idUser, 'idField'=>$f['id'], 'value'=>$value);
                }
            }
        }
        $uf = new DB('se_user_values');
        $uf->select('id, id_field');
        $uf->where('id_user=?', $idUser);
        $ufields = $uf->getList();

        foreach($fdata as &$fu) {
            foreach($ufields as $uval) {
                if ($uval['idField'] == $fu['idField']) {
                    $fu['id'] = $uval['id']; break;
                }
            }
        }
        if (!empty($fdata)) {
            $uf = new DB('se_user_values');
            foreach($fdata as $fu) {
                $uf->setValuesFields($fu);
                $uf->save();
            }
        }

    }

    public function getUserValues($idUser)
    {
        $uf = new DB('se_user_values', 'suv');
        $uf->select('suf.code, suv.value');
        $uf->innerJoin('se_userfields suf', 'suv.id_field=suf.id');
        $uf->where('suv.id_user=?', $idUser);
        $result = array();
        foreach ($uf->getList() as $val) {
            $result[$val['code']] = $val['value'];
        }
        return $result;
    }

    public function setUserPermission($idUser, $groups = array())
    {
        $grIds = array();
        foreach($this->getPermission() as $group) {
            if (in_array($group['name'], $groups)) {
                $grIds[] = $group['id'];
            }
        }
        $gr = new DB('se_user_permission');
        $gr->select('id, id_permission');
        $gr->where('id_user=?', $idUser);
        //$gr->andWhere('id_group IN (?)', join(',', $grIds));
        $thislist = $gr->getList();

        $dellist = array();
        foreach($thislist as $item) {
            if ($idArr = array_search($item['idPermission'], $grIds)) {
                unset($grIds[$idArr]);
            } else {
                $dellist[] = $item['id'];
            }
        }
        if (!empty($dellist)) {
            $gr = new DB('se_user_permission');
            $gr->where('id IN (?)', join(',', $dellist));
            $gr->deleteList();
        }
        foreach($grIds as $id) {
            $gr = new DB('se_user_permission');
            $gr->setValuesFields(array('idUser'=>$idUser, 'idPermission'=>$id));
            $gr->save();
        }
    }

    public function getPermission()
    {
        $gr = new DB('app_permission');
        $gr->select('*');
        return $gr->getList();

    }

    public function setPermission($name, $title)
    {
        $gr = new DB('app_permission');
        $gr->select('id');
        $gr->where("name='?'", $name);
        $fnd = $gr->fetchOne();

        $item = array('name'=>$name, 'title'=>$title);
        if (!empty($fnd)) {
            $item['id'] = $fnd['id'];
        }
        $gr = new DB('app_permission');
        $gr->setValuesFields($item);
        $gr->save();
    }

    // Авторизация пользователя
    public function login($login, $password)
    {
        if ($_SESSION['AUTH_USER_TRY'] > 3) {
            //return array('status' => 'error', 'errorInfo' => 'User not found', 'errorCode' => '-10', 'try' => intval($_SESSION['AUTH_USER_TRY']));
        }
        $password = $login.$password;
        $user = new DB('se_user', 'su');
        $user->select('su.*, GROUP_CONCAT(`aup`.id_permission) AS `permissions`');
        $user->leftJoin('se_user_permission aup', 'su.id=aup.id_user');
        $user->where("(`password`='?' OR `tmppassw`='?')", md5($password));
        $user->andWhere("LOWER(`username`) = '?'", strtolower($login));
        $user->andWhere("is_active = 1");
        $result = $user->fetchOne();
        if ($result['id']) {
            @$result['permissions'] = explode(',', $result['permissions']);
            if ($this->IP != $result['ip']) {
                // Отсылаем предупредительное письмо
                $values = array(
                    'ip'=>$this->IP,
                    'email' => $result['email'],
                    'phone' =>$result['ip'],
                    'personname' => $result['personName']
                );
                $email = new plugin_emailtemplates();
                $email->sendmail('loginfromip', $values, $result['id']);
                DB::exec("UPDATE `se_user` SET `ip`='{$this->IP}' WHERE `id`={$result['id']}");
            }
            $result['IP'] = $this->IP;
            $_SESSION['AUTH_USER'] = $this->user = $result;
            return array('status'=>'success', 'idUser'=>$result['id']);
        }
        $this->user = array();
        $_SESSION['AUTH_USER_TRY'] += 1;
        if ($_SESSION['AUTH_USER_TRY'] > 3) {
            $this->remember($login);
        }
        return array('status'=>'error', 'errorInfo'=>'User not found', 'errorCode'=> '-10', 'try'=>intval($_SESSION['AUTH_USER_TRY']));
    }

    private function signIn($id)
    {
        $user = new DB('se_user', 'su');
        $user->select('su.*, GROUP_CONCAT(`aup`.id_permission) AS `permissions`');
        $user->leftJoin('se_user_permission aup', 'su.id=aup.id_user');
        $user->where('su.id=?', $id);
        $user->andWhere("is_active = 1");
        $result = $user->fetchOne();
        if (!empty($result['id'])) {
            @$result['permissions'] = explode(',', $result['permissions']);
            $result['IP'] = $this->IP;
            $_SESSION['AUTH_USER'] = $this->user = $result;
            return true;
        }
    }

    public function checkSession()
    {
        if (!empty($_SESSION['AUTH_USER']['IP']) && $_SESSION['AUTH_USER']['IP'] === $this->IP) {
            return true;
        } else {
           if (isset($_SESSION['AUTH_USER']))
               unset($_SESSION['AUTH_USER']);
        }
    }

    public function getUser()
    {
        if ($this->checkSession()) {
            return $_SESSION['AUTH_USER'];
        } else {
            return array();
        }
    }

    private function checkNameUser($nameuser)
    {
        $user = new DB('se_user');
        $user->select('id');
        $user->andWhere("LOWER(`username`) = '?'", $nameuser);
        $result = $user->fetchOne();
        return ($result['id'] > 0);
    }

    public function logout()
    {
        unset($_SESSION['AUTH_USER']);
    }

    public function checkPermission($idPermission)
    {
        //print_r($_SESSION['AUTH_USER']);
        if (!empty($this->user['permissions']) && in_array($idPermission, $this->user['permissions'])) {
            return true;
        }
    }



}

function seUserLogin()
{
    if (!empty($_SESSION['AUTH_USER']))
        return $_SESSION['AUTH_USER']['username'];
}



function seUserId()
{
    if (!empty($_SESSION['AUTH_USER']))
        return $_SESSION['AUTH_USER']['id'];
}

function seUserName()
{
    if (!empty($_SESSION['AUTH_USER']))
        return $_SESSION['AUTH_USER']['personName'];
}

function seUserEmail()
{
    if (!empty($_SESSION['AUTH_USER']))
        return $_SESSION['AUTH_USER']['email'];
}

function seUserPhone()
{
    if (!empty($_SESSION['AUTH_USER']))
        return $_SESSION['AUTH_USER']['phone'];
}


/*

function seUserGroup()
{
    global $SESSION_VARS;
    return intval($SESSION_VARS['GROUPUSER']);
}

//����� ����� ID �������� ������������
function seUserId()
{
    global $SESSION_VARS;
    return intval($SESSION_VARS['IDUSER']);
}

//����� ��� ������ �������� ������������
function seUserGroupName()
{
    global $SESSION_VARS;
    return $SESSION_VARS['ADMINUSER'];
}

//����� �.�.� �������� ������������
function seUserName()
{
    global $SESSION_VARS;
    $user_id = seUserId();
    if ($user_id && SE_DB_ENABLE) {
        $person = new sePerson();
        $person->find($user_id);
        return trim($person->last_name . ' ' . $person->first_name . ' ' . $person->sec_name);
    } else
        if (seUserGroup()) {
            return 'Administrator';
        }
}

// ����� ����� �������� ������������
function seUserLogin()
{
    global $SESSION_VARS;
    return $SESSION_VARS['AUTH_USER'];
}

function seUserEmail()
{
    global $SESSION_VARS;
    return $SESSION_VARS['EMAIL'];
}

function seUserRole($namerole, $user_id = 0)
{
    if (!SE_DB_ENABLE) return false;
    if (!$user_id) $user_id = seUserId();
    $user = new seTable('se_user_group', 'sug');
    $user->innerjoin('se_group sg', 'sug.group_id=sg.id');
    $user->where("sug . user_id =?", $user_id);
    $user->andwhere("sg . name = '?'", $namerole);
    $user->fetchOne();
    return $user->isFind();
}
*/