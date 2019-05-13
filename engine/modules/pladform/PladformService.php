<?php

class PladformService
{
    /**
     * 'files': [
     *      {
     *          'filename':            string          Имя файла
     *          'status':              $this->statuses Статус
     *          'last_update_date':    date
     *          'download_time_start': date
     *          'download_time_end':   date
     *          'parsing_time_start':  date
     *          'parsing_time_end':    date
     *          'cnt_all':             int,            Всего обработано
     *          'cnt_added':           int,            Всего добавлено
     *          'last_error':          String          Последняя ошибка
     *          
     *      }
     *      ...
     * ]
     * 
     */
    private $data = null;
    
    private $datafile = ENGINE_DIR . '/modules/pladform/storage/data.json';
    
    const STATUS_DOWNLOAD_START = 1;
    const STATUS_DOWNLOAD_END   = 2;
    const STATUS_DOWNLOAD_ERROR = 3;
    const STATUS_PARSING_START  = 4;
    const STATUS_PARSING_END    = 5;
    const STATUS_PARSING_ERROR  = 6;
    
    public $statuses = array(
        self::STATUS_DOWNLOAD_START => 'Загрузка',
        self::STATUS_DOWNLOAD_END   => 'Загрузка завершена',
        self::STATUS_DOWNLOAD_ERROR => 'Ошибка загрузки',
        self::STATUS_PARSING_START  => 'Парсинг',
        self::STATUS_PARSING_END    => 'Парсинг завершен',
        self::STATUS_PARSING_ERROR  => 'Ошибка парсинга'
    );

    const PARAM_FILENAME            = 'filename';
    const PARAM_STATUS              = 'status';
    const PARAM_DOWNLOAD_TIME_START = 'download_time_start';
    const PARAM_DOWNLOAD_TIME_END   = 'download_time_end';
    const PARAM_PARSING_TIME_START  = 'parsing_time_start';
    const PARAM_PARSING_TIME_END    = 'parsing_time_end';
    const PARAM_CNT_ALL             = 'cnt_all';
    const PARAM_CNT_ADDED           = 'cnt_added';
    const PARAM_LAST_UPDATE_DATE    = 'last_update_date';   
    const PARAM_LAST_ERROR          = 'last_error';
    
    private $empty_file = array(
        self::PARAM_FILENAME            => '',
        self::PARAM_STATUS              => '',
        self::PARAM_LAST_UPDATE_DATE    => '',
        self::PARAM_DOWNLOAD_TIME_START => '',
        self::PARAM_DOWNLOAD_TIME_END   => '',
        self::PARAM_PARSING_TIME_START  => '',
        self::PARAM_PARSING_TIME_END    => '',
        self::PARAM_CNT_ALL             => '',
        self::PARAM_CNT_ADDED           => '',
        self::PARAM_LAST_ERROR          => '',
    );
    
    private $descr;
    

    public function runProcess()
    {
        exec('php ' . ENGINE_DIR . '/modules/pladform/pladform_process.php >/dev/null 2>/dev/null &');
    }
    
    
    
    public function initDataFileRow()
    {
        $this->getData();
        $this->descr = count($this->data['files']);
        $this->data['files'][$this->descr] = $this->empty_file;
    }
    
    public function setDataFileRowParam($key, $value)
    {
        $this->data['files'][$this->descr][$key] = $value;
    }
    
    /**
     * Метод возвращает данные 
     * @return type
     */
    public function getData()
    {
        if ($this->data === null)
        {
            if (!file_exists($this->datafile))
            {
                $this->data = array('files' => array());
            }
            $this->data = json_decode(file_get_contents($this->datafile), true);
            if (empty($this->data))
            {
                $this->data = array('files' => array());
            }
        }
        return $this->data;
    }
    
    /**
     * Метод сохраняет данные
     */
    public function saveData()
    {
        if (empty($this->data)) {
            $this->data = array('files' => array());
        }
        
        $fp = fopen($this->datafile, 'w');
        fwrite($fp, json_encode($this->data));
        fclose($fp);
    }
}
