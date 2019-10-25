<?php

namespace SE\CMS;

use SE\DB;

class Image extends Base
{
    private $dir;
    private $section;
    private $lang;
    protected $tableName = 'app_image';
    protected $tableAlias = 'img';

    private $imagesFolder = "apps/images";

    function __construct($input = null)
    {
        parent::__construct($input);

        $this->section = !empty($this->input["section"]) ? $this->input["section"] : $_GET['section'];
        $this->lang = $_SESSION['language'] ? $_SESSION['language'] : 'rus';
    }

    public function fetch($isId = false)
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
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if (!in_array($ext, array('jpeg', 'jpg', 'png', 'gif')))
                    continue;

                if ($searchStr && (strpos(mb_strtolower($file), $searchStr) === false))
                    continue;
                $count++;
                if ($i++ < $this->offset)
                    continue;

                if ($count <= $this->limit + $this->offset) {
                    $item = array();
                    $item["name"] = $file;
                    $item["title"] = $file;
                    $item["weight"] = number_format(filesize($this->dir . "/" . $file), 0, '', ' ');
                    list($width, $height, $type, $attr) = getimagesize($this->dir . "/" . $file);
                    $item["sizeDisplay"] = $width . " x " . $height;
                    $item["imageUrl"] = !empty($this->section) ?
                        'http://' . HOSTNAME . "/images/rus/{$this->section}/" . $file :
                        'http://' . HOSTNAME . "/images/" . $file;
                    $item["imageUrlPreview"] = !empty($this->section) ?
                        "http://" . HOSTNAME . "/lib/image.php?size=64&img=images/rus/{$this->section}/" . $file :
                        "http://" . HOSTNAME . "/lib/image.php?size=64&img=images/" . $file;
                    $listFiles[] = $item;
                }
            }
            closedir($handleDir);
        }
        $this->result['count'] = $count;
        $this->result['items'] = $listFiles;
        return $listFiles;
    }

    public function get()
    {
        $image = urldecode($_GET['img']);
        $size = intval($_GET['size']);

        $path = '/apps/cache/';

        if ($size < 50 || $size > 800) return;

        if (empty($size)) $size = 500;
        $ext = explode('.', $image);
        $dest = md5($image) . '_' . $size . '.' . end($ext);

        if (substr($image, 0, 1) == '/') $image = substr($image, 1);

        if (!is_dir(getcwd() . $path)) {
            mkdir(getcwd() . $path);
        } else {
            $flist = glob($path . md5($image) . '*');
            if (!empty($flist) && count($flist) > 3) {
                foreach ($flist as $f) {
                    if ($f != $path . $dest) unlink($f);
                }
            }
        }

        if (strpos($image, '://') === false) {
            $image = '/apps/images/' . str_replace('//', '/', $image);
        }

        if (!file_exists($path . $dest)) {
            thumbCreate(getcwd() . $path . $dest, getcwd() . $image, '', $size);
        }

        if (file_exists(getcwd() . $path . $dest) && file_exists(getcwd() . $image)) {
            //echo '+++';
            $s = getimagesize($path . $dest);
            header('Content-type: ' . $s['mime']);
            //echo join('', file($path . $dest)) . chr(254) . (255);
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $path . $dest);
        }
    }

    public function delete()
    {
        $files = $this->input["files"];

        $isUnused = (bool) $this->input["isUnused"];
        $usedImages = array();

        if ($this->section == 'shopprice' && $isUnused) {
            $u = new DB('shop_price', 'sp');
            $u->select('img name');
            $images = $u->getList();
            foreach ($images as $image)
                if ($image['name'])
                    $usedImages[] = $image['name'];
            $u = new DB('shop_img', 'si');
            $u->select('picture name');
            $images = $u->getList();
            foreach ($images as $image)
                if ($image['name'])
                    $usedImages[] = $image['name'];
        }
        if ($this->section == 'shopgroup' && $isUnused) {

            $u = new DB('shop_group', 'sg');
            $u->select('picture name');
            $images = $u->getList();
            foreach ($images as $image)
                if ($image['name'])
                    $usedImages[] = $image['name'];
            $u = new DB('shop_img', 'si');
            $u->select('picture name');
            $images = $u->getList();
            foreach ($images as $image)
                if ($image['name'])
                    $usedImages[] = $image['name'];
        }
        if ($this->section == 'newsimg' && $isUnused) {
            $u = new DB('news', 'n');
            $u->select('img name');
            $images = $u->getList();
            foreach ($images as $image)
                if ($image['name'])
                    $usedImages[] = $image['name'];
            $u = new DB('news_img', 'ni');
            $u->select('picture name');
            $images = $u->getList();
            foreach ($images as $image)
                if ($image['name'])
                    $usedImages[] = $image['name'];
        }
        if ($this->section== 'shopbrand' && $isUnused) {
            $u = new DB('shop_brand', 'sb');
            $u->select('image name');
            $images = $u->getList();
            foreach ($images as $image)
                if ($image['name'])
                    $usedImages[] = $image['name'];
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

    public function post()
    {
        $this->input = $_POST;
        $countFiles = count($_FILES);
        $ups = 0;
        $files = array();
        $items = array();
        $protocol = $this->protocol;
        $this->input["path"] = empty($this->input["path"]) ? "/" : $this->input["path"];
        $this->input["path"] = substr($this->input["path"], -1) == "/" ? $this->input["path"] : $this->input["path"] . "/";
        $path = DOCUMENT_ROOT . "/" . $this->imagesFolder . $this->input["path"];

        for ($i = 0; $i < $countFiles; $i++) {
            $file = $_FILES["file$i"]['name'];
            $uploadFile = $path . $file;
            $fileTemp = $_FILES["file$i"]['tmp_name'];
            if (!getimagesize($fileTemp)) {
                $this->error = "Ошибка! Найден файл не являющийся изображением!";
                return;
            }
            if (!filesize($fileTemp) || move_uploaded_file($fileTemp, $uploadFile)) {
                if (file_exists($uploadFile)) {
                    $files[] = $uploadFile;
                    $item = array();
                    $item["name"] = $file;
                    $item["title"] = $file;
                    $item["weight"] = number_format(filesize($uploadFile), 0, '', ' ');
                    list($width, $height, $type, $attr) = getimagesize($uploadFile);
                    $item["sizeDisplay"] = $width . " x " . $height;
                    $item["imagePath"] = $this->input["path"] . urlencode($file);
                    $item["imageUrl"] = $protocol . "://" . HOSTNAME . "/" . $this->imagesFolder .
                        $item["imagePath"];
                    $item["imageUrlPreview"] = $protocol . "://" . HOSTNAME . "/" . $this->imagesFolder .
                        $item["imagePath"];
                    $items[] = $item;
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
            $item = [];
            $item['oldName'] = $name;
            $item['newName'] = $this->getNewName($this->dir, $name);
            $items[] = $item;
        }
        $this->result['items'] = $items;
    }


}
