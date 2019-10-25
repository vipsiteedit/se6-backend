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
        $thisIP = detect_ip();
        if (!trim($params['username']) || !preg_match('/^[A-z0-9\.@_\-]{5,80}$/', $params['username'])) {
            return array('status' => 'error', 'errorInfo'=>'Missing username', 'errorCode'=>2);
        }
         if ( !empty($_SESSION['APP_REG_IP'][$thisIP]) && $_SESSION['APP_REG_IP'][$thisIP] > 5) {
             return array('status' => 'error', 'errorInfo'=>'DDos IP', 'errorCode'=>10);
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
        $params['username'] = strtolower($params['username']);
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
            $params['idAffiliate'] = intval($_SESSION['SE_AFFILIATE']);
        }
		$bd->setValuesFields($params);
        if ($id = $bd->save()) {
            if (!empty($params['permissions'])) {
                $this->setUserPermission($id, $params['permissions']);
            }
            $_SESSION['APP_REG_IP'][$thisIP] ++;
            DB::commit();
            unset($params['permissions']);
            $vars = array('password'=>$password);
            $vars['thisnamesite'] = _HOST_;

            $email = new trigger();
            if ($confirm) {
                $verify = md5($tmppassw . $id);
                $vars['registration-confirm-link'] = _HOST_  . '/verify.php?email=' . $verify;
                $email->setUsers(array($id));
				$email->run('regconfirm', $vars);
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
    $username = strtolower(trim($username));
    if (!preg_match('/^[A-z0-9\.@_\-]{5,80}$/', $username)) {
            return array('status'=>'error', 'errorInfo'=>'Account not found', 'errorCode'=>1);
        }
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
            $email = new trigger();
            $vars['registration-confirm-link'] = _HOST_  . '/verify.php?remember=' . $verify;
            $email->setUsers(array($id));
			$email->run('accessrecovery', $vars);
            return array('status'=>'success', 'result'=>1);
        } else {
            return array('status'=>'error', 'errorInfo'=>'Account not found', 'errorCode'=>1);
        }
    }

    public function validateSMS($idUser = false)
    {
        $idUser = ($idUser) ? $idUser : seUserId();
        if (defined('SMSId') && $idUser) {
            try {
                $passw = rand(1, 9) . rand(1, 9) . rand(1, 9) . rand(1, 9);
                $uf = new DB('se_user');
                $uf->select('phone, time_last_send, tmppassw, send_try');
                $uf->where("id=?", $idUser);
                $result = $uf->fetchOne();

                if ($result['timeLastSend'] + 86400 < time()) {
                    DB::exec("UPDATE se_user SET `send_try`=0 WHERE id='{$idUser}'");
                    $result['sendTry'] = 0;
                }

                if ($result['sendTry'] > 5 || ($result['timeLastSend']+600>time() && !empty($_SESSION['CURR_SMS']) && !empty($result['tmppassw']))) {
                    return array('status' => 'success', 'result' => 1);
                }
                $time = time();
                $tmppassw = md5($passw);
                DB::exec("UPDATE se_user SET `tmppassw`='{$tmppassw}', time_last_send='{$time}', `send_try`=`send_try`+1 WHERE id='{$idUser}'");

                $sms = new plugin_sms(SMSId);
                $app = App::get();
                $response = $sms->sms_send(str_replace('+', '', $result['phone']), $passw, $app['main']['smsSender']);
                if ($response['code'] == 100) {
                    $_SESSION['CURR_SMS'] = $passw;
                    return array('status' => 'success', 'result' => $response['ids'][0]);
                } else {
                    return array('status' => 'error', 'code' => $response['code']);
                }
            } catch (Exception $e) {
                return array('status' => 'error', 'code' => 0);
            }
        }
    }

    public function confirmSMS($code = '', $idUser = false)
    {
        $idUser = ($idUser) ? $idUser : seUserId();
        try {
            $code = md5($code);
            $uf = new DB('se_user');
            $uf->select('id');
            $uf->where("id=?", $idUser);
            $uf->andWhere("tmppassw='?'", $code);
            $result = $uf->fetchOne();
            if (!empty($result['id'])) {
                DB::exec("UPDATE se_user SET `tmppassw`='',`phone_confirm`='1', `is_active`='1' WHERE id='{$idUser}'");
                return array('status' => 'success', 'result' => $result['id']);
            } else {
                return array('status' => 'error');
            }
        } catch (Exception $e) {
            return array('status' => 'error');
        }
    }


    public function validateEmail($hash)
    {
        try {
            $uf = new DB('se_user');
            $uf->select('id');
            $uf->where("MD5(CONCAT_WS('',`tmppassw`,`id`))='?'", $hash);
            $result = $uf->fetchOne();
            if (!empty($result)) {
                DB::exec("UPDATE `se_user` SET `email_confirm`=1, `send_try`=0, `is_active`=1, `tmppassw`='' WHERE `id`={$result['id']}");
                $_SESSION['AUTH_USER_TRY'] = 0;
                $this->signIn($result['id']);
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public function editUser($idUser, $data = array())
    {
        try {
            $bd = new DB('se_user');
            $bd->addField('ga_secret', 'varchar(255)');
            //$bd->where('id=?', $idUser);
            //$user = $bd->fetchOne();
            $data['id'] = $idUser;
            /*
            $qrCode = '';
            if (empty($user['gaSecret']) && $data['dblAuth']) {
                $ga=new GoogleAuthenticator();
                $data['gaSecret'] = $ga->generateSecret();
                list($login) = explode('@', $user['username']);
                $qrCode = $ga->getQrCode($login, $_SERVER['HTTP_HOST'], $data['gaSecret']);
            } else
            if (isset($data['dblAuth']) && !$data['dblAuth']) {
                $data['gaSecret'] = '';
            }
            */
            $bd->setValuesFields($data);
            if ($id = $bd->save()) {
                $this->signIn($id);

                return array('status'=>'success', 'result'=>1);
            } else {
                return array('status'=>'error', 'errorInfo'=>'Save error', 'errorCode'=>1);
            }
        } catch (Exception $e) {
            return array('status'=>'error', 'errorInfo'=>'Save error', 'errorCode'=>1);
        }
    }

    public function getUsersAffiliate($idUser)
    {
        try {
            $user = new DB('se_user', 'su');
            $user->select('su.id, su.username, su.person_name, su.email, su.phone, su.created_at');
           // $user->leftJoin('se_user_permission aup', 'su.id=aup.id_user');
            $user->where("`id_affiliate`=?", intval($idUser));
            //$user->andWhere("is_active = 1");
            return $user->getList();
        } catch (Exception $e) {
            return array();
        }
    }

    public function setUserValues($idUser, $values)
    {
        try {
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
            return true;
        } catch (Exception $e) {
            return false;
        }

    }

    public function getUserValues($idUser)
    {
        $uf = new DB('se_user_values', 'suv');
        $uf->select('suf.code, suv.value');
        $uf->innerJoin('se_userfields suf', 'suv.id_field=suf.id');
        $uf->where('suv.id_user=?', intval($idUser));
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
        $gr->where('id_user=?', intval($idUser));
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

    public function checkUserName($username, $password)
    {
        $username = strtolower($username);
        if (!trim($username) || !preg_match('/^[A-z0-9\.@_\-]{5,80}$/', $username)) {
            return array();
        }
        $password = $username.$password;
        $user = new DB('se_user', 'su');
        $user->select('su.*, GROUP_CONCAT(`aup`.id_permission) AS `permissions`');
        $user->leftJoin('se_user_permission aup', 'su.id=aup.id_user');
        $user->where("(`password`='?' OR `tmppassw`='?')", md5($password));
        $user->andWhere("LOWER(`username`) = '?'", $username);
        //$user->andWhere("is_active = 1");
        return $user->fetchOne();
    }

    // Авторизация пользователя
    public function login($login, $password, $code = '')
    {
        $login = strtolower($login);
        if (!trim($login) || !preg_match('/^[A-z0-9\.@_\-]{5,80}$/', $login)) {
            return array('status'=>'error', 'errorInfo'=>'User not found', 'errorCode'=> '-10', 'try'=>intval($_SESSION['AUTH_USER_TRY']));
        }
        if ($_SESSION['AUTH_USER_TRY'] > 3 && false) {
            $this->remember($login);
            return array(
                'status' => 'error',
                'errorInfo' => 'Exceeded attempts',
                'errorCode' => '-11',
                'try' => intval($_SESSION['AUTH_USER_TRY'])
            );
        }
        $result = $this->checkUserName($login, $password);
        if ($result['id']) {
            if ($result['dblAuth'] && empty($result['gaSecret']) && empty($code)) {
                $this->remember($login);
                //$this->validateSMS($result['id']);
                return array(
                    'status' => 'error',
                    'errorInfo' => 'Validate account',
                    'errorCode' => '-12',
                    'try' => intval($_SESSION['AUTH_USER_TRY'])
                );
            }
            if ($result['dblAuth'] && !empty($result['gaSecret']) && empty($code)) {
                //$this->validateSMS($result['id']);
                return array(
                    'status' => 'success',
                    'idUser' => $result['id'],
                    'double' => $result['dblAuth'],
                    'ip' => $this->IP
                );
            } else {
                if (!empty($code)) {
                    $ga=new GoogleAuthenticator();
                    $gacode=$ga->getCode($result['gaSecret']);


                    //$conf = $this->confirmSMS($code, $result['id']);
                    //$conf['status']!='success'
                    if ($gacode != $code) {
                        return array(
                            'status'=>'error',
                            'errorInfo'=>'User not found',
                            'errorCode'=> '-10',
                            'try'=>intval($_SESSION['AUTH_USER_TRY'])
                        );
                    }
                }

                @$result['permissions'] = explode(',', $result['permissions']);
                if ($this->IP != $result['ip']) {
                    // Отсылаем предупредительное письмо
                    $values = array(
                        'ip' => $this->IP,
                        'email' => $result['email'],
                        'phone' => $result['ip'],
                        'personname' => $result['personName']
                    );
                    $email = new trigger();
                    $email->setUsers(array($result['id']));
					$email->run('loginfromip', $values);
                    DB::exec("UPDATE `se_user` SET `ip`='{$this->IP}' WHERE `id`={$result['id']}");
                }
                $result['IP'] = $this->IP;
                $_SESSION['AUTH_USER'] = $this->user = $result;
                return array('status' => 'success', 'idUser' => $result['id'], 'double' => false, 'ip' => $this->IP);
            }
        }
        $this->user = array();
        $_SESSION['AUTH_USER_TRY'] += 1;
        if ($_SESSION['AUTH_USER_TRY'] > 5) {
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