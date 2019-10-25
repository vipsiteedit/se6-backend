<?php

namespace SE\CMS;

class SocialUsers extends Base
{
    private $root = DOCUMENT_ROOT;
	private function request($data)
	{
		$json = json_encode($data);
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
				'content' => $json,
			),
		));
		$res = file_get_contents('https://api.siteedit.ru/6.0/SocialUsers', $use_include_path = false, $context);
		//echo $res;
		return json_decode($res, true);
	}
	
	public function fetch()
	{
		$data = array('token'=>$this->input['token'], 'method'=>'fetch');
		$this->result = $this->request($data);
	}
	
	public function delete()
	{
		$data = array('token'=>$this->input['token'], 'method'=>'delete', 'ids'=>$this->input['ids'], 'input'=>$this->input);
		$this->result = $this->request($data);
	}
}