<?PHP

$pladform_config = array (
    
    # Логин в Pladform
    'login' => 'login',
    
    # Пароль в Pladform
    'password' => 'password',
    
    # ID плеера в Pladform (создается в личном кабинете Pladform)
    'player_id' => 1,
    
    # Фильтр по категориям. Массив ID категорий в системе pladform
    'filter_category' => [],
    
    # Фильтр по проектам. Массив ID проектов в системе pladform
    'filter_project' => [],
    
    # Фильтр по жанрам. Массив ID жанров в системе pladform
    'filter_genres' => [],
    
    # Имя пользователя, под кем будет создаваться пост
    'post_author' => 'admin',
    
    # Ширина плеера
    'iframe_width' => 720,
    
    # Высота плеера
    'iframe_height' => 480,
    
    # кол-во знаков текст видео для короткого описания поста
    'post_short_size' => 300,
    
    # Формат даты
    'date_format' => 'Y-m-d H:i:s',
    
    # HTML шаблон заголовка
    'template_post_title' => '{VIDEO_TITLE}', 
    
    # HTML шаблон предосмотра
    'template_post_short' => '<div align="center"><img src="{VIDEO_COVER}"></div><div>{VIDEO_SHORT}</div>',
    
    # HTML шаблон страницы поста
    'template_post_full' => '<table>'
                            . '<tr><td style="vertical-align: top"><img src="{VIDEO_COVER}" width="400px"></td>'
                            . '<td style="padding-left: 10px; vertical-align: top">'
                                . '<b>Проект:</b> {PROJECT_TITLE}<br />'
                                . '<b>Сезон:</b> {SEASON_TITLE}<br />'
                                . '<b>Серия:</b> {VIDEO_EPISODE}<br />'
                                . '<b>Длительность:</b> {VIDEO_DURATION}<br />'
                                . '<b>Дата выхода:</b> {VIDEO_RELEASE}<br />'
                                . '<b>Категория:</b> {CATEGORY_TITLE}<br />'
                                . '<b>Жанры:</b> {VIDEO_GENRES}<br />'
                                . '<b>Теги:</b> {VIDEO_TAGS}<br />'
                            . '</td></tr></table>'
                            . '<div style="padding-top: 30px; padding-bottom: 30px">{VIDEO_FULL}</div>'
                            . '<div align="center">{PLAYER}</div>',
    
    # Включение-выключение логирования. Позволяет получить файл с детальным логом обработки. 
    # Данную опцию рекомендуется включать для отладки, т.к. потребляет достаточно много места на диске.
    # Файл логов будет доступен для скачивания в панели управления модулем.
    'log' => false,
);

?>