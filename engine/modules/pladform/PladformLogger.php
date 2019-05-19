<?php

class PladformLogger
{
    private $filename;
    
    public function setFilename($filename) {
        $this->filename = $filename;
    }
    
    public function log($message)
    {
        if (!empty($this->filename)) 
        {
            print_r(date("Y-m-d H:i:s") . " " . $message . "\n");
            file_put_contents($this->filename, date("Y-m-d H:i:s") . " " . $message . "\n", FILE_APPEND);
        }
        else
        {
            print_r(date("Y-m-d H:i:s") . " " . $message );
        }
    }
}