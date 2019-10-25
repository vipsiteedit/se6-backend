<?php

namespace SE;

define('URL_API', 'https://api.siteedit.ru/6.0/');

class Update {
    
    private $fileUpdate = "update_api.zip";

    public function __construct()
    {
        $this->urlUpdate = URL_API . "update/";
    }
    
    public function checkBuild()
    {
        return $this->getOriginalBuild() > API_BUILD;
    }
    
    public function getOriginalBuild()
    {
        $versionBuild = (int)file_get_contents($this->urlUpdate . "?method=getVersion");
        
        return $versionBuild;
    }
    
    public function getUpdateArchive()
    {
        if (empty($this->urlUpdate))
            return;

        file_put_contents($file = API_ROOT . $this->fileUpdate, file_get_contents($this->urlUpdate . "?method=getArchive"));
        
        return $file;
    }
    
    public function forceUpdate()
    {
        $file = $this->getUpdateArchive();
        
        if (file_exists($file)) {
            $zip = new \ZipArchive;
            if ($zip->open($file) === TRUE) {
                $zip->extractTo(API_ROOT . '../');
                $zip->close();
                unlink($file);
            }
        }
    }
}