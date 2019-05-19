<?php

class PladformApi
{
    const PLADFORM_URL = "https://api.pladform.ru";
    const API_AUTH     = "/auth/login?format=json";
    const API_DOWNLOAD = "/distributor/videos/download";
    
    private $login;
    
    private $password;
    
    private $storedir;
    
    private $downloaded_filename;
    
    private $error = array();
    
    private $report_service;
    
    private $logger;
    
    public function setLogin($login) {
        $this->login = $login;
    }
    
    public function setPassword($password) {
        $this->password = $password;
    }
    
    public function setStoredir($storedir) {
        $this->storedir = $storedir;
    }
    
    public function setReportService($report_service) {
        $this->report_service = $report_service;
    }
    
    public function setLogger($logger) {
        $this->logger = $logger;
    }
    
    public function getError() {
        return $this->error;
    }
    
    public function getDownloadedFilename() {
        return $this->downloaded_filename;
    }
    

    /**
     * Method returns API token
     * @return String 
     */
    public function getToken()
    {
        $body = $this->api_request(self::PLADFORM_URL . self::API_AUTH, array(
            'login'    => $this->login,
            'password' => $this->password
        ), array());

        $return = null;
        
        if (empty($this->error)) // проверка на ошибки, которые могли возникнуть при обращении к API
        {
            $json = json_decode($body, true);
            if ($json['result'] == true)
            {
                $return = $json['session'];
            }
            else
            {
                $this->error[] = "Не правильный логин или пароль в Pladform. <br>Ответ API Pladform: " . $body;
            }
        }
        return $return;
    }
    
    /**
     * 
     */
    public function download($token)
    {
        while(true) // повторяем цикл пока не скачаем файл
        {
            $body = $this->api_request(self::PLADFORM_URL . self::API_DOWNLOAD, array(), array(
                'accept: application/hal+json',
                'Authorization: Bearer ' . $token
            ));
            
            $this->report_service->update(array('last_update_date' => date("Y-m-d H:i:s")));
            
            if (empty($this->error)) // в случае отсутствия ошибок продвигаемся дальше
            {         
                $json = json_decode($body, true);
                if (empty($json['status']))
                {
                    $this->error[] = "Ошибка ответа: " + $body;
                    return;
                }
                else if (!empty($json['link']))
                {
                    //echo $json['link'] . $token;
                    
                    $this->downloaded_filename = date("Ymd") . ".gz";
                    $body = $this->api_download($json['link'] . $token, $this->storedir . "/" . $this->downloaded_filename);
                    return;
                }
                else
                {
                    //echo date("Y-m-d H:i:s") . " - Ожидаю готовности файла выгрузки. Ответ API Pladform: " . $body;
                    sleep(60);
                }
            }
            else
            {
                return;
            }
        }
    }
    
    private function api_download($url, $tofile)
    {
        $fp = fopen ($tofile, 'w');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_FILE, $fp); 
        curl_exec($ch);
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($http_code < 200 || $http_code >= 300) 
        {
            $this->error[] = "API Pladform вернул на запрос <b>'" . $url . "'</b> http-code=" . $http_code . '. Ожидалось 20X.';
        }
        curl_close($ch);
        fclose($fp);
        
        $message = "Request: " . $url . "\n"
                 . "to file: " . $tofile . "\n"
                 . "response: http code=" . $http_code ;    
        $this->logger->log($message);
        
    }
    
    private function api_request($url, $post, $headers)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if (!empty($post))
        {
            curl_setopt($ch,CURLOPT_POST, true);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $post);
        }
        
        if (!empty($headers))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $body = curl_exec($ch);
        echo $url;
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($http_code < 200 || $http_code >= 300) 
        {
            $this->error[] = "API Pladform вернул на запрос <b>'" . $url . "'</b> http-code=" . $http_code . '. Ожидалось 20X.';
        }
        else
        {
            if (empty($body))
            {
                $this->error[] = "Пустой ответ от API Pladform на запрос <b>'" . $url . "'</b>.";
            }
        }
        
        curl_close($ch);
        
        $message = "Request: " . $url . "\n"
                 . (!empty($post)    ? "post: " . json_encode($post) . "\n"       : "")
                 . (!empty($headers) ? "headers: " . json_encode($headers) . "\n" : "")
                 . "response: http code=" . $http_code . "\n"
                 . $body;        
        $this->logger->log($message);

        return $body;
    }
}
