<?php

class ApiListener implements JsonListener
{
    private $db;
    
    private $config;
    
    private $categories = [];
    
    private $all_count = 0;
    
    private $added_count = 0;
    
    private $pladform_service;
    
    public function __construct($db, $config, $pladform_service) 
    {
        $this->db = $db;
        $this->config = $config;
        $this->pladform_service = $pladform_service;
    }

    /**
     * @param string $jsonObject
     * @todo метатеги 
     */
    public function onObjectFound($jsonObject)
    {
        $this->all_count ++;
        
        try {
        
            $video = json_decode($jsonObject, true);

            //echo "<pre>";
            //print_r($video);

            // Фильтрация
            // по категориям
            if (count($this->config['filter_category']) > 0 && !in_array($video['relationships']['category']['id'], $this->config['filter_category'])) {
                return ;
            }
            // по проектам
            if (count($this->config['filter_project']) > 0 && !in_array($video['relationships']['project']['id'], $this->config['filter_project'])) {
                return ;
            }
            // по жанрам
            if (count($this->config['filter_project']) > 0)
            {
                $is_filtered = true;
                foreach ($video['meta']['genres'] as $genre) 
                {
                    if (in_array($video['relationships']['project']['id'], $this->config['filter_project'])) {
                        $is_filtered = false;
                    }
                }
                if ($is_filtered) {
                    return ;
                }
            }


            $row = $this->db->super_query( "SELECT id FROM " . PREFIX . "_post WHERE alt_name='" . $video['id'] . "'" );
            if(!$row['id']) 
            {
                // все про категорию
                $category_id    = $video['relationships']['category']['id'];
                $category_title = $video['relationships']['category']['title'];

                if (!isset($this->categories[$category_id])) // если в кеше категории нет, то ищем ее в базе 
                {
                    $category_alt_name = $this->translit($video['relationships']['category']['title']) . "-" . $category_id;
                    $row = $this->db->super_query( "SELECT id FROM " . PREFIX . "_category WHERE alt_name='" . $category_alt_name . "'" );
                    if (isset($row['id'])) 
                    {
                        $this->categories[$category_id] = $row['id'];
                    } 
                    else 
                    {
                        $this->db->query("INSERT INTO " . PREFIX . "_category (name, alt_name) VALUES ('" . $category_title . "','" . $category_alt_name . "') ", false);
                        $this->categories[$category_id] = $this->db->insert_id();

                    }
                }
                $post_category_id = $this->categories[$category_id];

                // все про видео
                $video_title    = str_replace("'", "\'", $video['meta']['title']);
                $video_short    = mb_strimwidth(str_replace("'", "\'", $video['meta']['description']), 0, $this->config['post_short_size'], "...");
                $video_full     = str_replace("'", "\'", $video['meta']['description']);
                $player         = '<iframe src="//out.pladform.ru/player?pl=' . $this->config['player_id'] . '"  width="' . $this->config['iframe_width'] . '" height="' . $this->config['iframe_height'] . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowfullscreen></iframe>'; 
                $video_cover    = $video['meta']['cover'];
                $video_episode  = $video['meta']['episode'];
                $video_duration = sprintf('%02d мин %02d сек', $video['meta']['duration'] / 60, $video['meta']['duration'] % 60);
                $video_release  = date($this->config['date_format'], strtotime($video['meta']['shooting_date']));
                $video_genres   = "";
                foreach ($video['meta']['genres'] as $genre) {
                    $video_genres .= (!empty($video_genres) ? ", " : "") . $genre['title'];
                }
                $project_title  = $video['relationships']['project']['title'];
                $season_title   = isset($video['relationships']['season']) ? $video['relationships']['season']['title'] : "";

                $video_tags = implode(", ", $video['extra']['tags']);
                if (!empty($video_genres)) {
                    $video_tags .= (!empty($video_tags) ? ", " : "") . $video_genres;
                }


                $post_title = $this->config['template_post_title'];
                $post_title = str_replace('{VIDEO_TITLE}',    $video_title,    $post_title);
                $post_title = str_replace('{VIDEO_EPISODE}',  $video_episode,  $post_title);
                $post_title = str_replace('{VIDEO_DURATION}', $video_duration, $post_title);
                $post_title = str_replace('{VIDEO_RELEASE}',  $video_release,  $post_title);
                $post_title = str_replace('{PROJECT_TITLE}',  $project_title,  $post_title);
                $post_title = str_replace('{SEASON_TITLE}',   $season_title,   $post_title);        
                $post_title = str_replace('{CATEGORY_TITLE}', $category_title, $post_title);      

                $post_short = $this->config['template_post_short'];
                $post_short = str_replace('{VIDEO_TITLE}',    $video_title,    $post_short);
                $post_short = str_replace('{VIDEO_SHORT}',    $video_short,    $post_short);
                $post_short = str_replace('{VIDEO_FULL}',     $video_full,     $post_short);
                $post_short = str_replace('{VIDEO_COVER}',    $video_cover,    $post_short);
                $post_short = str_replace('{PLAYER}',         $player,         $post_short);
                $post_short = str_replace('{VIDEO_EPISODE}',  $video_episode,  $post_short);
                $post_short = str_replace('{VIDEO_DURATION}', $video_duration, $post_short);
                $post_short = str_replace('{VIDEO_RELEASE}',  $video_release,  $post_short);
                $post_short = str_replace('{VIDEO_GENRES}',   $video_genres,   $post_short);
                $post_short = str_replace('{VIDEO_TAGS}',     $video_tags,     $post_short);
                $post_short = str_replace('{PROJECT_TITLE}',  $project_title,  $post_short);
                $post_short = str_replace('{SEASON_TITLE}',   $season_title,   $post_short);
                $post_short = str_replace('{CATEGORY_TITLE}', $category_title, $post_short);

                $post_full = $this->config['template_post_full'];
                $post_full = str_replace('{VIDEO_TITLE}',    $video_title,    $post_full);
                $post_full = str_replace('{VIDEO_SHORT}',    $video_short,    $post_full);
                $post_full = str_replace('{VIDEO_FULL}',     $video_full,     $post_full);
                $post_full = str_replace('{VIDEO_COVER}',    $video_cover,    $post_full);
                $post_full = str_replace('{PLAYER}',         $player,         $post_full);
                $post_full = str_replace('{VIDEO_EPISODE}',  $video_episode,  $post_full);
                $post_full = str_replace('{VIDEO_DURATION}', $video_duration, $post_full);
                $post_full = str_replace('{VIDEO_RELEASE}',  $video_release,  $post_full);
                $post_full = str_replace('{VIDEO_GENRES}',   $video_genres,   $post_full);
                $post_full = str_replace('{VIDEO_TAGS}',     $video_tags,     $post_full);
                $post_full = str_replace('{PROJECT_TITLE}',  $project_title,  $post_full);
                $post_full = str_replace('{SEASON_TITLE}',   $season_title,   $post_full);
                $post_full = str_replace('{CATEGORY_TITLE}', $category_title, $post_full);

                $this->db->query("INSERT INTO " . PREFIX . "_post ("
                            . "autor,"
                            . "date,"
                            . "short_story,"
                            . "title,"
                            . "full_story,"
                            . "category,"
                            . "alt_name,"
                            . "approve"
                        . ") VALUES (" 
                            . "'" . $this->config['post_author'] . "',"
                            . "'" . date("Y-m-d H:i:s") . "',"
                            . "'" . $post_short . "',"
                            . "'" . $post_title . "',"
                            . "'" . $post_full . "',"
                            . "'" . $post_category_id . "',"
                            . "'" . $video['id'] . "',"
                            . "'" . "1" . "'"
                        . ") ", false);
                $post_id = $this->db->insert_id();

                // теги
                foreach(explode(", ", $video_tags) as $tag)
                {
                    if (!empty($tag)) {
                        $this->db->query("INSERT INTO " . PREFIX . "_tags (news_id, tag) VALUES ('" . $post_id . "','" . $tag . "') ", false);
                    }
                }

                $this->added_count ++;
            }
        } catch (Exception $e) {
            echo $e;
        }
        
        $this->pladform_service->setDataFileRowParam(PladformService::PARAM_LAST_UPDATE_DATE, time());
        $this->pladform_service->setDataFileRowParam(PladformService::PARAM_CNT_ALL, $this->all_count);
        $this->pladform_service->setDataFileRowParam(PladformService::PARAM_CNT_ADDED, $this->added_count);
        $this->pladform_service->saveData();
    }

    public function onStart()
    {
        //echo date('H:i:s') . "> Let's go";
    }

    public function onEnd()
    {
        //echo date('H:i:s') . "> Ready";
    }

    public function onError(\Exception $e)
    {
        //echo date('H:i:s') . "> Exception: " . $e->getMessage();
    }

    public function onStreamRead($textChunk, $streamPosition)
    {
        //echo date('H:i:s') . "> Chunk length: " . strlen($textChunk) . ". Stream position: $streamPosition" . PHP_EOL;
    }
    
    private function translit($s) 
    {
        $s = (string) $s; // преобразуем в строковое значение
        $s = strip_tags($s); // убираем HTML-теги
        $s = str_replace(array("\n", "\r"), " ", $s); // убираем перевод каретки
        $s = preg_replace("/\s+/", ' ', $s); // удаляем повторяющие пробелы
        $s = trim($s); // убираем пробелы в начале и конце строки
        $s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); // переводим строку в нижний регистр (иногда надо задать локаль)
        $s = strtr($s, array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya','ъ'=>'','ь'=>''));
        $s = preg_replace("/[^0-9a-z-_ ]/i", "", $s); // очищаем строку от недопустимых символов
        $s = str_replace(" ", "-", $s); // заменяем пробелы знаком минус
        return $s; // возвращаем результат
    }
    
    public function getAddedCount() {
        return $this->added_count;
    }
}