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
    $service = new PladformService();
    if( $action == "run" ) 
    {
        $service->runProcess();
        sleep(2); // поспим чтобы прошли процессы старта
    }

    $freespace = disk_free_space($store_path);
    $totalspace = disk_total_space($store_path);
    $data = $service->getData();
    
    /**
     * нужно пробежаться по дате и посмотреть есть ли запущенный процесс не доведенный до конца
     * 1. Если процесса нет, то активируем кнопку загрузки и парсинга
     * 2. Если процесс есть, но last_update_date > суток, то сообщаем пользователю что возможно что-то есть, но кнопку активируем
     * 3. Если процесс есть и last_update_date < суток, то блокируем кнопку
     */
    $block_dp_btn = false;
    foreach($data['files'] as $row)
    {
        if ($row[PladformService::PARAM_STATUS]    != PladformService::STATUS_PARSING_END 
            && $row[PladformService::PARAM_STATUS] != PladformService::STATUS_PARSING_ERROR
            && $row[PladformService::PARAM_STATUS] != PladformService::STATUS_DOWNLOAD_ERROR
            && time() - $row[PladformService::PARAM_LAST_UPDATE_DATE] < 86400
        ) {
            $block_dp_btn = true;
            break;
        }
    }
    
    ?>
    <div class="box">
        <div class="box-header">
            <div class="title">Отчет</div>
        </div>
        <div class="box-content">
            <div class="row box-section">
                <p>
                    <b>Дирректория хранения файлов выгрузки:</b> '<?php echo $store_path; ?>': 
                </p>
                <p>
                    <b>Свободное место на диске:</b> <?php echo round($freespace / 1024 / 1024); ?> Mb
                </p>
                <p>
                    <b>Занятое место на диске:</b> <?php echo ceil($totalspace / 1024 / 1024); ?> Mb
                </p>
                <p>
                <b>История: </b>
                <table border='1' class="table table-normal">
                    <tr>
                        <th>Имя файла</th>
                        <th>Статус</th>
                        <th>Дата последнего обновления</th>
                        <th>Дата начала загрузки</th>
                        <th>Дата окончания загрузки</th>
                        <th>Дата начала парсинга</th>
                        <th>Дата окончания парсинга</th>
                        <th>Обработано видео</th>
                        <th>Добавлено видео</th>
                        <th>Ошибка</th>
                    </tr>
                    <?php foreach($data['files'] as $row): ?>
                        <tr>
                            <td><?php echo $row[PladformService::PARAM_FILENAME] ?></td>
                            <td>
                                <?php if ($row[PladformService::PARAM_STATUS] == PladformService::STATUS_PARSING_END): ?>
                                    <i class="icon-ok"></i> 
                                <?php elseif ($row[PladformService::PARAM_STATUS] == PladformService::STATUS_DOWNLOAD_ERROR || $row[PladformService::PARAM_STATUS] == PladformService::STATUS_PARSING_ERROR): ?>
                                    <i class="icon-warning-sign"></i> 
                                <?php else: ?>
                                    <i class="icon-spinner"></i> 
                                <?php endif ?>
                                    
                                <?php echo $service->statuses[$row[PladformService::PARAM_STATUS]] ?>
                            </td>
                            <td><?php echo !empty($row[PladformService::PARAM_LAST_UPDATE_DATE]) ? date("Y-m-d H:i:s", $row[PladformService::PARAM_LAST_UPDATE_DATE]) : "-" ?></td>
                            <td><?php echo !empty($row[PladformService::PARAM_DOWNLOAD_TIME_START]) ? date("Y-m-d H:i:s", $row[PladformService::PARAM_DOWNLOAD_TIME_START]) : "-" ?></td>
                            <td><?php echo !empty($row[PladformService::PARAM_DOWNLOAD_TIME_END]) ? date("Y-m-d H:i:s", $row[PladformService::PARAM_DOWNLOAD_TIME_END]) : "-" ?></td>
                            <td><?php echo !empty($row[PladformService::PARAM_PARSING_TIME_START]) ? date("Y-m-d H:i:s", $row[PladformService::PARAM_PARSING_TIME_START]) : "-" ?></td>
                            <td><?php echo !empty($row[PladformService::PARAM_PARSING_TIME_END]) ? date("Y-m-d H:i:s", $row[PladformService::PARAM_PARSING_TIME_END]) : "-" ?></td>
                            <td><?php echo $row[PladformService::PARAM_CNT_ALL] ?></td>
                            <td><?php echo $row[PladformService::PARAM_CNT_ADDED] ?></td>
                            <td><?php echo $row[PladformService::PARAM_LAST_ERROR] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                </p>

                <form action="" method="post">
                    <input type="hidden" name="action" value="run">
                    <input type="submit" value="Загрузить и распарсить файл" <?php echo $block_dp_btn ? "disabled='disabled'" : "" ?> class="btn btn-green">
                </form>
            </div>
        </div>
    </div>
    
    <?php
}

echofooter();
?>