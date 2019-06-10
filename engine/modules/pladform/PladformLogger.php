<?php

class PladformLogger
{
    private $filename;
    
    private $unit = array('b','kb','mb','gb','tb','pb');
    
    public function setFilename($filename) {
        $this->filename = $filename;
    }
    
    public function log($message)
    {
        $size = memory_get_usage(true);
        $line = date("Y-m-d H:i:s") . " " . $message . "\n" . "Memory: " . (@round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$this->unit[$i]) . "\n";
        print_r($line);
        if (!empty($this->filename)) 
        {
            @file_put_contents($this->filename, $line . "\n", FILE_APPEND);
        }
    }
}