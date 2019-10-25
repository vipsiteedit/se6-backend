<?php

namespace SE\CMS;

use SE\DB as DB;

class ImageFolder extends Base
{
    protected $tableName = 'app_image_folder';
    protected $tableAlias = 'f';

    private $imagesFolder = "apps/images";

    private $deleteImage = 0;
    private $removedImage = 0;

    public function fetch($isId = false)
    {
        $protocol = $this->protocol;

        $this->input["path"] = empty($this->input["path"]) ? "/" : $this->input["path"];
        $path = DOCUMENT_ROOT . "/" . $this->imagesFolder . $this->input["path"];
        $iterator = new \RecursiveDirectoryIterator($path);
        $fileList = [];
        $fileListKey = [];
        $imagePath = $this->input["path"] == '/' ? '' : $this->input["path"];
        foreach ($iterator as $entry) {
            if ($entry->getFilename() == '.' || $entry->getFilename() == '..')
                continue;
            $ext = pathinfo($entry->getFilename(), PATHINFO_EXTENSION);
            if (!in_array($ext, array('jpeg', 'jpg', 'png', 'gif')))
                continue;
            $fileInfo["name"] = $entry->getFilename();
            $fileInfo["isDir"] = $entry->isDir();
            if (!$fileInfo["isDir"]) {
                $fileInfo["url"] = $protocol . "://" . HOSTNAME . "/" . $this->imagesFolder . $imagePath . $fileInfo["name"];
                $fileInfo["urlPreview"] = 'http://' . $this->hostname . "/api/CMS/Image/?size=100x100&img=" . $imagePath . $fileInfo["name"];
                $fileInfo["size"] = $this->getFileSize($path . $fileInfo["name"]);
            }
            $fileListKey[$fileInfo["name"]] = $fileInfo;
        }
        ksort($fileListKey);
        foreach ($fileListKey as $file)
            $fileList[] = $file;
        $this->result["items"] = $fileList;
    }

    public function save()
    {
        $this->input["path"] = empty($this->input["path"]) ? "/" : $this->input["path"];
        $path = DOCUMENT_ROOT . "/" . $this->imagesFolder . $this->input["path"];
        $cmd = $this->input["cmd"] ? $this->input["cmd"] : "create";
        $name = $this->input["name"];
        if ($cmd == "create" && !empty($name)) {
            $path .= "/{$name}";
            if (!mkdir($path))
                $this->error = "Не удаётся создать папку с именем: {$name}!";
        }
        if ($cmd == "rename" && !empty($name)) {
            $newName = $path . "/" . $this->input["newName"];
            $path .= "/{$name}";
            if (!rename($path, $newName) || !$this->renameInBase($path, $newName, is_dir($newName)))
                $this->error = "Не удаётся переименовать указанный файл или папку";
        }
    }

    private function renameInBase($name, $newName, $idDir)
    {
        return true;
    }

    private function deleteImage($image)
    {
        $is_delete = true;
        if ($id = $this->getIdImage($image)) {
            try {
                $u = new DB("app_image");
                $u->where("id = '?'", $id);
                $is_delete = $u->deletelist();
            }
            catch (\Exception $e) {
                $is_delete = false;
            }
        }

        return $is_delete;
    }

    private function removeDirectory($dir)
    {
        $path = DOCUMENT_ROOT . "/" . $this->imagesFolder;

        if ($objList = glob($path . $dir . "/*")) {
            foreach ($objList as $obj) {
                $file = str_replace($path, '', $obj);
                if (is_dir($obj)) {
                        $this->removeDirectory($file);
                } else {
                    $this->deleteImage++;
                    if ($this->deleteImage($file)) {
                        unlink($obj);
                        $this->removedImage++;
                    }
                }
            }
        }
        rmdir($path . $dir);
    }

    public function delete()
    {
        $error = null;
        $this->input["path"] = empty($this->input["path"]) ? "/" : $this->input["path"];
        $path = DOCUMENT_ROOT . "/" . $this->imagesFolder;
        $files = $this->input["files"];
        foreach ($files as $file) {
            $file = substr($file, -1) == "/" ? $this->input["path"] . $file : $this->input["path"] . "/" . $file;

            if (!is_dir($path . $file)) {
                $this->deleteImage++;
                if ($this->deleteImage($file)) {
                    unlink($path . $file);
                    $this->removedImage++;
                }
            }
            else {
                $this->removeDirectory($file);
            }
        }
        if ($this->removedImage < $this->deleteImage) {
            $this->error = 'ERROR! Delete files ' . $this->deleteImage . ', removed ' . $this->removedImage;
        }
    }


}