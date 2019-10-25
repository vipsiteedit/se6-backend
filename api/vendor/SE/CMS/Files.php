<?php

namespace SE\CMS;

use SE\DB;

class Files extends Base
{
    private $dir;
    private $section;
    private $lang;

    function __construct($input = null)
    {
        parent::__construct($input);

        $this->section = !empty($this->input["section"]) ? $this->input["section"] : $_GET['section'];
        $this->dir = $this->pathContent . '/files';
        if ($this->section) {
            $this->dir .= "/{$this->section}";
        }
        if (!file_exists($this->dir))
            mkdir($this->dir, 0700, true);
    }

    public function fetch()
    {
        if (function_exists("mb_strtolower"))
            $searchStr = mb_strtolower(trim($this->search));
        else $searchStr = strtolower(trim($this->search));
        if ($searchStr)
            $this->offset = 0;
        $listFiles = array();
        $count = 0;
        if (file_exists($this->dir) && is_dir($this->dir)) {
            $handleDir = opendir($this->dir);
            $i = 0;
            while (($file = readdir($handleDir)) !== false) {
                if ($file == '.' || $file == '..')
                    continue;
                if ($searchStr && (strpos(mb_strtolower($file), $searchStr) === false))
                    continue;
                $count++;
                if ($i++ < $this->offset)
                    continue;

                if ($count <= $this->limit + $this->offset) {
                    $item = array();
                    $item["name"] = 'Скачать '.$file;
                    $item["file"] = $file;
                    $listFiles[] = $item;
                }
            }
            closedir($handleDir);
        }
        $this->result['count'] = $count;
        $this->result['items'] = $listFiles;
        return $listFiles;
    }

    public function delete()
    {
        return FALSE;
        $files = $this->input["files"];

        $isUnused = (bool) $this->input["isUnused"];
        $usedFiles = array();

        if ($this->section == 'shopprice' && $isUnused) {
            $u = new DB('app_section_collection_files', 'ascf');
            $u->select('file name');
            $u->innerJoin('app_section_collection `asc`', 'asc.id=ascf.id_collection');
            $u->innerJoin('app_section `as`', 'as.id=asc.id_section');
            $u->where("as.alias=?", $this->section);
            $files = $u->getList();
            foreach ($files as $file)
                if ($file['name'])
                    $usedFiles[] = $file['name'];
        }

        if (!empty($this->section)) {
            if ($isUnused) {
                $handleDir = opendir($this->dir);
                while (($file = readdir($handleDir)) !== false) {
                    if ($file == '.' || $file == '..')
                        continue;
                    if (!in_array($file, $usedImages))
                        unlink($this->dir . "/" . $file);
                }
            } else
                foreach ($files as $file)
                    if (!empty($file))
                        unlink($this->dir . "/" . $file);
        } else $this->error = "Не удаётся удалить файлы изображений!";
    }

    private function getUrlFile($file, $uploadFile)
    {
        $imgPath = 'www/'. $this->apps;
        $item = array();
        $item["name"] = $file;
        $item["file"] = $file;
        $item["ext"] = strtoupper(substr(strrchr($file,'.'), 1));
        $item['url'] = 'http://' . HOSTNAME . "/{$imgPath}/" . $file;
        return $item;
    }

    public function post()
    {
        $countFiles = count($_FILES);
        $ups = 0;
        $files = array();
        $items = array();

        for ($i = 0; $i < $countFiles; $i++) {
            $file = $this->convertName($_FILES["file$i"]['name']);
            $uploadFile = $this->dir . '/' . $file;
            $fileTemp = $_FILES["file$i"]['tmp_name'];

            if (!filesize($fileTemp) || move_uploaded_file($fileTemp, $uploadFile)) {
                if (file_exists($uploadFile)) {
                    $files[] = $uploadFile;
                    $items[] = $this->getUrlFile('files/' . $this->section . '/' .$file, $uploadFile);
                    //writeLog($item);
                }
                $ups++;
            }

        }
        if ($ups == $countFiles)
            $this->result['items'] = $items;
        else $this->error = "Не удается загрузить файлы!";

        return $items;
    }

    private function convertName($name) {
        $chars = array(" ", "#", ":", "!", "+", "?", "&", "@", "~", "%");
        return str_replace($chars, "_", $name);
    }

    private function getNewName($dir, $name) {
        $i = 0;
        $newName = $name = $this->convertName(trim($name));
        while (true) {
            if (!file_exists($dir . "/" . $newName))
                return $newName;
            $newName = substr($name, 0, strrpos($name, ".")) . "_" . ++$i . "." . end(explode(".", $name));
        }
    }

    public function info($id = null)
    {
        $names = $this->input["listValues"];
        $newNames = array();
        foreach ($names as $name)
            $newNames[] = $this->getNewName($this->dir, $name);
        $item['newNames'] = $newNames;
        $this->result['count'] = 1;
        $this->result['items'][0] = $item;
        return $item;
    }

    public function checkNames()
    {
        $items = array();
        $names = $this->input["names"];
        foreach ($names as $name) {
            $item = array();
            $item['oldName'] = $name;
            $item['newName'] = $this->getNewName($this->dir, $name);
            $items[] = $item;
        }
        $this->result['items'] = $items;
    }
}
