<?php

if( ! defined( 'DATALIFEENGINE' ) ) {
	die( "Hacking attempt!" );
}

set_time_limit(0);
ini_set('max_execution_time', 0); // контрольный. Иногда только так и работает

require_once ENGINE_DIR . '/modules/pladform/autoload.php';
echoheader("Pladform", "Админпанель модуля Pladform");

$is_available_actions = true;
// проверить наличие всех модулей
if (!extension_loaded("curl")) 
{
    $is_available_actions = false;
    echo '<div class="well relative"><span class="triangle-button red"><i class="icon-bell"></i></span>Не установлено расширение php-curl.</div>';
}
    
if (!extension_loaded("mbstring")) 
{
    $is_available_actions = false;
    echo '<div class="well relative"><span class="triangle-button red"><i class="icon-bell"></i></span>Не установлено расширение php-mbstring.</div>';
}
    
if (!extension_loaded("json")) 
{
    $is_available_actions = false;
    echo '<div class="well relative"><span class="triangle-button red"><i class="icon-bell"></i></span>Не установлено расширение php-json.</div>';
}
    
$store_path = ENGINE_DIR . "/modules/pladform/storage";
if (!is_writable($store_path)) 
{
    $is_available_actions = false;
    echo '<div class="well relative"><span class="triangle-button red"><i class="icon-bell"></i></span>Дирректория <b>' . $store_path . '</b> не доступна для записи.</div>';
}
    
if (ini_get('max_execution_time') > 0 && ini_get('max_execution_time') < 3600) 
{
    $is_available_actions = false;
    echo '<div class="well relative"><span class="triangle-button red"><i class="icon-bell"></i></span>Установите переменную PHP <b>max_execution_time=0</b> или более 3600 сек., т.к. для обработки файлов требуется долгое время обработки.<br>Текущее значение: <b>max_execution_time=' . ini_get('max_execution_time') . '</b></div>';
}

if ($is_available_actions)
{
    $reportService = new ReportService($db);
    $is_run_process = PladformService::isRunProcess();
    if (!$is_run_process) 
    {
        if( $action == "run" ) 
        {
            PladformService::runProcess();
            sleep(2); // поспим чтобы прошли процессы старта
        }
        else if( $action == "clear" ) 
        {
            $reportService->clear();
        }
        else
        {
            $reportService->validateReports();
        }
    }

    $freespace = disk_free_space($store_path);
    $totalspace = disk_total_space($store_path);
    $reports = $reportService->getAll();
    
    ?>

    <?php if ($is_run_process): ?>
        <div class="well relative"><span class="triangle-button green"><i class="icon-ok-circle"></i></span><b>В данный момент происходит импорт видео, дождитесь его окончания.</b> Автообновление страницы произойдет через <span id="ssec">0</span> сек.</div>
        <script>
            var ssal = 30;
            setInterval(function(){
                document.getElementById("ssec").innerHTML = ssal;
                if (ssal <= 0) {
                    window.location.href = "?mod=pladform";
                }
                ssal --;
            }, 1000);
        </script>
    <?php endif ?>

    <div class="box">
        <div class="box-content">
            <div class="row box-section">
                <p>
                    <b>Дирректория хранения файлов выгрузки:</b> '<?php echo $store_path; ?>' 
                </p>
                <p>
                    <b>Свободное место на диске:</b> <?php echo round($freespace / 1024 / 1024); ?> Mb
                </p>
                <p>
                    <b>Занятое место на диске:</b> <?php echo ceil($totalspace / 1024 / 1024); ?> Mb
                </p>
                <?php if (!$is_run_process): ?>
                    <table width="100%">
                    <tr>
                        <td>
                            <form action="" method="post">
                                <input type="hidden" name="action" value="run">
                                <input type="submit" value="Запустить импорт сейчас" class="btn btn-green">
                            </form>
                        </td>
                        <td style="padding-left: 20px" align="right">
                            <form action="" method="post">
                                <input type="hidden" name="action" value="clear">
                                <input type="submit" value="Очистить историю обработок" class="btn btn-red">
                            </form>
                        </td>
                    </tr>
                    </table>
                <?php endif ?>
                <p>
                <table border='1' class="table table-normal">
                    <tr>
                        <th>Имя файла</th>
                        <th>Последний статус</th>
                        <th>Дата последнего обновления</th>
                        <th>Дата начала загрузки</th>
                        <th>Дата окончания загрузки</th>
                        <th>Дата начала обработки</th>
                        <th>Дата окончания обработка</th>
                        <th>Обработано видео</th>
                        <th>Добавлено видео</th>
                        <th>Ошибка</th>
                        <th>Лог</th>
                    </tr>
                    <?php foreach($reports as $row): ?>
                        <tr>
                            <td><?php echo $row['filename'] ?></td>
                            <td>
                                <?php if ($row['status'] == PladformService::STATUS_PARSING_END): ?>
                                    <i class="icon-ok"></i> 
                                <?php elseif ($row['status'] == PladformService::STATUS_DOWNLOAD_ERROR || $row['status'] == PladformService::STATUS_PARSING_ERROR): ?>
                                    <i class="icon-warning-sign"></i> 
                                <?php else: ?>
                                    <i class="icon-spinner"></i> 
                                <?php endif ?>
                                    
                                <?php echo PladformService::$statuses[$row['status']] ?>
                            </td>
                            <td><?php echo !empty($row['last_update_date'])    ? $row['last_update_date']    : "-" ?></td>
                            <td><?php echo !empty($row['download_time_start']) ? $row['download_time_start'] : "-" ?></td>
                            <td><?php echo !empty($row['download_time_end'])   ? $row['download_time_end']   : "-" ?></td>
                            <td><?php echo !empty($row['parsing_time_start'])  ? $row['parsing_time_start']  : "-" ?></td>
                            <td><?php echo !empty($row['parsing_time_end'])    ? $row['parsing_time_end']    : "-" ?></td>
                            <td><?php echo $row['video_all'] ?></td>
                            <td><?php echo $row['video_added'] ?></td>
                            <td><?php echo $row['last_error'] ?></td>
                            <td>
                                <?php if (!empty($row['logfile'])): ?>
                                    <a target="_blank" href="<?php echo "/engine/modules/pladform/storage/" . $row['logfile'] ?>"><?php echo $row['logfile'] ?></a>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                </p>

            </div>
        </div>
    </div>
    
    <?php
}

echofooter();
?>