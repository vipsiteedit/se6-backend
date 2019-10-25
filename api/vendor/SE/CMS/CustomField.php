<?php

namespace SE\CMS;

use SE\DB as DB;
use SE\Exception;

class CustomField extends Base
{
    protected $tableName = "app_fields";
    protected $sortBy = "sort";
    protected $sortOrder = "asc";

	private function convertFields($str)
    {
        $str = str_replace('idSection', '`af`.id_section', $str);
        $str = str_replace('idGroup', '`aff`.id_group', $str);
        //$str = str_replace('sect', '`af`.sect', $str);
        return $str;
    }
    /*protected function getSettingsFetch()
    {

        $result["select"] = '`af`.*, afs.id_section, `afg`.`name` AS `group_name`';
        $result["joins"][] = array(
            "type" => "inner",
            "table" => 'app_fields `af`',
            "condition" => '`af`.id_service = `afs`.id AND afs.sect="section"'
        );
        $result["joins"][] = array(
            "type" => "left",
            "table" => 'app_fieldsgroup `afg`',
            "condition" => '`afg`.id = `af`.id_group'
        );

        return $result;
    }*/
	
	public function fetch()
	{
		$sectionName = '';
		foreach($this->input['filters'] as $filter) {
			if ($filter['field'] == 'sect')	{
				$sectionName = $filter['value'];
				break;
			}
		}
		foreach($this->input['filters'] as $id => &$filt) {
			if ($filter['field'] == 'idSection')	{
				if (in_array($sectionName, array('section','request'))) {
					$filt['field'] = 'id'.ucfirst($sectionName);
					break;
				} else {
					unset($this->input['filters'][$id]);
				}	
			}
			
		}

		$filter = array();
		if (!empty($this->input["filters"])) {
			foreach ($this->input["filters"] as $f) {
				if (empty($f['value'])) continue;
				$f['sign'] = ($f['sign']) ? $f['sign'] : 'IN';
				$filter[] = '(' . $this->convertFields($f['field']) . ' ' . $f['sign'] . ' (\'' . $f['value'] . '\'))';
			}
		}
		//print_r($filter);
		$u = new DB("app_fields_service", "af");
		$u->select("`aff`.*, `afg`.`name` AS `group_name`");
		$u->innerJoin('app_fields `aff`', '`aff`.id_service = `af`.id AND af.sect="'.$sectionName.'"');
		$u->leftJoin('app_fieldsgroup `afg`', '`aff`.id_group = `afg`.id');
		
		
		$u->where(join(' AND ', $filter));
		//$this->getWhereQuery($u->getFields()));
		$u->groupBy('aff.id');
		//echo $u->getSql();
		$this->result['items'] = $u->getList();
		
	}


    public function correctValuesBeforeSave()
    {
        /*
        $u = new DB($this->tableName);
        $field = $u->getField('data');

        if ($field['Type'] !== "enum('contact','order','company','productgroup','product','public')") {
            DB::query("ALTER TABLE `{$this->tableName}` CHANGE `data` `data` ENUM('contact','order','company','productgroup','product','public') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        }
        */
        $this->input["idGroup"] = empty($this->input["idGroup"]) ? null : $this->input["idGroup"];
    }
	
	public function save()
    {
		$this->input["idGroup"] = empty($this->input["idGroup"]) ? null : $this->input["idGroup"];
        try {
           // DB::beginTransaction();
            // Сохраняем
			if (in_array($this->input['sect'], array('section','request'))) {
					$filter['field'] = 'id'.ucfirst($this->input['sect']);
					$tmlSect = $this->input['idSection'];
					unset($this->input['idSection']);
					$this->input['id'.ucfirst($this->input['sect'])] = $tmlSect;
			}

            if (!$this->input["idService"]) {
				$u = new DB("app_fields_service");
				$u->select('id');
				$u->where("sect='?'", $this->input['sect']);
				if ($this->input['idSection'])
					$u->andWhere("id_section='?'", $this->input['idSection']);
				else if ($this->input['idRequest'])
					$u->andWhere("id_request='?'", $this->input['idRequest']);
				$result = $u->fetchOne();
				
				if (!$result['id']) {
					$data = array(
						'idApp'=>$this->input['seIdApp'], 
						'sect'=>$this->input['sect'], 
						'idSection'=>$this->input['idSection'],
						'idRequest'=>$this->input['idRequest']
					);
					$u->setValuesFields($data);
					$this->input["idService"] = $u->save();
				} else {
					$this->input["idService"] = $result['id'];
				}	
			}
			//print_r($this->input);
			$u = new DB('app_fields');
            $u->setValuesFields($this->input);
            if ($this->input["id"] = $u->save()) {
				if (empty($this->input["ids"]) && $this->input["id"])
                $this->input["ids"] = array($this->input["id"]);
				$this->info();
                //DB::commit();
                return $this;
            } else throw new Exception();
			
			
   

        } catch (Exception $e) {
            //DB::rollBack();
            $this->error = empty($this->error) ? "Не удаётся сохранить информацию об объекте!" : $this->error;
        }
    }
}
