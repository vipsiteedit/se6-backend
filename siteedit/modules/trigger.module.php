<?php

class Trigger
{
    private static $instance = null;
    private $users = array();

    public function __construct()
    {

    }
	
	public function setUsers($ids = array())
	{
		$auth = new DB('se_user');
		$auth->select('email, phone, person_name, username');
		$auth->where("id IN (?)", join(',', $ids));
		$this->users = $auth->getList();
	}

    public function run($event, $args = array())
    {
        $notices = $this->getNotices($event);
        foreach ($notices as $notice) {
            $values = $args;
			if (empty($this->users)) {
				if ($notice['target'] == 'email')
					$this->users[] = array('email'=>$notice['recipient']);
				if ($notice['target'] == 'sms')
					$this->users[] = array('phone'=>$notice['recipient']);	
			}
			foreach($this->users as $user) {
				if ($user['username'])
					$values['username'] = $user['username'];
				if ($user['personName'])
					$values['personname'] = $user['personName'];
				if ($user['email'])
					$values['email'] = $user['email'];
				if ($user['phone'])
					$values['phone'] = $user['phone'];
				if ($notice['target'] == 'email') 
					$notice['recipient'] = $user['email'];
				if ($notice['target'] == 'sms') 
					$notice['recipient'] = $user['phone'];	
				$this->notify($notice, $values);
			}
		}	
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getNotices($event)
    {
        $t = new DB("se_notice", "n");
        $t->select("n.sender, n.recipient, n.subject, n.content, n.target");
        $t->innerjoin("se_notice_trigger ntr", "ntr.id_notice = n.id");
        $t->innerjoin("se_trigger t", "t.id = ntr.id_trigger");
        $t->where("t.code = '?'", $event);
        $t->andWhere("n.is_active");
        $t->groupby("n.id");

        return $t->getList();
    }

    public function notify($notice, $args = array())
    {
		foreach($this->users as $user) {
			$macros = new Macros($args);
			foreach ($notice as $key => &$value) {
				$value = $macros->exec($value);
			}

			$result = false;
			$t = new DB("se_notice_log");
			$t->sender = $notice['sender'];
			$t->recipient = $notice['recipient'];
			$t->target = $notice['target'];
			$t->content = $notice['content'];
			$idNotice = $t->save();

			if ($idNotice) {
				switch ($notice['target']) {
					case 'email':
						$mail = new plugin_email($notice['subject'], $notice['recipient'], $notice['sender']);
						$mail->addtext($notice['content'], 'text/html');
						$result = $mail->send();
						break;
					case 'sms':
						$result = (new plugin_sms())->sms_send($notice['recipient'], strip_tags($notice['content']), $notice['sender']);
						break;
					case 'telegram':
						//$result = (new plugin_telegram($notice['recipient'], $notice['sender']))->send(strip_tags($notice['content']));
						break;
				}
				$t->where('id=?', $idNotice);
				$t->status = (int)$result;
				$t->save();
			}
		}
        return $result;
    }

}