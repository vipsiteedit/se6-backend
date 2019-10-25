<?php

class plugin_userpermission {
	private $masks = false;
	public function __construct($idUser = 0)
	{
		$idUser = ($idUser) ? $idUser : seUserId();
		$u = new DB('se_permission_object', 'po');
        $u->select('po.*, por.mask');
        $u->leftJoin('se_permission_object_role por', 'por.id_object = po.id');
        $u->leftJoin('se_permission_role_user pru', 'pru.id_role = por.id_role');
        $u->where('pru.id_user = ?', $idUser);
        $u->groupBy('po.id');
        $this->masks = $u->getList();
	}

	public function check($code, $method = 'read')
	{
		$mask = '00000';
		switch ($method) {
			case 'all':
				$mask = 15;
				break;
			case 'read': 
				$mask = 8;
				break;
			case 'edit':
				$mask = 14;
				break;	
		
		}
		
		foreach($this->masks as $obj) {
			if ($code == $obj['code'] && $obj['mask'] == $mask) {
				return true;
			}
		}
	}
}