<?php

namespace SE\CMS;

class StructuresTree extends Base
{
    private $root = DOCUMENT_ROOT;

    private $defaultFileTemplate = DOCUMENT_ROOT . '/apps/templates/default.html';
    private $fileTemplateTree = DOCUMENT_ROOT . '/apps/templates/tree.json';

    /**
     * Скпировать шаблоны страниц из основной папки в песочницы
     *
     * Осн. папка $this->projectFolder
     * Песочница $this->pathEdit
     */
    public function fetch($isId = false)
    {
        $templates = $this->getFiles($this->pathTemplates);
        if (file_exists($this->pathTemplates . 'tree.json')) {
            $tree = json_decode(file_get_contents($this->pathTemplates . 'tree.json'), true);
            /*$tree = array(
                array('id'=>'top', '_DIR_'=>true, 'name'=>'top', 'childs'=>array(
                        array('id'=>'pages2', '_DIR_'=>true, 'name'=>'pages2', 'childs'=>array()),
                        array('id'=>'header1.html', 'name'=>'header1'),
                    )),
                array('id'=>2, '_DIR_'=>true, 'name'=>'pages', 'childs'=>array(array('id'=>'index.html', 'name'=>'index'))),
            );
            */
            $this->parseTree($tree, $templates);
            $tree = array_merge($tree, $templates);

            file_put_contents($this->pathTemplates . 'tree.json', json_encode($tree));
            $this->result['items'] = $tree;
        } else
            $this->result['items'] = $templates;
    }
    
    private function parseTree(&$tree, &$templates)
    {
        foreach($tree as $dit=>&$item) {
            if (empty($item['_DIR_'])) {
                $is = false;
                foreach($templates as $it=>$file) {
                    if ($item['id'] == $file['id']) {
                        $is = true;
                        //print_r($file);
                        unset($templates[$it]);
                    }
                }
                if (!$is) {
                    unset($tree[$dit]);
                }
            } else {
                if (!empty($item['childs'])) {
                    $this->parseTree($item['childs'], $templates);
                }
            }
        }
    }


    public function save()
    {
        try {
            $tree = is_array($this->input['nodes']) ? $this->input['nodes'] : array();
            $this->checkTree($tree);
            file_put_contents($this->pathTemplates . 'tree.json', json_encode($tree));
            $this->result['status'] = 'OK';
        } catch (Exception $e) {
            $this->error = 'Error save file tree';
        }
    }

    private function checkTree(&$tree)
    {
        $newitem = array();
        foreach($tree as $item) {
            if (empty($item['_DIR_']) || strpos($item['id'], '.html')===true) {
                $newitem['id'] = $item['id'];
                $newitem['name'] = $item['name'];
            } else {
                $newitem['id'] = str_replace('.html', '', $item['id']);
                $newitem['name'] = $item['name'];
                $newitem['_DIR_'] = true;
                $newitem['childs'] = $item['childs'];
                $this->checkTree($newitem['childs']);
            }
            $item = $newitem;
        }
    }

    public function delete()
    {
        //$filename = $this->input['name'];
        if (!empty($this->input['ids'])) {
            foreach($this->input['ids'] as $File) {
                $File = str_replace('.html', '', $File);
                if (file_exists($this->pathTemplates . $File . '.html'))
                    rename($this->pathTemplates . $File . '.html', $this->pathTemplates . $File . '.del');
            }
            $this->result['status'] = 'ok';
            return true;
        }
        $this->error = 'Не удается удалить файл';
    }

    public function info($id = null)
    {

    }

    private function getFiles($dir)
    {
        $files = glob($dir . '*.html');
        $result = array();
        foreach ($files as $file) {
            $result[] = array(
                'id' => basename($file),
                'name' => basename($file, '.html'),
                //'fullname' => basename($file),
                //'path' => $file,
                //'timestamp' => filemtime($file)
            );
        }
        return $result;
    }
	
	public function initPath()
	{
		parent::initPath();
		$this->updateEdit();
	}

    private function updateEdit()
    {
        $originalFiles = $this->getFiles($this->pathTemplates);
        foreach ($originalFiles as $originalFile) {
            if (
                !file_exists($this->pathTemplates . $originalFile['fullname'])
                or
                ($originalFile['timestamp'] >= filemtime($this->pathTemplates . $originalFile['fullname']))
            ) {
                if (!file_exists($this->pathTemplates . $originalFile['fullname'] . '.del')) {
                    !copy($originalFile['path'], $this->pathTemplates . $originalFile['fullname']);
                }
            }
        }
    }
}