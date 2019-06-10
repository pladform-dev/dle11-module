<?php

class ReportService
{
    private $db;
    
    private $report_id;
    
    public function ReportService($db) {
        $this->db = $db;
    }
    
    public function getAll()
    {
        $reports = array();
        $this->db->query( "SELECT * FROM " . PREFIX . "_pladform_report ORDER BY id DESC" );
        while($row = $this->db->get_row()) {
            $reports[] = $row;
        }
                        
        return $reports;
    }
    
    public function init($params)
    {
        $sql = "INSERT INTO " . PREFIX . "_pladform_report ({{keys}}) VALUES ({{values}})";
        $prepared_sql = str_replace("{{keys}}", implode(",", array_keys($params)), 
            str_replace("{{values}}", "'". implode("','", array_values($params)) . "'", $sql)
        );
        $this->db->query($prepared_sql);
        $this->report_id = $this->db->insert_id();
    }
    
    public function update($params)
    {
        $values = "";
        $sql = "UPDATE " . PREFIX . "_pladform_report SET {{values}} WHERE id=" . $this->report_id;
        foreach ($params as $key => $value)
        {
            $values .= !empty($values) ? "," : "";
            $values .= $key . "='" . $value . "'";
        }
        $prepared_sql = str_replace("{{values}}", $values, $sql);
        $this->db->query($prepared_sql);
    }
    
    public function validateReports()
    {
        $rows = array();
        $this->db->query( "SELECT * FROM " . PREFIX . "_pladform_report WHERE last_update_date<'".date("Y-m-d H:i:s", strtotime("-1 day"))."' AND status IN (".PladformService::STATUS_DOWNLOAD_START.",".PladformService::STATUS_PARSING_START.")" );
        while($row = $this->db->get_row()) {
            $rows[] = $row;
        }
        
        foreach ($rows as $row)
        {
            $values = "last_update_date='". date("Y-m-d H:i:s")."', "
                     . "last_error='Вышло время ожидания', "
                     . "status=" . ($row['status'] == PladformService::STATUS_DOWNLOAD_START ? PladformService::STATUS_DOWNLOAD_ERROR : PladformService::STATUS_PARSING_ERROR);
            $this->db->query("UPDATE " . PREFIX . "_pladform_report SET ".$values." WHERE id=" . $row['id']);
        }
    }
    
    public function clear()
    {
        $this->db->query("DELETE FROM " . PREFIX . "_pladform_report");
    }

}

