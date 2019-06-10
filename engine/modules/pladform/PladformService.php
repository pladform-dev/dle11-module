<?php

class PladformService
{
    const STATUS_DOWNLOAD_START = 1;
    const STATUS_DOWNLOAD_END   = 2;
    const STATUS_DOWNLOAD_ERROR = 3;
    const STATUS_PARSING_START  = 4;
    const STATUS_PARSING_END    = 5;
    const STATUS_PARSING_ERROR  = 6;
    
    public static $statuses = array(
        self::STATUS_DOWNLOAD_START => 'Загрузка',
        self::STATUS_DOWNLOAD_END   => 'Загрузка завершена',
        self::STATUS_DOWNLOAD_ERROR => 'Ошибка загрузки',
        self::STATUS_PARSING_START  => 'Обработка',
        self::STATUS_PARSING_END    => 'Обработка завершена',
        self::STATUS_PARSING_ERROR  => 'Ошибка обработки'
    );

    public static function runProcess()
    {
        exec('php ' . ENGINE_DIR . '/modules/pladform/pladform_process.php >/dev/null 2>/dev/null &');
    }
    
    public static function isRunProcess()
    {
        return strpos(shell_exec("ps aux | grep pladform_process.php"), "pladform/pladform_process.php") !== false;
    }
    
    
}
