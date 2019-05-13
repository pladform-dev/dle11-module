<?php

@error_reporting ( E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE );
@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );

define ( 'DATALIFEENGINE', true );
define ( 'ROOT_DIR', realpath(dirname ( __FILE__ ) . "/../../..") );
define ( 'ENGINE_DIR', ROOT_DIR . '/engine' );

@set_time_limit(0);
@ini_set('max_execution_time', 0);

require_once (ENGINE_DIR . '/inc/include/init.php');
require_once (ENGINE_DIR . '/modules/pladform/autoload.php');


$service = new PladformService();
$service->initDataFileRow();
$service->setDataFileRowParam(PladformService::PARAM_DOWNLOAD_TIME_START, time());
$service->setDataFileRowParam(PladformService::PARAM_LAST_UPDATE_DATE, time());
$service->setDataFileRowParam(PladformService::PARAM_STATUS, PladformService::STATUS_DOWNLOAD_START);
$service->saveData();

// иницилизируем API
$api = new PladformApi();
$api->setLogin($pladform_config['login']);
$api->setPassword($pladform_config['password']);
$api->setStoredir(ENGINE_DIR . "/modules/pladform/storage");
$api->setPladformService($service);

// получаем токен
$token = $api->getToken();
if (empty($token)) 
{
    $service->setDataFileRowParam(PladformService::PARAM_STATUS, PladformService::STATUS_DOWNLOAD_ERROR);
    $service->setDataFileRowParam(PladformService::PARAM_LAST_UPDATE_DATE, time());
    $service->setDataFileRowParam(PladformService::PARAM_LAST_ERROR, implode(", ", $api->getError()));
    $service->setDataFileRowParam(PladformService::PARAM_DOWNLOAD_TIME_END, time());
    $service->saveData();
    exit(1);
}

$service->setDataFileRowParam(PladformService::PARAM_LAST_UPDATE_DATE, time());
$service->saveData();

// пытаемся скачать файл
$api->download($token);
if (!empty($api->getError()))
{
    $service->setDataFileRowParam(PladformService::PARAM_STATUS, PladformService::STATUS_DOWNLOAD_ERROR);
    $service->setDataFileRowParam(PladformService::PARAM_LAST_UPDATE_DATE, time());
    $service->setDataFileRowParam(PladformService::PARAM_LAST_ERROR, implode(", ", $api->getError()));
    $service->setDataFileRowParam(PladformService::PARAM_DOWNLOAD_TIME_END, time());
    $service->saveData();
    exit(1);
}

$service->setDataFileRowParam(PladformService::PARAM_FILENAME, $api->getDownloadedFilename());
$service->setDataFileRowParam(PladformService::PARAM_STATUS, PladformService::STATUS_DOWNLOAD_END);
$service->setDataFileRowParam(PladformService::PARAM_LAST_UPDATE_DATE, time());
$service->setDataFileRowParam(PladformService::PARAM_DOWNLOAD_TIME_END, time());
$service->saveData();


$service->setDataFileRowParam(PladformService::PARAM_STATUS, PladformService::STATUS_PARSING_START);
$service->setDataFileRowParam(PladformService::PARAM_LAST_UPDATE_DATE, time());
$service->setDataFileRowParam(PladformService::PARAM_PARSING_TIME_START, time());
$service->saveData();




// парсим файл
$parser = new JsonParser();
$listener = new ApiListener($db, $pladform_config, $service);
$parser->parse(ENGINE_DIR . "/modules/pladform/storage/" . $api->getDownloadedFilename(), $listener);
//$parser->parse(ENGINE_DIR . "/modules/pladform/storage/20190405.gz", $listener);

$service->setDataFileRowParam(PladformService::PARAM_STATUS, PladformService::STATUS_PARSING_END);
$service->setDataFileRowParam(PladformService::PARAM_LAST_UPDATE_DATE, time());
$service->setDataFileRowParam(PladformService::PARAM_PARSING_TIME_END, time());
$service->saveData();
