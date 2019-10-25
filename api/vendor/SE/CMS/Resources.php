<?php

namespace SE\CMS;

class Resources extends Base
{
    protected $path;

    function __construct($input = null)
    {
        parent::__construct($input);
    }

    public function initPath()
    {
        parent::initPath();

        if (strpos($this->input["path"], '..') !== false) {
            $this->input["path"] = '/';
        }

        $this->input["path"] = trim($this->input["path"], '/');
        $this->path = $this->pathContent . '/' . $this->input["path"] . '/';
    }

    public function fetch($isId = false)
    {
        $iterator = new \RecursiveDirectoryIterator($this->path);

        $fileList = array();
        $fileListKey = array();
        foreach ($iterator as $entry) {
            if ($entry->getFilename() == '.' || $entry->getFilename() == '..')
                continue;

            $fileInfo = array(
                'name' => $entry->getFilename(),
                'isDir' => $entry->isDir(),
            );

            if (!$fileInfo["isDir"]) {
                $path = $this->path . $fileInfo["name"];
                $fileInfo["size"] = $this->getFileSize($path);
                $fileInfo["ext"] = $entry->getExtension();
                $fileInfo["path"] = empty($this->input["path"]) ? $fileInfo["name"] : $this->input["path"] . '/' . $fileInfo["name"];
                $fileInfo["mime"] = mime_content_type($path);
                
                if ($fileInfo["mime"] == 'inode/x-empty') {
                    $fileInfo["mime"] = 'text/x-empty';
                }
                elseif (strpos($fileInfo["mime"], 'image/') === 0 && $imageSize = getimagesize($path)) {

                    $fileInfo["image"] = array(
                        'path' => _HOST_ . '/' . $fileInfo["path"],
                        'width' => $imageSize[0],
                        'heigh' => $imageSize[1],
                    );
                }
            }
            $fileListKey[$fileInfo["name"]] = $fileInfo;
        }
        ksort($fileListKey);
        foreach ($fileListKey as $file)
            $fileList[] = $file;
        $this->result["items"] = $fileList;
    }

    public function info($id = null)
    {
        $name = $this->input["name"];

        ;

        if (!empty($name)) {

            if (is_file($this->path . $name)) {
                $content = file_get_contents($this->path . $name);

                if (($encoding = $this->getEncodingContent($content)) && ($encoding != 'utf-8')) {
                    $content = iconv($encoding, 'utf-8', $content);
                }

                $this->result["item"] = array(
                    'source' => $content,
                    'size' => $this->getFileSize($this->path . $name),
                    'ext' => pathinfo($this->path . $name, PATHINFO_EXTENSION),
                    'mime' => mime_content_type($this->path . $name),
                );

            } else {
                $this->error = "Не удаётся найти или получить данные файла с именем: {$name}!";
            }
        }
    }

    protected function getEncodingContent($string)
    {
        $encoding_list = array(
            'utf-8', 'windows-1251', 'ASCII', 'windows-1252', 'windows-1254',
            'iso-8859-1', 'iso-8859-2', 'iso-8859-3', 'iso-8859-4', 'iso-8859-5',
            'iso-8859-6', 'iso-8859-7', 'iso-8859-8', 'iso-8859-9', 'iso-8859-10',
            'iso-8859-13', 'iso-8859-14', 'iso-8859-15', 'iso-8859-16'
        );

        foreach ($encoding_list as $val) {
            $sample = iconv($val, $val, $string);
            if ($sample == $string)
                return $val;
        }
        return null;
    }

    public function save()
    {
        $cmd = $this->input["cmd"] ? $this->input["cmd"] : "create";
        $name = $this->input["name"];
        if ($cmd == "create" && !empty($name)) {
            $path = $this->path . $name;
            if ($this->input["isDir"]) {
                if (!mkdir($path))
                    $this->error = "Не удаётся создать папку с именем: {$name}!";
            } else {
                $content = $this->input['source'];
                if (file_put_contents($path, $content) === false)
                    $this->error = "Не удаётся создать файл с именем: {$name}!";
            }
        }
        if ($cmd == "rename" && !empty($name)) {
            $newName = $this->path . $this->input["newName"];
            $path = $this->path . $name;
            if (!rename($path, $newName))
                $this->error = "Не удаётся переименовать указанный файл или папку";
        }
    }

    protected function removeDirectory($dir)
    {
        if ($objList = glob($this->path . $dir . "/*")) {
            foreach ($objList as $obj) {
                if (is_dir($obj)) {
                    $path = str_replace($this->path, '', $obj);
                    $this->removeDirectory($path);
                } else {
                    unlink($obj);
                }
            }
        }
        rmdir($this->path . $dir);
    }

    public function delete()
    {
        $error = null;
        $files = $this->input["files"];
        foreach ($files as $file) {
            if (!is_dir($this->path . $file)) {
                unlink($this->path . $file);
            } else {
                $this->removeDirectory($file);
            }
        }
    }

    public function post()
    {
        $this->input = $_POST;
        $this->initPath();
        $countFiles = count($_FILES);
        $countUpload = 0;
        $files = array();
        $items = array();

        $this->result['count'] = $countFiles;
        $this->result['input'] = $this->input;

        for ($i = 0; $i < $countFiles; $i++) {
            $file = $_FILES["file$i"]['name'];
            $uploadFile = $this->path . $file;
            $fileTemp = $_FILES["file$i"]['tmp_name'];

            if (!filesize($fileTemp) || move_uploaded_file($fileTemp, $uploadFile)) {
                if (file_exists($uploadFile)) {
                    $files[] = $uploadFile;
                    $item = array();
                    $item["name"] = $file;
                    $item["title"] = $file;
                    $items[] = $item;
                    $countUpload++;
                }
            }
        }
        if ($countUpload == $countFiles)
            $this->result['items'] = $items;
        else $this->error = "Не удается загрузить файлы!";

        return $items;
    }
}