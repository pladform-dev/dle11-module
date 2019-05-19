Модуль загрузки и обработки каталога видео pladform для Data life Engine version 11 

ТРЕБОВАНИЯ
1. PHP версии 5.6 и выше
2. Модули php:
    - php-mbstring
    - php-json
    - php-curl
    - gzip
3. max_execution_time = 0 или более 3600. Обусловлено долгой обработкой данных приложением


УСТАНОВКА

1. Скачать модуль. Последняя версия тут - https://github.com/pladform-dev/dle11-module
2. Скопировать папку ./engine в корень проекта. 
    В дирректории ./engine/modiles должна появится дирректория pladform. 
    В дирректории ./engine/inc должен появится файл pladform.php
3. Изменить права доступа к дирректории ./engine/modules/pladform/storage на запись, выполнив команду:
    chmod -R 0777 ./engine/modules/pladform/storage
4. В базе данных DLE MySQL выполнить запрос:
    CREATE TABLE `{PREFIX}_pladform_report` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `filename` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
      `status` smallint(6) NOT NULL DEFAULT '0',
      `download_time_start` timestamp NULL DEFAULT NULL,
      `download_time_end` timestamp NULL DEFAULT NULL,
      `parsing_time_start` timestamp NULL DEFAULT NULL,
      `parsing_time_end` timestamp NULL DEFAULT NULL,
      `video_all` int(11) NOT NULL DEFAULT '0',
      `video_added` int(11) NOT NULL DEFAULT '0',
      `last_update_date` timestamp NULL DEFAULT NULL,
      `last_error` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
      `logfile` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    при этом заменить {PREFIX} на префикс используемый в вашей базе данных - это можно увидеть посмотрев на префикс любой таблицы базы данных.
5. В базе данных DLE MySQL выполнить запрос:
    INSERT INTO `{PREFIX}_admin_sections` (`name`, `title`, `descr`, `icon`, `allow_groups`) VALUES ('pladform', 'Pladform', 'Pladform модуль', '', '1');
    при этом заменить {PREFIX} на префикс используемый в вашей базе данных - это можно увидеть посмотрев на префикс любой таблицы базы данных.
6. Отредактировать файл настроек ./engine/modules/pladform/config.php
    Все параметры конфигурирования описаны в файле настроек.


ИСПОЛЬЗОВАНИЕ

1. Зайти в панель администрирования, в пункте меню "Сторонние модули" появится пункт "Pladform".
2. Если какие-то пункты требований не соответствуют значениям для работы модуля, они будут отображены на экране. Пока вы их все не выполните, дальнейшая работа модуля невозможна.
3. Возможен ручной запуск обработки видео, для этого перейти в панель управления модулем и в случае отсутствия запущенных процессов обработки будет доступна кнопка ручного запуска обработки.
4. Возможна автоматическая загрузка и обработка данных через CRON, для этого в файл конфигурации CRON (обычно /etc/crontab) нужно добавить строку:
    0 6 * * * /usr/bin/php {PATH_TO_DLE}/engine/modules/pladform/pladform_process.php
    где {PATH_TO DLE} нужно заменить на свой путь к Data Life Engine. 
    Также следует проверить корректность пути до PHP и пользователя, под которым запускается парсинг. Пользователя следует указать того, под кем происходит запуск PHP.
    Следует обязательно проверить наличие перевода сроки после записи - это известная особенность CRON вследствии чего задание может не выполниться.
    Данный пример поставит задачу на запуск обработки видео в 6 утра ежедневно. Подробности управления CRON см. https://help.ubuntu.ru/wiki/cron
    Чаще чем 1 раз в сутки устанавливать обработку не имеет смысла, т.к. Pladform отдает файл выгрузки сформированный только за 1 сутки.
5. Добавление видео в DLE состоит из 2-х этапов: 
    - Загрузка файла выгрузки из pladform. Сохранение файла выгрузки происходит в дирректорию  ./engine/modules/pladform/storage. 
    Pladform возвращает файл-архив со всем видео доступный в текущий день выгрузки для указанного партнера.
    - Обработка файла выгрузки.
6. Для отладки используйте настройку 'log' => true в файле конфигурации. При включенной настройке будет писаться лог в файл о каждом действии обработчика. 


МАКРОСЫ ШАБЛОНОВ

{VIDEO_TITLE}    - Наименование видео
{VIDEO_SHORT}    - Короткое описание видео
{VIDEO_FULL}     - Полное описание видео
{VIDEO_COVER}    - Пикшот видео (ссылка)
{VIDEO_EPISODE}  - Номер эпизода видео
{VIDEO_RELEASE}  - Дата выхода видео
{VIDEO_GENRES}   - Жанры видео через запятую
{VIDEO_TAGS}     - Теги видео через запятую
{PROJECT_TITLE}  - Наименование проекта
{SEASON_TITLE}   - Наименование сезона
{CATEGORY_TITLE} - Наименование категории
{PLAYER}         - Плеер (Iframe)
