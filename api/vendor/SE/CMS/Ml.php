<?php

namespace SE\CMS;

class Ml extends Base
{
    private $root = DOCUMENT_ROOT;

    private $pathTranslate = DOCUMENT_ROOT . '/apps/translate/';

    /**
     * Скпировать шаблоны страниц из основной папки в песочницы
     *
     * Осн. папка $this->projectFolder
     * Песочница $this->pathEdit
     */
    public function fetch($isId = false)
    {
        $this->result['items'] = $this->getFiles($this->pathTranslate);
    }

    public function save()
    {
        $filename = $this->input['name'] . '.lng';
        $words = json_encode($this->input['words']);
        $fileFullPath = $this->pathTranslate . $filename;

        $time = filemtime($fileFullPath);
        file_put_contents($fileFullPath, $words);
        if (filemtime($fileFullPath) >= $time) {
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
            if (file_exists($this->pathTranslate . $File. '.lng'))
                rename($this->pathTranslate . $File. '.html', $this->pathTranslate . $File . '.del');
            $this->result['status'] = 'ok';
            return true;
        }
        $this->error = 'Не удается удалить файл';
    }

    public function info($id = null)
    {
        $filename = $this->input['name'];
        $this->result['words'] = array();
        if (file_exists($this->pathTranslate . $filename . '.lng')) {
            $this->result['words'] = json_decode(file_get_contents($this->pathTranslate . $filename . '.lng'), true);
        }
    }

    private function getFiles($dir, $idLang)
    {
        $files = glob($dir . '*.lng');
        $result = array();
        foreach ($files as $file) {
            $result[] = array(
                'id' => basename($file),
                'name' => basename($file, '.lng'),
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
        $originalFiles = $this->getFiles($this->pathTranslate);
        foreach ($originalFiles as $originalFile) {
            if (
                !file_exists($this->pathTranslate . $originalFile['fullname'])
                or
                ($originalFile['timestamp'] >= filemtime($this->pathTranslate . $originalFile['fullname']))
            ) {
                if (!file_exists($this->pathTranslate . $originalFile['fullname'] . '.del')) {
                    !copy($originalFile['path'], $this->pathTranslate . $originalFile['fullname']);
                }
            }
        }
    }
}