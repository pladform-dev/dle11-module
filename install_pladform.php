<?php
session_start();

@error_reporting ( E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE );
@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );

define('DATALIFEENGINE', true);
define('ROOT_DIR', dirname (__FILE__));
define('ENGINE_DIR', ROOT_DIR.'/engine');

define('PLADFORM_PATH', ENGINE_DIR . "/modules/pladform"); 
define('STORE_PATH', PLADFORM_PATH . "/storage"); 

$distr_charset = "utf-8";
$db_charset = "utf8";

@set_time_limit(0);
@ini_set('max_execution_time', 0);

header("Content-type: text/html; charset=".$distr_charset);
require_once (ENGINE_DIR . '/inc/include/init.php');

function msgbox($title, $text, $action, $btn_title)
{
echo '
    <form action="install_pladform.php" method="get">
        <input type="hidden" name="action" value="'.$action.'">
        <div class="box">
            <div class="box-header">
                <div class="title">'.$title.'</div>
            </div>
            <div class="box-content">
                <div class="row box-section">'.$text.'</div>
                ' . (!empty($btn_title) ? '<div class="row box-section"><input class="btn btn-green" type=submit value="'.$btn_title.'"></div>' : '') .'
            </div>
        </div>
    </form>';
}

?>
<!doctype html>
<html>
<head>
  <meta charset="{$distr_charset}">
  <meta name="viewport" content="width=device-width, maximum-scale=1, initial-scale=1, user-scalable=0">
  <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
  <title>DataLife Engine - Установка модуля pladform</title>
  <link href="engine/skins/stylesheets/application.css" media="screen" rel="stylesheet" type="text/css" />
  <script type="text/javascript" src="engine/skins/javascripts/application.js"></script>
<style type="text/css">
body {
	background: url("engine/skins/images/bg.png");
}
</style>
</head>
<body>

<nav class="navbar navbar-default navbar-inverse navbar-static-top" role="navigation">
  <div class="navbar-header">
    <a class="navbar-brand" href="">Мастер установки модуля Pladform в DataLife Engine версии 11</a>
  </div>
</nav>
<div class="container">
  <div class="col-md-8 col-md-offset-2">
    <div class="padded">
	    <div style="margin-top: 10px;">
<?php

if($_REQUEST['action'] == "step1")
{
    // устанавливаем права на запись в папку стора
    @chmod(STORE_PATH, 0777);
    
    $php_version    = version_compare(phpversion(), '5.3.7', '>');
    $curl_exist     = extension_loaded("curl");
    $mbstring_exist = extension_loaded("mbstring");
    $json_exist     = extension_loaded("json");
    $store_write    = is_writable(STORE_PATH);
    $execution_time = ini_get('max_execution_time') <= 0 || ini_get('max_execution_time') >= 3600;
    $total = $php_version && $curl_exist && $mbstring_exist && $json_exist && $store_write && $execution_time;

    $body = "<table class='table table-normal table-bordered'>
        <tr><td>Версия PHP 5.3.7 и выше</td><td>" . ($php_version ? "<font color=green><b>Да</b></font>" : "<font color=red><b>Нет</b></font>" ) . "</td><td>Версия: ".phpversion()."</td></tr>
        <tr><td>Расширение php-curl</td><td>"     . ($curl_exist ? "<font color=green><b>Установлено</b></font>" : "<font color=red><b>Нет</b></font>" ) . "</td><td></td></tr>
        <tr><td>Расширение php-mbstring</td><td>" . ($mbstring_exist ? "<font color=green><b>Установлено</b></font>" : "<font color=red><b>Нет</b></font>" ) . "</td><td></td></tr>
        <tr><td>Расширение php-json</td><td>"     . ($json_exist ? "<font color=green><b>Установлено</b></font>" : "<font color=red><b>Нет</b></font>" ) . "</td><td></td></tr>
        <tr><td>Дирректория '".STORE_PATH."' доступна для записи</td><td>"  . ($store_write ? "<font color=green><b>Да</b></font>" : "<font color=red><b>Нет</b></font>" ) . "</td><td>". ($store_write ? "" : "Скрипт не смог сам установить права на запись. Не хватает прав доступа. Вы можете вручную задать права на запись, выполнив команду: chmod -R 0777 ".STORE_PATH) ."</td></tr>
        <tr><td>Время выполения скрипта PHP (max_execution_time)</td><td>"     . ($execution_time ? "<font color=green><b>Да</b></font>" : "<font color=red><b>Нет</b></font>" ) . "</td><td>Значение: ".ini_get('max_execution_time')."</td></tr>
    </table>";
    
    msgbox("Проверка установленных компонентов PHP и разрешений", $body, $total ? "step2" : "step1", $total ? "Продолжить" : "Обновить");

} 
else if($_REQUEST['action'] == "step2")
{
    $add_report_log = "";
    $add_report = true;
    $sql_report =  "CREATE TABLE " . PREFIX . "_pladform_report (
        `id` int(11) NOT NULL auto_increment,
        `filename` varchar(255) NOT NULL DEFAULT '',
        `status` smallint(6) NOT NULL DEFAULT '0',
        `download_time_start` timestamp NULL DEFAULT NULL,
        `download_time_end` timestamp NULL DEFAULT NULL,
        `parsing_time_start` timestamp NULL DEFAULT NULL,
        `parsing_time_end` timestamp NULL DEFAULT NULL,
        `video_all` int(11) NOT NULL DEFAULT '0',
        `video_added` int(11) NOT NULL DEFAULT '0',
        `last_update_date` timestamp NULL DEFAULT NULL,
        `last_error` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
        `logfile` varchar(255) NOT NULL,
        PRIMARY KEY  (`id`)
    )";
    $query_id = $db->query($sql_report);
    $mysql_error = mysqli_error($query_id);
    if (!empty($mysql_error))
    {
        $add_report = false;
        $add_report_log = $mysql_error;
    }
    
    $add_module_log = "";
    $add_module = true;

    $sql_module_add = "INSERT INTO ".PREFIX."_admin_sections (`name`, `title`, `descr`, `icon`, `allow_groups`) VALUES ('pladform', 'Pladform', 'Pladform модуль', '', '1')";
    $query_id = $db->query($sql_module_add);
    $mysql_error = mysqli_error($query_id);
    if (!empty($mysql_error))
    {
        $add_module = false;
        $add_module_log = $mysql_error;
    }

    $total = $add_report && $add_module;
    
    $body = "<table class='table table-normal table-bordered'>
        <tr><td>Добавление таблицы отчета</td><td>" . ($add_report ? "<font color=green><b>Да</b></font>" : "<font color=red><b>Нет</b></font>" ) . "</td><td>".$add_report_log."</td></tr>
        <tr><td>Добавление модуля в список модулей скрипта</td><td>" . ($add_module ? "<font color=green><b>Да</b></font>" : "<font color=red><b>Нет</b></font>" ) . "</td><td>".$add_module_log."</td></tr>
    </table>";
        
    msgbox("Обновление базы данных", $body, $total ? "finish" : "step2", $total ? "Продолжить" : "Обновить");
}
else if($_REQUEST['action'] == "finish")
{    
    msgbox("Установка модуля окончена", "Поздравляем, установка модуля окончена.<p>Вы сможете найти панель управления модулем в админке DLE в разделе 'Сторонние модули'. <p><p><font color='red'>Не забудьте настроить файл конфигурации по пути: " . PLADFORM_PATH . "/config.php"."<p>Внимание: не забудьте удалить файл установки модуля ./install_pladform.php</font><p><p>Приятной Вам работы,<p>Команда Pladform");
}
else
{
    msgbox("Мастер установки модуля Pladform в DataLife Engine версии 11", "Добро пожаловать в мастер установки DataLife Engine. Данный мастер поможет вам установить скрипт всего за несколько минут. Однако, не смотря на это, мы настоятельно рекомендуем Вам ознакомиться с документацией по работе со скриптом, а также по его установке, которая поставляется вместе со скриптом.<p><p>Прежде чем начать установку убедитесь, что все файлы модуля загружены на сервер, а также выставлены необходимые права доступа для папок и файлов.<p><p><font color='red'>Внимание: при установке скрипта создается структура базы данных, а также прописываются основные настройки модуля, поэтому после успешной установки удалите файл install_pladform.php во избежание повторной установки модуля!</font><p><p>Приятной Вам работы,<p>Команда Pladform", "step1", "Продолжить");
}


?>
	 <!--MAIN area-->
    </div>
  </div>
</div>
</div>

</body>
</html>