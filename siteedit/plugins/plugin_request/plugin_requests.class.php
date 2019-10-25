<?php

class plugin_requests {
	public function send($theme, $data = array(), $objectId = false)
	{
		
		if (!trim($this->filter($data['name']))) {
			return false;
		}
        $utm = '';
		// Сохранение UTM метки
		if (!empty($_SESSION['_UTM_'])) {
            $utm = json_encode($_SESSION['_UTM_']);
        }

		$themaId = $this->getThemaId($theme);
		$db = new DB('app_request_order');
		$db->setValuesFields(array('idRequest'=>$themaId, 'name'=>$this->filter($data['name']), 'email'=>$this->filter($data['email']), 'phone'=>$this->filter($data['phone']), 'idObject'=>$objectId, 'utm'=>$utm));
		return $db->save();
	}
	
	
	private function filter($text)
	{
		return htmlspecialchars(str_replace(array('<', '>', 'script'), '', $text));
	}
	
	private function getThemaId($theme)
	{
		$db = new DB('app_requests');
		$db->select('id');
		$db->where("name='?'", $theme);
		$result = $db->fetchOne();
		if (empty($result['id'])) {
			$db->setValuesFields(array('name'=>$theme));
			return $db->save();
		} else {
			return $result['id'];
		}		
	}
	
}