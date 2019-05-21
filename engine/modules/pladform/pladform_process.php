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

// инициализируем логгер
$logger = new PladformLogger();
if ($pladform_config['log'])
{
    $logger_filename =  date("U") . ".log";
    $logger->setFilename(ENGINE_DIR . '/modules/pladform/storage/' . $logger_filename);
}

// инициализируем report
$reportService = new ReportService($db);
$report = $reportService->init(array(
    'status'              => PladformService::STATUS_DOWNLOAD_START,
    'download_time_start' => date("Y-m-d H:i:s"),
    'last_update_date'    => date("Y-m-d H:i:s"),
    'logfile'             => $logger_filename
));


// иницилизируем API
$api = new PladformApi();
$api->setLogin($pladform_config['login']);
$api->setPassword($pladform_config['password']);
$api->setStoredir(ENGINE_DIR . "/modules/pladform/storage");
$api->setReportService($reportService);
$api->setLogger($logger);

// получаем токен
$token = $api->getToken();
if (empty($token)) 
{
    $report = $reportService->update(array(
        'status'            => PladformService::STATUS_DOWNLOAD_ERROR,
        'download_time_end' => date("Y-m-d H:i:s"),
        'last_update_date'  => date("Y-m-d H:i:s"),
        'last_error'        => implode(", ", $api->getError()),
    ));
    $logger->log("Завершение работы. Причина: не получен токен в pladform");
    exit(1);
}
else
{
    $logger->log("Получен токен: " . $token);
}

$reportService->update(array('last_update_date' => date("Y-m-d H:i:s")));

// пытаемся скачать файл
$api->download($token);
if (!empty($api->getError()))
{
    $reportService->update(array(
        'status'            => PladformService::STATUS_DOWNLOAD_ERROR,
        'download_time_end' => date("Y-m-d H:i:s"),
        'last_update_date'  => date("Y-m-d H:i:s"),
        'last_error'        => str_replace("'", "", implode(", ", $api->getError())),
    ));
    $logger->log("Завершение работы. Причина: ошибки при получении файла выгрузки." . "\n" . implode(", ", $api->getError()));
    exit(1);
}
else
{
    $logger->log("Файл был успешно скачан: " . $api->getDownloadedFilename() );
}

$reportService->update(array(
    'status'            => PladformService::STATUS_DOWNLOAD_END,
    'download_time_end' => date("Y-m-d H:i:s"),
    'last_update_date'  => date("Y-m-d H:i:s"),
    'filename'          => $api->getDownloadedFilename(),
));

$reportService->update(array(
    'status'             => PladformService::STATUS_PARSING_START,
    'parsing_time_start' => date("Y-m-d H:i:s"),
    'last_update_date'   => date("Y-m-d H:i:s"),
));

// парсим файл
$parser = new JsonParser();
$listener = new ApiListener($db, $pladform_config, $service, $logger);
$parser->parse(ENGINE_DIR . "/modules/pladform/storage/" . $api->getDownloadedFilename(), $listener);

$reportService->update(array(
    'status'           => PladformService::STATUS_PARSING_END,
    'parsing_time_end' => date("Y-m-d H:i:s"),
    'last_update_date' => date("Y-m-d H:i:s"),
));

$logger->log("Успешное завершение работы");