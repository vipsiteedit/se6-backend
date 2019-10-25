<?php

namespace SE\CMS;

class Structures extends Base
{
    private $root = DOCUMENT_ROOT;

    private $defaultFileTemplate = DOCUMENT_ROOT . '/apps/templates/default.html';

    /**
     * Скпировать шаблоны страниц из основной папки в песочницы
     *
     * Осн. папка $this->projectFolder
     * Песочница $this->pathEdit
     */
    public function fetch($isId = false)
    {
        $this->result['items'] = $this->getFiles($this->pathTemplates);
    }


    public function save()
    {
        $filename = $this->input['name'] . '.html';
        $filesource = $this->input['source'];

        if (empty($filesource) && file_exists($this->defaultFileTemplate) && empty($this->getFiles($this->pathTemplates))) {
            $filesource = file_get_contents($this->defaultFileTemplate);
        }

        $fileFullPath = $this->pathTemplates . $filename;

        $time = filemtime($fileFullPath);
        $f = fopen($fileFullPath, 'w');
        fwrite($f, $filesource);
        fclose($f);
        if (filemtime($fileFullPath) >= $time) {
            if ($this->input['createPage']) {
                $this->input['title'] = $this->input['name'];

                $input = array(
                    'name' => $this->input['name'],
                    'title' => $this->input['name'],
                    'seIdApp' => $this->input['seIdApp'],
                    'pattern' => array('idApp' => $this->input['seIdApp']),
                    'template' => $this->input['name'],
                );

                $page = new Pages($input);
                $page->save();
            }
            $this->result['status'] = 'OK';
        } else {
            $this->error = 'Ошибка при сохранении файла';
        }
    }

    public function delete()
    {
        //$filename = $this->input['name'];
        if (!empty($this->input['id'])) {
            $File = $this->input['id'];
            if (file_exists($this->pathTemplates . $File. '.html'))
                rename($this->pathTemplates . $File. '.html', $this->pathTemplates . $File . '.del');
            $this->result['status'] = 'ok';
            return true;
        }
        $this->error = 'Не удается удалить файл';
    }

    public function info($id = null)
    {
        $filename = $this->input['name'];
		$this->result['source'] = '';
		if (file_exists($this->pathTemplates . $filename . '.html')) {
			$this->result['source'] = file_get_contents($this->pathTemplates . $filename . '.html');
		}
    }

    private function getFiles($dir)
    {
        $files = glob($dir . '*.html');
        $result = array();
        foreach ($files as $file) {
            $result[] = array(
                'id' => basename($file),
                'name' => basename($file, '.html'),
                'fullname' => basename($file),
                'path' => $file,
                'timestamp' => filemtime($file)
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