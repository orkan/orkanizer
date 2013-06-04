<?php

class ZipFolder {
    protected $zip;
    protected $file;
    protected $root;
    protected $ignored_names;
   
    public function __construct($file, $folder, $ignored=null) {
        $this->file = $file;
        $this->zip = new ZipArchive();
        $this->ignored_names = is_array($ignored) ? $ignored : $ignored ? array($ignored) : array();
        if ($this->zip->open($file, ZIPARCHIVE::CREATE)!==TRUE) {
            throw new Exception("cannot open <$file>\n");
        }
        $folder = substr($folder, -1) == '/' ? substr($folder, 0, strlen($folder)-1) : $folder;
        if(strstr($folder, '/')) {
            $this->root = substr($folder, 0, strrpos($folder, '/')+1);
            $folder = substr($folder, strrpos($folder, '/')+1);
        }
        $this->zip($folder);
        $this->zip->close();
    }
   
    public function zip($folder, $parent=null) {
        $full_path = $this->root.$parent.$folder;
        $zip_path = $parent.$folder;
        $this->zip->addEmptyDir($zip_path);
        $dir = new DirectoryIterator($full_path);
        foreach($dir as $file) {
            if(!$file->isDot()) {
                $filename = $file->getFilename();
                if(!in_array($filename, $this->ignored_names)) {
                    if($file->isDir()) {
                        $this->zip($filename, $zip_path.'/');
                    }
                    else {
                        $this->zip->addFile($full_path.'/'.$filename, $zip_path.'/'.$filename);
                    }
                }
            }
        }
    }
    
    public function addDir($path) {
        $this->zip->open($this->file);
        $this->addEmptyDir($path);
        $nodes = glob($path . '/*');
        foreach ($nodes as $node) {
            if (is_dir($node)) {
                $this->addDir($node);
            } else if (is_file($node))  {
                $this->addFile($node);
            }
        }
        $this->zip->close();
    } 
    
    public function addFromString($f, $s) {
        $this->zip->open($this->file);
        $this->zip->addFromString($f, $s);
        $this->zip->close();
    }     
    
    
//    public function addFile($path) {
//        $this->addFile('/path/to/index.txt', 'newname.txt');
//    } 
    
}
