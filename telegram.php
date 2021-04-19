<?php

/*
    Для установки сертификата ввести строке браузера
    https://api.telegram.org/bot<token>/setwebhook?url=https://task.itburg.pro/<путь_до_скрипта>/telegram_webhook.php
 
    Для удаления WebHook
    https://api.telegram.org/bot<token>/setwebhook
    
    Алгоритм создания персонального бота для клиента
    1) создаем в папке сайта папку "bot-<имя бота>"
    2) в созданной папке создаем папку с длинным случайным именем
    3) копируем в нее файлы config.php и telegram_webhook.php из любого другого бота
    4) создаем копию базы SQL, чистим заявки, категории, группы категорий, лог, юзеров
    5) создаем бота через BotFather
    6) прописываем параметры базы и бота в файле config.php (имя бота, токен, имя базы)
    7) Регистрируем через TOR вебхук: https://api.telegram.org/bot<token>/setwebhook?url=https://task.itburg.pro/<путь_до_скрипта>/telegram_webhook.php
    8) Начинаем чат с ботом, вводим команду /start и вписываем через phpmyadmin нового админа
    
    
    
*/

        mb_internal_encoding('UTF-8');
        
        include 'init.php'; 
        //include('config.php');
        //include('./core/db.php');
        //include('./core/function2.php');

        $db=new db();
        // read incoming info and grab the chatID
        $content    = file_get_contents("php://input");
        $update     = json_decode($content, true);
        $chat['result'][]=$update;
        bdlog($content);
        
        //exit;
        $botname=$GLOBALS['telegram']['botname'];
        $bot = new TelegramBot() ;
        //$user=userinfo($update['message']['chat']['id']);
        //$return=$bot->sendMessage(155090468, "ok");

        $out2='';
        
        
        //$chat=$bot->getUpdates(get_last_telegramid());
        $out2.="last update: ".date("Y-m-d H:i:s",time())."<br/>";
        $out2.=json_encode($chat)."<br/>";
        //$task_obj=new Task(557);
        
        //$answer['text']="test22522";
        //$bot->editMessageReplyMarkup(155090468, 1752, $answer['text'],$answer);
        //$task_obj->update_task(array('text'=>'999000'));
        //print_r(get_tasklist(array('chat_id'=>155090468,'my_dep'=>true)));
        //print_r($task_obj);
        //print_r(userinfo(155090468));
        //print_r(get_tasklist(array('chat_id'=>$chat_id,'by_eng'=>true)));

        // При получении сообщения запускаем обработчик
        if (isset($chat['result'][0])) {
            foreach($chat['result'] as $key => $value) {
                //print_r($value);
                //bdlog($value);
                // Если был получен ответ на нажатие кнопки, то обрабатываем его, иначе - обрабатываем обычное сообщение
                if (substr($value['callback_query']['data'],0,1)=='/') {
                    // Вставим в команду номер заявки
                    $value['message']['text']=$value['callback_query']['data'];
                    $value['message']['chat']['id']=$value['callback_query']['message']['chat']['id'];
                    $value['message']['message_id']=$value['callback_query']['message']['message_id'];
                    $answer=parse_telegram_command($value);
                    //$bot->sendMessage($value['message']['chat']['id'], $answer['text'],$answer);

                // Если получена команда, то обрабатываем её
                } elseif (substr($value['message']['text'],0,1)=='/') {
                    // Последнее сообщение
                   $lastmsg=get_last_msg($value['message']['chat']['id']);
                   // Если команда состоит из цифр, то это номер заявки, напишем ее в lastmsg
                    if (is_numeric(substr($value['message']['text'],1))) {
                        set_last_msg($value['message']['chat']['id'], substr($value['message']['text'],1));
                    }
                    $answer=parse_telegram_command($value);
                    //$bot->sendMessage($value['message']['chat']['id'], $answer['text'],$answer);

                // Если получен обычный текст, то проверяем, не связан ли он с нажатием кнопки
                } else {
                   $lastmsg=get_last_msg($value['message']['chat']['id']);
                    
                    // Если это добавление группы категорий
                    if ($lastmsg=='/adm-catgroup-add') {
                        $params=split("_",$lastmsg);
                        set_last_msg($value['message']['chat']['id'], $params[1]);
                        $value['message']['text']="/adm-catgroup-add-apply_".$value['message']['text'];
                        $answer=parse_telegram_command($value);
                       
                    // Если это добавление категори
                    } elseif ($lastmsg=='/adm-cat-add') {
                        $params=split("_",$lastmsg);
                        //set_last_msg($value['message']['chat']['id'], $params[1]);
                        $value['message']['text']="/adm-cat-add-selgroup_".$value['message']['text'];
                        $answer=parse_telegram_command($value);
                       
                    // Если это переименование группы категорий
                    } elseif (substr($lastmsg,0,26)=='/adm-selectedcatgroup-edit') {
                        $params=split("_",$lastmsg);
                        //set_last_msg($value['message']['chat']['id'], $params[1]);
                        $value['message']['text']="/adm-selectedcatgroup-edit-apply_".$params[1]."_".$value['message']['text'];
                        $answer=parse_telegram_command($value);
                                              
                    // Если это переименование категории
                    } elseif (substr($lastmsg,0,13)=='/adm-cat-edit') {
                        $params=split("_",$lastmsg);
                        //set_last_msg($value['message']['chat']['id'], $params[1]);
                        $value['message']['text']="/adm-cat-edit-apply_".$params[1]."_".$value['message']['text'];
                        $answer=parse_telegram_command($value);
                                              
                    // Если это переименование пользователя
                    } elseif (substr($lastmsg,0,32)=='/adm-selecteduser-options-rename') {
                        $params=split("_",$lastmsg);
                        //set_last_msg($value['message']['chat']['id'], $params[1]);
                        $value['message']['text']="/adm-selecteduser-options-rename-apply_".$params[1]."_".$value['message']['text'];
                        $answer=parse_telegram_command($value);
                                              
                    // Если это новый идентификатор пользователя
                    } elseif (substr($lastmsg,0,34)=='/adm-selecteduser-options-changeid') {
                        $params=split("_",$lastmsg);
                        //set_last_msg($value['message']['chat']['id'], $params[1]);
                        $value['message']['text']="/adm-selecteduser-options-changeid-apply_".$params[1]."_".$value['message']['text'];
                        $answer=parse_telegram_command($value);
                                              
                    // Если это комментарий
                    } elseif (substr($lastmsg,0,18)=='/task-commentapply') {
                        $params=split("_",$lastmsg,2);
                        set_last_msg($value['message']['chat']['id'], $params[1]);
                        $value['message']['text']="/task-commentapply_".$params[1].'_'.$value['message']['text'];
                        $answer=parse_telegram_command($value);
                       
                    // Если это текст подзадачи
                    } elseif (substr($lastmsg,0,29)=='/task-optionslist-optionapply') {
                        $params=split("_",$lastmsg,2);
                        set_last_msg($value['message']['chat']['id'], $params[1]);
                        $value['message']['text']="/task-optionslist-optionapply_".$params[1].'_'.$value['message']['text'];
                        $answer=parse_telegram_command($value);
                       
                    // Если это дедлайн
                    } elseif (substr($lastmsg,0,19)=='/task-deadlineapply') {
                        $params=split("_",$lastmsg,2);
                        set_last_msg($value['message']['chat']['id'], $params[1]);
                        $value['message']['text']='/task-deadlineapply_'.$params[1].'_'.$value['message']['text'];
                        $answer=parse_telegram_command($value);
                       
                    // Если это период
                    } elseif (substr($lastmsg,0,16)=='/task-period-set') {
                        $params=split("_",$lastmsg,2);
                        set_last_msg($value['message']['chat']['id'], $params[1]);
                        $value['message']['text']='/task-period-set_'.$params[1].$value['message']['text'];
                        $answer=parse_telegram_command($value);
                       
                    // иначе
                    } else {
                        set_last_msg($value['message']['chat']['id'], $value['message']['text']);
                        $answer['text']= 'Оформить ваше сообщение как заявку? '.$value['message']['chat']['id'].' '.$value['message']['text'];
                        //$answer['reply_markup']=array("inline_keyboard" => array(array(array("text"=>"Оформить заявку","callback_data"=>'/applytask')),array(array("text"=>"Отмена","callback_data"=>'/cancel'))));
                       
                        if (is_numeric(substr($lastmsg,1))) {
                            
                        }
                        $answer['reply_markup']=array(
                            "inline_keyboard" => array(
                                array(array(
                                    "text"          =>  "Оформить заявку",
                                    "callback_data" =>  '/applytask'
                                )),
                                array(array(
                                    "text"          =>  "Отмена",
                                    "callback_data" =>  '/cancel'
                                ))
                            )
                        );
                        
                    }
                    
                }
                
                $lastmsg_id=get_last_msgid($value['message']['chat']['id']);
                //$answer['text']=$lastmsg_id."\r\n".$answer['text'];
                if (isset($answer['messagetype'])) {
                    if ($answer['messagetype']=='sendMessage') {
                        $bot->sendMessage($value['message']['chat']['id'], $answer['text'],$answer);
                    } elseif ($answer['messagetype']=='editMessageReplyMarkup') {
                        bdlog($bot->editMessageReplyMarkup($value['message']['chat']['id'], $value['message']['message_id'], $answer['text'],$answer));
                        //bdlog($bot->sendMessage($value['message']['chat']['id'], $answer['text'],$answer));
                        
                    }
                    
                } else {
                    $bot->sendMessage($value['message']['chat']['id'], $answer['text'],$answer);
                    
                }
                $lastid=$value['update_id'];
                if (isset($lastid)) {set_last_telegramid($lastid);};
            }
        } else {
            //-------  БЛОК рассылки уведомлений
            
            $notify_flag=array();
            
            // Отбираем все заявки URGENT и проверяем, какие из уведомлений не отсылались более 10 минут
            $userarr=array();
            $utasks=get_urgent_tasks();
            
            if ($utasks) {
                foreach ($utasks as $key=>$utask) {
                    if (!is_last_state($utask['status'])) {
                        $users=array('author','engineer');
                        foreach ($users as $key => $uservalue) {
                            $user=userinfo_by_id($utask[$uservalue]);
                            $timediff=abs(strtotime(date("Y-m-d H:i:s"))-strtotime($user['last_notify']));
                            print("urgent /".$utask['id']." ".(600-$timediff)."<br>");
                            if ($timediff>600 and !isset($notify_flag[$user['id']]['urgent'])) {
                                $userarr[$user['id']]=$uservalue;
                                $notify_flag[$user['id']]['urgent']=1;
                                //set_notify_time($user['id'],date("Y-m-d H:i:s"));
                                $task_obj=new Task($utask['id']);
                                $task=&$task_obj->taskinfo;
                                $answer['text']= "ПОЖАРная заявка /".$task['id'].":\r\n".$task['title']."\r\n";
                                //telegram_notify($task['id'],0,$userarr,$answer['text']);
                                send_telegram_message(array($task[$uservalue]), $answer['text']);
                            }
                                
                                
                        }
                    }
                }
            }
            
            // 
            $userarr=array();
            $dtasks=get_deadline_tasks();
            if ($dtasks) {
                foreach ($dtasks as $key=>$dtask) {
                    if (!is_last_state($dtask['status'])) {
                        $users=array('author','engineer');
                        foreach ($users as $key => $uservalue) {
                            $user=userinfo_by_id($dtask[$uservalue]);
                            $timeleft=strtotime($dtask['deadline'])-strtotime(date("Y-m-d H:i:s"));
                            $plantime=abs(strtotime($dtask['deadline'])-strtotime($dtask['last_notify']));
                            $lastmsgtime=strtotime(date("Y-m-d H:i:s"))-strtotime($user['last_notify']);
                            $startnotify=strtotime(date("Y-m-d H:i:s"))-strtotime($dtask['start_notify']);
                            //print(strtotime($user['last_notify']));    
                            print("deadline /".$dtask['id']." ".round($timeleft/$plantime*100,2)."% , next notify ".(600-$lastmsgtime)." sec<br>");    
                            // Если прошла треть срока, уведомляем, но не чаще, чем раз в 10 минут
                            if (round($timeleft/$plantime*100,2)<67 and $lastmsgtime>600 and $startnotify>=0 and !isset($notify_flag[$user['id']]['deadline'])) {
                                $userarr[$user['id']]=$uservalue;
                                $notify_flag[$user['id']]['deadline']=1;
                                //set_notify_time($user['id'],date("Y-m-d H:i:s"));
                                
                                $task_obj=new Task($dtask['id']);
                                $task=&$task_obj->taskinfo;
                                if ($timeleft>=0) {
                                    $strbegin="НАПОМИНАЮ о заявке ";
                                } else {
                                    $strbegin="ПРОШЛИ сроки заявки ";
                                }
                                $answer['text']= $strbegin."/".$task['id'].", срок ".$task['deadline'].":\r\n".$task['title']."\r\n";
                                send_telegram_message(array($task[$uservalue]), $answer['text']);
                                $task_obj->update_task(
                                    array(
                                        'last_notify'=>date("Y-m-d H:i:s")
                                     ),
                                    0
                                );
                                
                            }
                        }
                        //telegram_notify($task['id'],0,$userarr,$answer['text']);
                    }
                }
            }
            
            $userarr=array();
            $ptasks=get_periodic_tasks();
            if ($ptasks) {
                foreach ($ptasks as $key=>$ptask) {
                    // если у ЗАКРЫТОЙ заявки от старого дедлайна заявки прошло больше времени, чем указано в периоде,
                    // то добавляем к дедлайну размер периода до тех пор, пока дедлайн не станет
                    // больше текущей даты
                    
                    if (time()>strtotime($ptask['deadline'])) {
                        $period_arg=substr($ptask['period'],0,1);
                        $period_val=substr($ptask['period'],1);
                        $task_obj=new Task($ptask['id']);
                        //print_r($ptask);
                        if ($period_arg=="d") {
                            // повтор каждые несколько дней
                            //$newdeadline=strtotime(date("Y-m-d H:i:s"))
                            $newdeadline=strtotime($ptask['deadline'])+$period_val*86400;
                            $newstartnotify=strtotime($ptask['start_notify'])+$period_val*86400;
                            if ($newstartnotify>$newdeadline) {$newstartnotify=$newdeadline-86400;}
                            
                        } elseif ($period_arg=="m") {
                            // определенное число каждого месяца (задается в дедлайне)
                            $day=substr($ptask['start_notify'],8,2);
                            $month=1+substr($ptask['start_notify'],5,2);
                            $year=substr($ptask['start_notify'],0,4);
                            if ($month>12) {
                                $month-=12;
                                $year+=1;
                            }
                            $time=substr($ptask['start_notify'],11);
                            $newstartnotify=strtotime($year."-".$month."-".$day." ".$time);
                            
                            $day=substr($ptask['deadline'],8,2);
                            $month=1+substr($ptask['deadline'],5,2);
                            $year=substr($ptask['deadline'],0,4);
                            if ($month>12) {
                                $month-=12;
                                $year+=1;
                            }
                            $time=substr($ptask['deadline'],11);
                            $newdeadline=strtotime($year."-".$month."-".$day." ".$time);
                            
                        } elseif ($period_arg=="s") {
                            // каждый квартал (число дней задается в дедлайне)
                            $day=substr($ptask['start_notify'],8,2);
                            $month=3+substr($ptask['start_notify'],5,2);
                            $year=substr($ptask['start_notify'],0,4);
                            if ($month>12) {
                                $month-=12;
                                $year+=1;
                            }
                            $time=substr($ptask['start_notify'],11);
                            $newstartnotify=strtotime($year."-".$month."-".$day." ".$time);
                            
                            $day=substr($ptask['deadline'],8,2);
                            $month=3+substr($ptask['deadline'],5,2);
                            $year=substr($ptask['deadline'],0,4);
                            if ($month>12) {
                                $month-=12;
                                $year+=1;
                            }
                            $time=substr($ptask['deadline'],11);
                            $newdeadline=strtotime($year."-".$month."-".$day." ".$time);
                        }
                        $out2.="<br>Task ".$ptask['id'];
                        $out2.="<br>New deadline".date("Y-m-d H:i:s",$newdeadline);
                        $out2.="<br>New start notify".date("Y-m-d H:i:s",$newstartnotify);
                        
                        $task_obj->update_task(
                            array(
                                'deadline'=>date("Y-m-d H:i:s",$newdeadline),
                                'start_notify'=>date("Y-m-d H:i:s",$newstartnotify),
                                'status'=>1
                             ),
                            0
                        );
                        $task=$task_obj->taskinfo;
                        $answer['text']= "Периодическая задача /".$ptask['id']." возобновлена\r\n".$ptask['title']."\r\nСрок: ".date("Y-m-d H:i:s",$newdeadline);
                        send_telegram_message(array($task['author'],$task['engineer']), $answer['text']);
                        //telegram_notify($ptask['id'],0,array('author','engineer'),$answer['text']);
                    }
                }
            }
            
            foreach ($notify_flag as $key => $value) {
                set_notify_time($key,date("Y-m-d H:i:s"));
            }
            
            
            // ----------------------------------
        }

        if (isset($value['message']['message_id']) and $value['message']['message_id']>0) {set_last_msgid($value['message']['chat']['id'],$value['message']['message_id']);}
        echo $out2;

    
    function parse_telegram_command($value) {
        // Команда передается в формате Команда_Значение
        global $db;        
        global $bot;

        $chat_id=$value['message']['chat']['id'];
        $user=userinfo_by_id($chat_id);
        
    if (!user_authorised($chat_id) and ($value['message']['text']!="/start")) {
        $answer['text']= "Вы не авторизованы. Введите команду /start и сообщите полученный идентификатор администратору.";
        return $answer;
    } else {
        
        // Запретить административные действия неадминам
        if (substr($value['message']['text'],0,5)=='/adm-' and $user['group_ext']['mname']!='admin') {
            $answer['text']= 'Нет прав';
            return $answer;
        }
        
        $lastmsg=get_last_msg($chat_id);
        $cmdarr=split("_",$value['message']['text'],2);
        $value['message']['text']=$cmdarr[0];
        echo ($value['message']['text']);
            // Формируем список чатов, которые авторизованы для работы
            //$chatid_list=get_chatid_list();
            //if (in_array($value['message']['chat']['id'],$chatid_list)) {
                // Какое-нибудь событие для известных chat_id    
            //}
        
        // Если работаем с заявкой, то опознаем команду, либо выводим данные о заявке
        if (is_numeric($lastmsg)) {
            $task_id=$lastmsg;
            $task_obj=new Task($task_id);
            $task=&$task_obj->taskinfo;
            $msg=$value['message']['text'];
            // Если получили номер заявки, то выводим информацию
            if (    is_numeric(substr($msg,1)) and
                    $msg=="/".substr($msg,1)        ) {
                $value['message']['text']='/taskopts';
            }
        }
        
        // Начальные массивы кнопок

        $answer_buttons_set['locations']=array(
                        "inline_keyboard" => array(
                            array(
                                  array("text"  =>  "СтацББ",        "callback_data" =>  '/location_2'),
                                  array("text"  =>  "ПО2",           "callback_data" =>  '/location_3'),
                                  array("text"  =>  "Адм",           "callback_data" =>  '/location_4')
                            ),
                            array(array("text"  =>  "ПО1",           "callback_data" =>  '/location_5'),
                                  array("text"  =>  "Седова",        "callback_data" =>  '/location_6'),
                                  array("text"  =>  "Билимб.",       "callback_data" =>  '/location_11')
                            ),
                            array(array("text"  =>  "Северка",       "callback_data" =>  '/location_7'),
                                  array("text"  =>  "Машинистов",    "callback_data" =>  '/location_8'),
                                  array("text"  =>  "Сулимова",      "callback_data" =>  '/location_9')
                            )
                        ),
                        "resize_keyboard" => true
                    );
        $answer_buttons_set['category']=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Картридж",     "callback_data" =>  '/category_1'),
                                  array("text"  =>  "Принтер",      "callback_data" =>  '/category_2')
                            ),
                            array(array("text"  =>  "ТК",           "callback_data" =>  '/category_3'),
                                  array("text"  =>  "ПК",           "callback_data" =>  '/category_4'),
                                  array("text"  =>  "Сеть",         "callback_data" =>  '/category_5')
                            ),
                            array(array("text"  =>  "Оргвопросы",   "callback_data" =>  '/category_6' ))
                        ),
                        "resize_keyboard" => true
                    );
        $answer_buttons_set['taskoptionlist']=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Расположение", "callback_data" =>  '/taskoptionlist_locations')),
                            array(array("text"  =>  "Категория",    "callback_data" =>  '/taskoptionlist_category')),
                            /*array(array("text"  =>  "Срочность",    "callback_data" =>  '/taskoptionlist_priority')),*/
                            array(array("text"  =>  "Исполнитель",  "callback_data" =>  '/taskoptionlist_engineer')),
                            array(array("text"  =>  "Срок",       "callback_data" =>  '/deadline'),
                                  array("text"  =>  "ПОЖАР",    "callback_data" =>  '/urgent')),
                            array(array("text"  =>  "Комментарий",    "callback_data" =>  '/comment')),
                            array(array("text"  =>  "В работе",    "callback_data" =>  '/statuswork'),
                                  array("text"  =>  "ВЫПОЛНЕНА",    "callback_data" =>  '/statusok'))
                        ));
        $answer_buttons_set['engineer']=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Общая",     "callback_data" =>  '/engineer_1')),
                            array(array("text"  =>  "Дегтярев Алексей","callback_data" =>  '/engineer_5')),
                            array(array("text"  =>  "Таланов Илья",           "callback_data" =>  '/engineer_22')),
                            array(array("text"  =>  "Таланов Андрей",           "callback_data" =>  '/engineer_23'))
                        ));
        $answer_buttons_set['adminpanel']=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Группы категорий",     "callback_data" =>  '/adm-catgroup-options')),
                            array(array("text"  =>  "Категории",     "callback_data" =>  '/adm-cat-options')),
                            array(array("text"  =>  "Пользователи",     "callback_data" =>  '/adm-users-options'))
                        ));


        $answer_buttons_set['adm-catgroup-options']=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Вывести список",     "callback_data" =>  '/adm-catgroup-list')),
                            array(array("text"  =>  "Добавить группу категорий",     "callback_data" =>  '/adm-catgroup-add'))
                        ));
        $answer_buttons_set['adm-selectedcatgroup-options']=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Переименовать",     "callback_data" =>  '/adm-selectedcatgroup-edit')),
                            array(array("text"  =>  "Удалить",     "callback_data" =>  '/adm-selectedcatgroup-delete'))
                        ));

        $answer_buttons_set['adm-cat-options']=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Вывести список",     "callback_data" =>  '/adm-cat-selgroup')),
                            array(array("text"  =>  "Добавить категорию",     "callback_data" =>  '/adm-cat-add'))
                        ));
        $answer_buttons_set['adm-selectedcat-options']=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Переименовать",     "callback_data" =>  '/adm-cat-edit')),
                            array(array("text"  =>  "Сменить группу категорий",     "callback_data" =>  '/adm-cat-changegroup')),
                            array(array("text"  =>  "Удалить",     "callback_data" =>  '/adm-cat-delete'))
                        ));

        $answer_buttons_set['adm-users-options']=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Вывести список",     "callback_data" =>  '/adm-user-list')),
                            array(array("text"  =>  "Добавить пользователя",     "callback_data" =>  '/adm-user-add-seluser'))
                        ));
        $answer_buttons_set['adm-selecteduser-options']=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Переименовать",     "callback_data" =>  '/adm-selecteduser-options-rename')),
                            array(array("text"  =>  "Сменить подразделение",     "callback_data" =>  '/adm-selecteduser-options-newdepart')),
                            array(array("text"  =>  "Назначить права",     "callback_data" =>  '/adm-selecteduser-options-changegroup')),
                            array(array("text"  =>  "Сменить ID",     "callback_data" =>  '/adm-selecteduser-options-changeid')),
                            array(array("text"  =>  "Заблокировать",     "callback_data" =>  '/adm-selecteduser-options-block'))
                        ));
        
        switch ($value['message']['text']) {



            // Быстрое открытие заявки
            case '/applytask':
                $param=get_last_msg($chat_id);
                //$param=array();
                if ($param) {
                    $user=userinfo_by_chatid($chat_id);
                    $task_obj=new Task(array(   'title'=>$param,
                                                'text'=>$param,
                                                'author'=>$user['id'],
                                                'engineer'=>$user['id']
                    ));
                    $task=$task_obj->taskinfo;
                    $task_id=$task['id'];
                    //$answer['text']= "Заявка принята с номером /".$param." и закреплена за вами (как исполнитель)\r\nВыберите первую категорию.";
                    $param=set_last_msg($chat_id,$task_id);
                   
                    $answer['text']= "Заявка принята с номером /".$task_id." и закреплена за вами (как исполнитель)\r\nВыберите МЕСТО выполнения работ.";
                    
                    /*
                    $answer_buttons_set=array();
                    $answer_buttons_set['resize_keyboard']=true;
                    $answer_buttons_set=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Опции",    "callback_data" =>  '/task-optionslist_'.$task_id))
                        ));
                    */
                    
                    $catgroup_arr=locations_list();
                    $answer_buttons_set=array();
                    $answer_buttons_set['resize_keyboard']=true;
                    foreach ($catgroup_arr as $key => $value) {
                        $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  $value['name'],        "callback_data" =>  '/applytask-selcat2_'.$task_id.'_'.$value['id']);
                    }
                    
                    
                    $answer['reply_markup']=$answer_buttons_set;


                }
                break;
            
            case '/applytask-selcat2':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $cat_id=$splitcmdarr[1];
                $category=loc_info($cat_id);
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                $task_obj->update_task(array('cat_location'=>$category['id'],'updated'=>date("Y-m-d H:i:s")),$chat_id);
                $answer['text']= "Заявка /".$task_id." привязана к месту ".$category['name']."\r\nВыберите ОБЪЕКТ работ.";
                $catgroup_arr=objects_list();
                $answer_buttons_set=array();
                $answer_buttons_set['resize_keyboard']=true;
                foreach ($catgroup_arr as $key => $value) {
                    $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  $value['name'],        "callback_data" =>  '/fasttask-engineer-englist_'.$task_id.'_'.$value['id']);
                }
                
                $answer['reply_markup']=$answer_buttons_set;
                break;
            case '/fasttask-engineer-englist':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $cat_id=$splitcmdarr[1];
                $category=obj_info($cat_id);
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                $task_obj->update_task(array('cat_object'=>$category['id'],'updated'=>date("Y-m-d H:i:s")),$chat_id);
                $answer['text']= "Заявка /".$task_id." привязана к объекту ".$category['name']."\r\nВыберите ИСПОЛНИТЕЛЯ";
                $userarr=users_list();
                $answer_buttons_set=array();
                $answer_buttons_set['resize_keyboard']=true;
                foreach ($userarr as $key => $value) {
                    if ($task['engineer']==$value['id']) {$value['name']="**".$value['name']."**";}
                    $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  $value['name'],        "callback_data" =>  '/task-engineer-seteng_'.$task_id.'_'.$value['id']);
                }
                $answer['reply_markup']=$answer_buttons_set;
                break;

            
            
            
            case '/cancel':
                $param=set_last_msg($value['message']['chat']['id'],null);
                $answer['text']= 'Заявка отменена';
                break;
            // Работа с заявкой
            case '/taskopts':
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;


                if ($task['urgent']==1) {
                    $urgentflag="<b>!!!ПОЖАР!!!</b> ";
                } else {$urgentflag="";}
                if ($task['deadline']>0) {$deadlinestr="\r\n<b>СРОК до ".date("H:i d.m.Y ",strtotime($task['deadline']))."</b>";}
                if (!$task['period']) {
                    $periodstr= "";
                } else {
                    if (substr($task['period'],0,1)=='d') {$tmpstr="дней";
                        } elseif (substr($task['period'],0,1)=='m') {$tmpstr="месяцев";
                        } elseif (substr($task['period'],0,1)=='s') {$tmpstr="кварталов";
                    }
                    $periodstr= "\r\n<b>Периодическая</b> задача: каждые ".substr($task['period'],1)." $tmpstr";
                }

                $commentstr="";
                if (isset($task['comments'])) {
                    $commentstr="\r\n<b>Комментарии:</b>";
                    foreach ($task['comments'] as $key => $value) {
                        $commentstr.="\r\n".$value['text'];
                    }
                    $commentstr.="\r\n";
                }
                $catlist.=$task['cat_location_ext']['name'].' '.$task['cat_object_ext']['name'];
                $answer['text']=
                    "Заявка /".
                    $task['id'].
                    "\r\n<i>".$catlist."</i>".
                    "\r\n".$task['text']."\r\n".
                    $urgentflag.
                    $deadlinestr.
                    $periodstr.
                    "\r\nАвтор: ".$task['author_ext']['name'].
                    "\r\nИсп-ль: ".$task['engineer_ext']['name'].
                    $commentstr.
                    "\r\nВыберите опцию для заявки";
                set_last_msg($chat_id,$task['id']);
                
                $optcntr=0;
                foreach ($task['option'] as $key => $value) {
                    if ($value['closed']==0) {$optcntr++;}
                }
                
                $answer_buttons_set=array();
                $answer_buttons_set['resize_keyboard']=true;
                if ($task['urgent']==1) {$urgentstr="**ПОЖАР**";} else {$urgentstr="ПОЖАР";}
                if ($task['status']==2) {$statworkstr="**В работе**";} else {$statworkstr="В работе";}
                if ($task['status']==6) {$statokstr="**ВЫПОЛНЕНА**";} else {$statokstr="ВЫПОЛНЕНА";}
                if ($task['deadline']!='0000-00-00 00:00:00') {$deadlinestr="**СРОК**";} else {$deadlinestr="Срок";}
                if (isset($task['option'])) {
                    $answer_buttons_set=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Список подзадач (".$optcntr.")",    "callback_data" =>  '/task-optionslist-list_'.$task['id'])),
                            array(array("text"  =>  "Опции",    "callback_data" =>  '/task-optionslist_'.$task['id'])),
                            array(array("text"  =>  $deadlinestr,       "callback_data" =>  '/task-deadline_'.$task_id),
                                  array("text"  =>  $periodstr,    "callback_data" =>  '/task-period_'.$task_id)),
                            array(array("text"  =>  $urgentstr,    "callback_data" =>  '/task-urgent_'.$task_id)),
                            array(array("text"  =>  $statworkstr,    "callback_data" =>  '/task-statuswork_'.$task_id),
                                  array("text"  =>  $statokstr,    "callback_data" =>  '/task-statusok_'.$task_id))
                    ));
                } else {
                    $answer_buttons_set=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "Опции",    "callback_data" =>  '/task-optionslist_'.$task['id'])),
                        array(array("text"  =>  $deadlinestr,       "callback_data" =>  '/task-deadline_'.$task_id),
                              array("text"  =>  $urgentstr,    "callback_data" =>  '/task-urgent_'.$task_id)),
                            array(array("text"  =>  $statworkstr,    "callback_data" =>  '/task-statuswork_'.$task_id),
                                  array("text"  =>  $statokstr,    "callback_data" =>  '/task-statusok_'.$task_id))
                    ));
                }
                $answer['reply_markup']=$answer_buttons_set;
                
                /*                
                */
                
                break;
            case '/task-optionslist':
                $task_id=$cmdarr[1];
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                if ($task['urgent']==1) {$urgentstr="**ПОЖАР**";} else {$urgentstr="ПОЖАР";}
                if ($task['status']==2) {$statworkstr="**В работе**";} else {$statworkstr="В работе";}
                if ($task['status']==6) {$statokstr="**ВЫПОЛНЕНА**";} else {$statokstr="ВЫПОЛНЕНА";}
                if ($task['deadline']!='0000-00-00 00:00:00') {$deadlinestr="**СРОК**";} else {$deadlinestr="Срок";}
                if ($task['period']!='') {$periodstr="**ПЕРИОД**";} else {$periodstr="Период";}
                $answer_buttons_set=array();
                $answer_buttons_set['resize_keyboard']=true;
                $answer_buttons_set=array(
                    "inline_keyboard" => array(
                        array(array("text"  =>  "Место",    "callback_data" =>  '/task-location-selloc_'.$task_id),
                              array("text"  =>  "Объект",    "callback_data" =>  '/task-object-selobj_'.$task_id)),
                        array(array("text"  =>  "Исполнитель",  "callback_data" =>  '/task-engineer-englist_'.$task_id)),
                        array(array("text"  =>  $deadlinestr,       "callback_data" =>  '/task-deadline_'.$task_id),
                              array("text"  =>  $periodstr,    "callback_data" =>  '/task-period_'.$task_id)),
                        array(array("text"  =>  $urgentstr,    "callback_data" =>  '/task-urgent_'.$task_id)),
                        array(array("text"  =>  "Подзадачи",    "callback_data" =>  '/task-optionslist-list_'.$task_id)),
                        array(array("text"  =>  "Комментарий",    "callback_data" =>  '/task-comment_'.$task_id)),
                        array(array("text"  =>  $statworkstr,    "callback_data" =>  '/task-statuswork_'.$task_id),
                              array("text"  =>  $statokstr,    "callback_data" =>  '/task-statusok_'.$task_id))
                ));
                $answer['reply_markup']=$answer_buttons_set;
                $answer['text']= "Выб1ерите опцию для заявки /$task_id";
            break;
            case '/task-location-selloc':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                    $answer['text']= "Выберите МЕСТО выполнения работ.";
                    $catgroup_arr=locations_list();
                    $answer_buttons_set=array();
                    $answer_buttons_set['resize_keyboard']=true;
                    foreach ($catgroup_arr as $key => $value) {
                        $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  $value['name'],        "callback_data" =>  '/task-location-setloc_'.$task_id.'_'.$value['id']);
                    }
                    
                    
                    $answer['reply_markup']=$answer_buttons_set;
                break;
            case '/task-location-setloc':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $cat_id=$splitcmdarr[1];
                $category=loc_info($cat_id);
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                $task_obj->update_task(array('cat_location'=>$category['id'],'updated'=>date("Y-m-d H:i:s")),$chat_id);
                $answer['text']= "Заявка /".$task_id." привязана к месту ".$category['name'];
                break;
            case '/task-object-selobj':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $answer['text']= "Выберите ОБЪЕКТ работ.";
                $catgroup_arr=objects_list();
                $answer_buttons_set=array();
                $answer_buttons_set['resize_keyboard']=true;
                foreach ($catgroup_arr as $key => $value) {
                    $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  $value['name'],        "callback_data" =>  '/task-object-setobj_'.$task_id.'_'.$value['id']);
                }
                
                $answer['reply_markup']=$answer_buttons_set;
                break;
            case '/task-object-setobj':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $cat_id=$splitcmdarr[1];
                $category=obj_info($cat_id);
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                $task_obj->update_task(array('cat_object'=>$category['id'],'updated'=>date("Y-m-d H:i:s")),$chat_id);
                $answer['text']= "Заявка /".$task_id." привязана к объекту ".$category['name'];
                break;






            case '/task-optionslist-list':
                $task_id=$cmdarr[1];
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                $answer_buttons_set=array();
                $answer_buttons_set['resize_keyboard']=true;
                foreach ($task['option'] as $key => $value) {
                    $interval = strtotime(date("d.m.Y H:i:s"))-strtotime($value['date']);
                    if ($value['closed']==0) {
                        $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  $value['text'],        "callback_data" =>  '/task-optionslist-optionset_'.$task_id.'_'.$value['id']);
                    } elseif ($interval<3600) {
                        $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  "--- ".$value['text']." (".round($interval/60,0)." мин.)",        "callback_data" =>  '/task-optionslist-optionset_'.$task_id.'_'.$value['id']);
                    }
                }
                $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  "Добавить подзадачи",        "callback_data" =>  '/task-optionslist-optionadd_'.$task_id);
                $answer['reply_markup']=$answer_buttons_set;
                $answer['text']= "Нажмите на подзадачу для завершения";
            break;
            case '/task-optionslist-optionset':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $option_id=$splitcmdarr[1];
                $task_obj=new Task($task_id);
                $answer['text']= "Задача \"".$task['option'][$option_id]['text']."\" ".$task_obj->update_task_option($option_id).". \r\nНажмите на другую подзадачу для завершения";
                //$task_obj->update_task_option($option_id);
                $task=$task_obj->taskinfo;
                
                
                $answer_buttons_set=array();
                $answer_buttons_set['resize_keyboard']=true;
                foreach ($task['option'] as $key => $value) {
                    $interval = strtotime(date("d.m.Y H:i:s"))-strtotime($value['date']);
                    if ($value['closed']==0) {
                        $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  $value['text'],        "callback_data" =>  '/task-optionslist-optionset_'.$task_id.'_'.$value['id']);
                    } elseif ($interval<3600) {
                        $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  "--- ".$value['text']." (".round($interval/60,0)." мин.)",        "callback_data" =>  '/task-optionslist-optionset_'.$task_id.'_'.$value['id']);
                    }
                }
                $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  "Добавить подзадачи",        "callback_data" =>  '/task-optionslist-optionadd_'.$task_id);
                $answer['reply_markup']=$answer_buttons_set;
                $answer['messagetype']='editMessageReplyMarkup';
            break;
            case '/task-optionslist-optionadd':
                $task_id=$cmdarr[1];
                set_last_msg($chat_id,'/task-optionslist-optionapply_'.$task_id);
                $answer['reply_markup']=array(
                    "inline_keyboard" => array(
                        array(array(
                            "text"          =>  "Отмена",
                            "callback_data" =>  '/cancel'
                        ))
                    )
                );
                $answer['text']= "Введите подзадачи (для разделения используйте Ввод на телефоне или Shift-Enter на компьютере)";
            break;
            case '/task-optionslist-optionapply':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $text=$splitcmdarr[1];
                $task_obj=new Task($task_id);
                $task_obj->add_task_option($text);
                $answer['text']= "Подзадачи добавлены.";
                $task=$task_obj->taskinfo;
                
                //telegram_notify($task_id,$chat_id,array('engineer'),);
                send_telegram_message(array($task['engineer']), "К заявке /$task_id ".$task['title']." добавлены подзадачи: \r\n $text");

                
                $answer_buttons_set=array();
                $answer_buttons_set['resize_keyboard']=true;
                foreach ($task['option'] as $key => $value) {
                    $interval = strtotime(date("d.m.Y H:i:s"))-strtotime($value['date']);
                    if ($value['closed']==0) {
                        $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  $value['text'],        "callback_data" =>  '/task-optionslist-optionset_'.$task_id.'_'.$value['id']);
                    } elseif ($interval<3600) {
                        $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  "--- ".$value['text']." (".round($interval/60,0)." мин.)",        "callback_data" =>  '/task-optionslist-optionset_'.$task_id.'_'.$value['id']);
                    }
                }
                $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  "Добавить подзадачи",        "callback_data" =>  '/task-optionslist-optionadd_'.$task_id);
                $answer['reply_markup']=$answer_buttons_set;
            break;
            case '/task-category-selcatgroup':
                $task_id=$cmdarr[1];
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;

                foreach ($task['categories'] as $key => $value) {$catarr[]=$value['category_group'];}

                $answer['text']= 'Выберите группу категорий:';

                $catgroup_arr=category_group_list();
                $answer_buttons_set['locations']=array();
                $answer_buttons_set['locations']['resize_keyboard']=true;
                foreach ($catgroup_arr as $key => $value) {
                    if (in_array($value['id'],$catarr)) {$value['name']="**".$value['name']."**";}
                    $answer_buttons_set['locations']['inline_keyboard'][][]=array("text"  =>  $value['name'],        "callback_data" =>  '/task-category-selcat_'.$task_id.'_'.$value['id']);
                }
                $answer['reply_markup']=$answer_buttons_set['locations'];

               break;
            case '/task-category-selcat':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                foreach ($task['categories'] as $key => $value) {$catarr[]=$value['category_id'];}
                $catgroup_id=$splitcmdarr[1];
                
                $answer['text']= 'Выберите категорию:';
                $catgroup_arr=category_list($catgroup_id);
                $answer_buttons_set=array();
                $answer_buttons_set['resize_keyboard']=true;
                foreach ($catgroup_arr as $key => $value) {
                    if (in_array($value['id'],$catarr)) {$value['name']="**".$value['name']."**";}
                    $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  $value['name'],        "callback_data" =>  '/task-category-setcat_'.$task_id.'_'.$value['id']);
                }
                $answer['reply_markup']=$answer_buttons_set;

               break;
            case '/task-category-setcat':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $cat_id=$splitcmdarr[1];
                $category=cat_info($cat_id);

                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                foreach ($task['categories'] as $key => $value) {$catarr[]=$value['category_id'];}
                if (in_array($cat_id,$catarr)) {
                    $task_obj->unset_category($cat_id);
                    $answer['text']= "Заявка /".$task_id." отвязана от категории ".$category['name'];
                } else {
                    $task_obj->set_category($cat_id);
                    $answer['text']= "Заявка /".$task_id." привязана к категории ".$category['name'];
                    
                }
                break;
            case '/task-engineer-englist':
                $task_id=$cmdarr[1];
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                $userarr=users_list();
/*
                foreach ($userarr as $key => $value) {
                    if ($user['departament']==$value['departament']) {
                        $tmparr=array();
                        $tmparr['id']=$value['id'];
                        $tmparr['name']=$value['name'];
                        $userlist[$value['id']]=$tmparr;
                        }
                }
                $depsarr=departaments_list();
                foreach ($depsarr as $key => $value) {
                    if ($user['departament']!=$value['id']) {
                        $tmparr=array();
                        $tmparr['id']=$value['id'];
                        $tmparr['name']="--".$value['name']."--";
                        $userlist[$value['id']]=$tmparr;
                    }
                }
*/              $userlist=$userarr;
                $answer['text']= 'Выберите исполнителя';//.json_encode($userlist);
                $answer_buttons_set=array();
                $answer_buttons_set['resize_keyboard']=true;
                foreach ($userlist as $key => $value) {
                    if ($task['engineer']==$value['id']) {$value['name']="**".$value['name']."**";}
                    $answer_buttons_set['inline_keyboard'][][]=array("text"  =>  $value['name'],        "callback_data" =>  '/task-engineer-seteng_'.$task_id.'_'.$value['id']);
                }
                $answer['reply_markup']=$answer_buttons_set;

               break;
            case '/task-engineer-seteng':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $eng_id=$splitcmdarr[1];
                $task_obj=new Task($task_id);
                $task_obj->update_task(array('engineer'=>$eng_id));
                $task=$task_obj->taskinfo;
                $answer['text']= 'Заявка /'.$task_id.' ('.$task['title'].') привязана к инженеру '.$task['engineer_ext']['name'];
                send_telegram_message(array($task['engineer'],$task['author']), $answer['text']);
                
/*          
                foreach ($task['categories'] as $key => $value) {
                    $category=cat_info($key);
                    $catlist.=" ".$category['name'];
                }
                if ($task['urgent']==1) {
                    $urgentflag="\r\n<b>!!!ПОЖАР!!!</b> ";
                } else {$urgentflag="";}
                if ($task['deadline']>0) {$deadlinestr="\r\n<b>СРОК до ".date("H:i d.m.Y ",strtotime($task['deadline']))."</b>";}

                if ($eng_id<1000) {
                    $dep=departament_info($eng_id);
                    $answer['text']=
                        "Новая заявка отделу /".
                        $task_id.":".
                        "\r\n<b>".$task_obj->taskinfo['title']."</b>".
                        "\r\n<i>".$catlist."</i>".
                    $urgentflag.
                    $deadlinestr.
                    "\r\nАвтор: ".$task['author_ext']['name'].
                    "\r\nИсп-ль: ".$dep['name'];
                    //telegram_notify($task_id,$chat_id,array('engineer'),$answer['text']);
                    //telegram_notify($task_id,$chat_id,array('engineer'),$answer['text']);
                    send_telegram_message(array($task['engineer']), $answer['text']);
                    $answer['text']= 'Заявка /'.$task_id.' привязана к отделу '.$dep['name'];
                } else {
                    //$answer['text']= "На вас назначена заявка /".$task_id.":\r\n<b>".$task_obj->taskinfo['title']."</b>\r\nРазмещение: ".$task_obj->taskinfo['custorgid_ext']['name']."\r\nКатегория: ".$task_obj->taskinfo['category_ext']['name'];
                    $answer['text']=
                        "На вас назначена заявка /".
                        $task_id.":".
                        "\r\n<b>".$task_obj->taskinfo['title']."</b>".
                        "\r\n<i>".$catlist."</i>".
                    $urgentflag.
                    $deadlinestr.
                    "\r\nАвтор: ".$task['author_ext']['name'].
                    "\r\nИсп-ль: ".$task['engineer_ext']['name'];
                    send_telegram_message(array($task['engineer']), $answer['text']);
                    //telegram_notify($task_id,$chat_id,array('engineer'),$answer['text']);
                    $answer['text']= 'Заявка /'.$task_id.' привязана к инженеру '.$task['engineer_ext']['name'];
            }
*/
            break;

            case '/task-statuswork':
                $stat_id=2;
                $task_id=$cmdarr[1];
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                $task_obj->update_task(array('status'=>$stat_id,'closed'=>"0000-00-00 00:00:00"),$chat_id);

                // Отправляем уведомление о заявке
                $answer['text']= "Заявка /".$task_id."\r\n".$task['title']."\r\nпомечена: <b>В РАБОТЕ</b>";
                //telegram_notify($task_id,$chat_id,array('author','engineer'),$answer['text']);
                send_telegram_message(array($task['author'],$task['engineer']), $answer['text']);
                $answer['text']= "Заявка /".$task_id." В РАБОТЕ";
                break;
            
            case '/task-statusok':
                $stat_id=6;
                $task_id=$cmdarr[1];
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                $task_obj->update_task(array('status'=>$stat_id,'closed'=>date("Y-m-d H:i:s")),$chat_id);

                // Отправляем уведомление о заявке
                $answer['text']= "Заявка /".$task_id."\r\n".$task['title']."\r\n<b>ВЫПОЛНЕНА</b>";
                //telegram_notify($task_id,$chat_id,array('author','engineer'),$answer['text']);
                send_telegram_message(array($task['author'],$task['engineer']), $answer['text']);
                $answer['text']= "Заявка /".$task_id." ВЫПОЛНЕНА";

                break;
            case '/task-comment':
                $task_id=$cmdarr[1];
                set_last_msg($chat_id,'/task-commentapply_'.$task_id);
                $answer['text']= 'Введите комментарий к заявке /'.$task_id;
                break;
            
             case '/task-commentapply':
                $splitcmdarr=split("_",$cmdarr[1],2);
                $task_id=$splitcmdarr[0];
                $commentstr=$splitcmdarr[1];
                $author=userinfo_by_chatid($chat_id);
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                $task_obj->set_comment($task_id,$commentstr,$author['id']);
                $answer['text']= "КОММЕНТАРИЙ к заявке /".$task_id." (".$task['title']."):\r\n".$commentstr;
                //telegram_notify($task_id,$chat_id,array('author','engineer'),$answer['text']);
                send_telegram_message(array($task['author'],$task['engineer']), $answer['text']);
                $answer['text']= "Комментарий добавлен";
                //set_last_msg($chat_id,$task_id);
                break;

            case '/task-urgent':
                $task_id=$cmdarr[1];
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                if ($task['urgent']==1) {
                    $urgentflag=0;
                    $answer['text']= 'Заявка /'.$task_id.': пожар снят.';
                } else {
                    $urgentflag=1;
                    $answer['text']= 'Заявка /'.$task_id.' помечена как срочная. Уведомления будут приходить каждые 10 минут до снятия пожара.';
                }
                $task_obj->update_task(array('urgent'=>$urgentflag),$chat_id);
                //$answer['messagetype']='editMessageReplyMarkup';
                break;
            

            case '/task-deadline':
                $task_id=$cmdarr[1];
                set_last_msg($chat_id,'/task-deadlineapply_'.$task_id);
                $answer['text']= "Введите срок в формате ДД.ММ.ГГГГ ЧЧ:ММ к заявке /".$task_id."\r\n или 0 (ноль) для отключения дедлайна";
                break;
            
             case '/task-deadlineapply':
                $splitcmdarr=split("_",$cmdarr[1],2);
                $task_id=$splitcmdarr[0];
                $deadline=$splitcmdarr[1];
                if ($deadline==0) {
                    $outdd="0000-00-00 00:00:00";
                    $task_obj->update_task(array('deadline'=>$outdd),$chat_id);
                    $answer['text']= 'Выключен дедлайн к заявке /'.$task_id;
                } else {
                    $outdd=date("Y-m-d H:i:s",strtotime($deadline));
                    $task_obj->update_task(array('deadline'=>$outdd),$chat_id);
                    $answer['text']= 'Добавлен дедлайн '.$task['deadline'].' к заявке /'.$task_id;
                    telegram_notify($task_id,$chat_id,array('author','engineer'),$answer['text']);

                    $answer_buttons_set=array();
                    $answer_buttons_set['resize_keyboard']=true;
                    $answer_buttons_set=array(
                        "inline_keyboard" => array(
                            array(array("text"  =>  "2 суток",    "callback_data" =>  '/task-deadline-setstart_'.$task_id.'_48'),
                                  array("text"  =>  "1 сутки",  "callback_data" =>  '/task-deadline-setstart_'.$task_id.'_24')),
                            array(array("text"  =>  "12 часов",    "callback_data" =>  '/task-deadline-setstart_'.$task_id.'_12'),
                                  array("text"  =>  "6 часов",  "callback_data" =>  '/task-deadline-setstart_'.$task_id.'_6')),
                            array(array("text"  =>  "2 часа",    "callback_data" =>  '/task-deadline-setstart_'.$task_id.'_2'),
                                  array("text"  =>  "1 час",  "callback_data" =>  '/task-deadline-setstart_'.$task_id.'_1'))
                    ));
                    $answer['reply_markup']=$answer_buttons_set;
                    $answer['text']= "Начинать уведомлять за ...";

                }
                set_last_msg($chat_id,$task_id);

                break;

            case '/task-deadline-setstart':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                $time=date("Y-m-d H:i:s",strtotime($task['deadline'])-$splitcmdarr[1]*60*60);
                //$time=strtotime($task['deadline'])-$splitcmdarr[1]*60*60;
                //$task_obj=new Task($task_id);
                
                $task_obj->update_task(array('start_notify'=>$time),$chat_id);
                $answer['text']= "Первое уведомление придет: $time";
                break;
        
            case '/task-period':
                $task_id=$cmdarr[1];
                $answer_buttons_set=array();
                $answer_buttons_set['resize_keyboard']=true;
                $answer_buttons_set=array(
                    "inline_keyboard" => array(
                        array(array("text"  =>  "ОТКЛЮЧИТЬ периодичность",    "callback_data" =>  '/task-period-set_'.$task_id.'_')),
                        array(array("text"  =>  "В днях",    "callback_data" =>  '/task-period-entercount_'.$task_id.'_d')),
                        array(array("text"  =>  "В месяцах",    "callback_data" =>  '/task-period-entercount_'.$task_id.'_m')),
                        array(array("text"  =>  "В кварталах",    "callback_data" =>  '/task-period-entercount_'.$task_id.'_s'))
                ));
                $answer['reply_markup']=$answer_buttons_set;
                $answer['text']= "В каких единицах задаем период?";
                break;
        
            case '/task-period-entercount':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $val_name=$splitcmdarr[1];
                set_last_msg($chat_id,'/task-period-set_'.$task_id.'_'.$val_name);
                if ($val_name=='d') {$tmpstr="дней";
                    } elseif ($val_name=='m') {$tmpstr="месяцев";
                    } elseif ($val_name=='s') {$tmpstr="кварталов";
                }
                $answer['text']= "Укажите периодичность (количество $tmpstr) в заявке /$task_id";
                break;
        
            case '/task-period-set':
                $splitcmdarr=split("_",$cmdarr[1]);
                $task_id=$splitcmdarr[0];
                $task_obj=new Task($task_id);
                $task=$task_obj->taskinfo;
                $val_name=$splitcmdarr[1];
                if (!isset($val_name)) {
                    $answer['text']= "Периодичность выключена";
                } else {
                    if (substr($val_name,0,1)=='d') {$tmpstr="дней";
                        } elseif (substr($val_name,0,1)=='m') {$tmpstr="месяцев";
                        } elseif (substr($val_name,0,1)=='s') {$tmpstr="кварталов";
                    }
                    $answer['text']= "Задача /$task_id будет возобновляться каждые ".substr($val_name,1)." $tmpstr относительно текущего СРОКа\r\nНе забудьте установить СРОК";
                    telegram_notify($task_id,$chat_id,array('author','engineer'),$answer['text']);
                    
                }
                $task_obj=new Task($task_id);
                
                $task_obj->update_task(array('period'=>$val_name),$chat_id);
                break;
        
            
            case '/start':
                if ($user['group_ext']['mname']=='admin') {
                    $answer['reply_markup']=array(
                        "keyboard" => array(
                            array(array("text" =>  "/Я - исп."),array("text" =>  "/Я - автор")),
                            array(array("text" =>  "/Все Текущие"),array("text" =>  "/Все Будущие"))
                        ),
                        "resize_keyboard" => true
                    );
                    
                } else {
                    $answer['reply_markup']=array(
                        "keyboard" => array(
                            array(array("text" =>  "/Я - исп."),array("text" =>  "/Я - автор")),
                            array(array("text" =>  "/Все Текущие"),array("text" =>  "/Все Будущие"))
                        ),
                        "resize_keyboard" => true
                    );
                    
                }
                
                $answer['text']= '
Добро пожаловать в систему учета задач (заявок).
Ваш персональный идентификатор: <b>'.$chat_id.'</b>
'.$GLOBALS['site'].'/
<b>Команды</b>
Введите текст заявки или команду:
/start - обновление панели кнопок
/help - краткая справка и список возможностей
                ';
                break;
            case '/help':
                $answer['text']= '
Бот предназначен для быстрого управления списком задач.
Веб-интерфейс доступен по адресу:
'.$GLOBALS['site'].'/
<b>Команды</b>
/start - обновление панели кнопок
/help - краткая справка и список возможностей
<b>Краткий список возможностей:</b>
- создание задач
- назначение места выполнения, типа (объекта) задачи
- назначение исполнителя
- определение сроков
- включение "пожарного" режима (уведомления каждые 10 минут
- определение периодичности (автозапуск задачи по графику)
- комментирование задачи
- автоуведомление участников об изменении в задаче

<b>СОЗДАНИЕ ЗАЯВКИ</b>
1) Отправьте боту текстовое сообщение.
2) Нажмите кнопку "Оформить заявку"
3) Выберите место выполнения работ и объект
4) Выберите исполнителя
Назначенному исполнителю придет уведомление о задаче.

<b>ПРОСМОТР СПИСКА ЗАДАЧ</b>
В панели кнопок (под строкой ввода сообщения) нажмите одну из кнопок:
/Я - исп. - вывод только тех заявок, которые назначены вам для исполнения
/Я - автор - вывод заявок, созданных вами
/Все Текущие - вывод всех доступных заявок, исключая отложенные
/Все Будущие - вывод всех отложенных заявок
В результате нажатия система отправит список заявок. Нажмите на номер заявки, для просмотра подробной информации о заявке.

<b>УПРАВЛЕНИЕ ЗАЯВКОЙ</b>
Открыть окно управления заявкой можно, щелкнув по её номеру в списке заявок (см. предыдущий пункт), либо набрав команду в формате /номер (например, /30).
В главном окне заявки выведены самые популярные опции:
СРОК - установление предельного срока заявки. Вводится <b>вручную</b> в формате ГГГГ-ММ-ДД ЧЧ:ММ, либо 0 (ноль) для отмены срока. При включенной опции система будет уведомлять по прошествии 1/3 срока.
ПОЖАР - установка принудительного уведомления о заявке каждые 10 минут, до момента выполнения заявки или до снятия пожара (путем повторного нажатия кнопки ПОЖАР).
В РАБОТЕ - установка исполнителем признака начала работы над заявкой
ВЫПОЛНЕНА - отметка о выполнении заявки

<b>РАСШИРЕННЫЕ ОПЦИИ</b>
Доступны по нажатию кнопки "Опции" в окне управления заявкой:
МЕСТО - смена места выполнения задачи
ОБЪЕКТ - смена объекта заявки (типа заявки)
ИСПОЛНИТЕЛЬ - смена исполнителя
ПЕРИОД - установка периодичности заявки. Эта опция возобновляет выполненную заявку через определенный период после окончания СРОКА заявки. Период настраивается. Не забудьте установить СРОК, по окончании которого система начнет отсчет периодичности заявки.
ПОДЗАДАЧИ - позволяет прикреплять к задаче список подзадач. Ввод подзадач осуществляется в окне сообщения (одна строка - одна подзадача, перевод строки осуществляется комбинацией Shift-Enter. Отметка о выполнении подзадач ставится путем щелчка по подзадаче. Повторный щелчок по подзадаче в течение 60 минут отменяет выполнение. Выполненная подзадача исчезает из списка по истечении 60 минут.
КОММЕНТАРИЙ - установка комментариев к заявке.
';
                //$answer['reply_markup']=array("inline_keyboard" => array(array(array("text"=>"Мой идентификатор","callback_data"=>'/help'))));
                break;
            case '/password':
                //TODO: Выдать новый логин/пароль для неизвестных и новый пароль для известных
                //$answer['text']= 'Ваш персональный идентификатор: '.$value['message']['chat']['id'].'. Сообщите его администратору системы.';
                //$answer['reply_markup']=array("inline_keyboard" => array(array(array("text"=>"Мой идентификатор","callback_data"=>'/help'))));
                break;
            case '/bot':
                //global $botname;
                $answer['text'] = "Имя бота: ".$GLOBALS['telegram']['botmname'];
                $answer['text'].= "\r\nВладелец: ".$GLOBALS['telegram']['owner'];
                if ($user['group_ext']['mname']=='admin') {
                    $tmpstr=str_replace($GLOBALS['cfg']['include_path'],"",$_SERVER['SCRIPT_FILENAME']);
                    $tmparr=split("/",$tmpstr,2);
                    $answer['text'].= "\r\nПапка бота: ".$tmparr[0];
                }
                break;
            case '/tasklist':
                $answer['text']= get_tasklist($value['message']['chat']['id']);
                break;
            case '/location':
                $loc_id=$cmdarr[1];
                $task_obj->update_task(array('custorgid'=>$loc_id));
                $answer['text']= 'Заявка /'.$task_id.' привязана к размещению '.$task['custorg_ext']['name'];
                break;
            case '/category':
                $cat_id=$cmdarr[1];
                $task_obj->update_task(array('category'=>$cat_id));
                $answer['text']= 'Заявка /'.$task_id.' привязана к категории '.$task['category_ext']['name'];
                break;
            case '/engineer':
                $eng_id=$cmdarr[1];
                $task_obj->update_task(array('engineer'=>$eng_id));
                $answer['text']="На вас назначена заявка /".$task['taskID'].":\r\n".$task['title'];
                telegram_notify($task_id,$chat_id,array('engineer'),$answer['text']);
                $answer['text']= 'Заявка /'.$task_id.' привязана к инженеру '.$task['engineer_ext']['name'];
                break;


            case '/Администрирование':
                $answer['reply_markup']=$answer_buttons_set['adminpanel'];
                //$row=$db->fetch_assoc($db->query("SELECT * FROM `fbd_user_info` WHERE `icq`='".$chat_id."' LIMIT 1"));
                $answer['text']= 'Выберите опцию';
                break;
            case '/adm-catgroup-options':
                $answer['reply_markup']=$answer_buttons_set['adm-catgroup-options'];
                $answer['text']= 'Операции с группами категорий';
                break;
            case '/adm-catgroup-list':
                $answer['text']= 'Выберите группу категорий';
                $catgroupssarr=category_group_list();
                $tmparr=array();
                foreach ($catgroupssarr as $key=>$value) {                   
                    $tmparr[]=array(0 => array('text' => $value['name'],'callback_data' => "/adm-selectedcatgroup-options_".$value['id']));
                }
                $answer['reply_markup']=array("inline_keyboard"=>$tmparr);
                break;
            case '/adm-catgroup-add':
                set_last_msg($chat_id,'/adm-catgroup-add');
                //$answer['reply_markup']=$answer_buttons_set['adm-cat-group-add'];
                //$row=$db->fetch_assoc($db->query("SELECT * FROM `fbd_user_info` WHERE `icq`='".$chat_id."' LIMIT 1"));
                $answer['text']= 'Введите наименование новой группы категорий';
                break;
             case '/adm-catgroup-add-apply':
                $result=category_group_add($cmdarr[1]);
                if ($result) {$answer['text']= "Группа категорий добавлена";} else {$answer['text']= "Группа категорий НЕ добавлена";}
                
                set_last_msg($chat_id,"");
                break;
            case '/adm-selectedcatgroup-options':
                $catgroupinfo=catgroup_info($cmdarr[1]);
                foreach ($catgroupinfo as $key => $value) {
                    $answer['text'].="\r\n".$key." -> ".$value;
                }
                $answer['text'].= "\r\nВыберите опцию";
                // Добавляем id группы категорий в callback кнопки опций
                foreach ($answer_buttons_set['adm-selectedcatgroup-options'] as $key1 => $value1) {
                    foreach ($value1 as $key2 => $value2) {
                        foreach ($value2 as $key3 => $value3) {
                            $answer_buttons_set['adm-selectedcatgroup-options'][$key1][$key2][$key3]['callback_data']=$value3['callback_data']."_".$cmdarr[1];
                        }
                    }
                }
                $answer['reply_markup']=$answer_buttons_set['adm-selectedcatgroup-options'];
                break;
            case '/adm-selectedcatgroup-edit':
                set_last_msg($chat_id,'/adm-selectedcatgroup-edit_'.$cmdarr[1]);
                $answer['text']= 'Введите новое наименование группы категорий';
                break;
             case '/adm-selectedcatgroup-edit-apply':
                $splitcmdarr=split("_",$cmdarr[1]);
                $result=category_group_rename($splitcmdarr[0],$splitcmdarr[1]);
                if ($result) {$answer['text']= "Группа категорий переименована в: ".$splitcmdarr[1];} else {$answer['text']= "Группа категорий НЕ переименована";}
                set_last_msg($chat_id,"");
                break;
            case '/adm-selectedcatgroup-delete':
                $answer['text']= "Уверены?";
                $answer['reply_markup']=array(
                    "inline_keyboard" => array(
                        array(
                            array("text"  =>  "Да",     "callback_data" =>  '/adm-selectedcatgroup-delete-apply_'.$cmdarr[1].'_yes'),
                            array("text"  =>  "Нет",     "callback_data" =>  '/adm-selectedcatgroup-delete-apply_'.$cmdarr[1].'_no')
                        )
                    )
                );
                break;
             case '/adm-selectedcatgroup-delete-apply':
                $splitcmdarr=split("_",$cmdarr[1]);
                if ($splitcmdarr[1]=='yes') {
                    $result=category_group_delete($splitcmdarr[0]);
                    if ($result) {$answer['text']= "Группа категорий удалена";} else {$answer['text']= "Группа категорий НЕ удалена";}
                } else {
                    $answer['text']= "Операция отменена";
                }
                set_last_msg($chat_id,"");
                break;

            case '/adm-cat-options':
                $answer['reply_markup']=$answer_buttons_set['adm-cat-options'];
                $answer['text']= 'Операции с категориями';
                //$answer['messagetype']='editMessageReplyMarkup';
                break;
            case '/adm-cat-add':
                set_last_msg($chat_id,'/adm-cat-add');
                $answer['text']= 'Введите наименование новой категории';
                break;
             case '/adm-cat-add-selgroup':
                $answer['text']= 'Выберите группу категорий';
                $cat_gr_arr=category_group_list();
                $tmparr=array();
                foreach($cat_gr_arr as $key => $value) {
                    $tmparr[]=array(0 => array('text' => $value['name'],'callback_data' => "/adm-cat-add-apply_".$value['id']."_".$cmdarr[1]));
                }
                $answer['reply_markup']=array("inline_keyboard"=>$tmparr);
                
                //set_last_msg($chat_id,"/adm-cat-add-selgroup");
                break;
             case '/adm-cat-add-apply':
                $splitcmdarr=split("_",$cmdarr[1]);
                $result=category_add($splitcmdarr[0],$splitcmdarr[1]);
                if ($result) {$answer['text']= "Группа категорий добавлена";} else {$answer['text']= "Группа категорий НЕ добавлена";}
                
                set_last_msg($chat_id,"");
                break;
            case '/adm-cat-selgroup':
                set_last_msg($chat_id,'');
                $answer['text']= 'Выберите группу категорий';
                $cat_gr_arr=category_group_list();
                $tmparr=array();
                foreach($cat_gr_arr as $key => $value) {
                    $tmparr[]=array(0 => array('text' => $value['name'],'callback_data' => "/adm-cat-list_".$value['id']));
                }
                $answer['reply_markup']=array("inline_keyboard"=>$tmparr);
                break;
            case '/adm-cat-list':
                $answer['text']= 'Выберите категорию';
                $catsarr=category_list($cmdarr[1]);
                $tmparr=array();
                foreach ($catsarr as $key=>$value) {
                    $catgroup=catgroup_info($value['category_group']);
                    $tmparr[]=array(0 => array('text' => $value['name'],'callback_data' => "/adm-selectedcat-options_".$value['id']));
                }
                $answer['reply_markup']=array("inline_keyboard"=>$tmparr);
                break;
            case '/adm-selectedcat-options':
                $catgroupinfo=cat_info($cmdarr[1]);
                foreach ($catgroupinfo as $key => $value) {
                    $answer['text'].="\r\n".$key." -> ".$value;
                }
                $answer['text'].= "\r\nВыберите опцию";
                // Добавляем id группы категорий в callback кнопки опций
                foreach ($answer_buttons_set['adm-selectedcat-options'] as $key1 => $value1) {
                    foreach ($value1 as $key2 => $value2) {
                        foreach ($value2 as $key3 => $value3) {
                            $answer_buttons_set['adm-selectedcat-options'][$key1][$key2][$key3]['callback_data']=$value3['callback_data']."_".$cmdarr[1];
                        }
                    }
                }
                $answer['reply_markup']=$answer_buttons_set['adm-selectedcat-options'];
                break;
            case '/adm-cat-edit':
                set_last_msg($chat_id,'/adm-cat-edit_'.$cmdarr[1]);
                $answer['text']= 'Введите новое наименование категории';
                break;
            case '/adm-cat-edit-apply':
                $splitcmdarr=split("_",$cmdarr[1]);
                $result=category_rename($splitcmdarr[0],$splitcmdarr[1]);
                if ($result) {$answer['text']= "Категория переименована в: ".$splitcmdarr[1];} else {$answer['text']= "Категория НЕ переименована";}
                set_last_msg($chat_id,"");
                break;

            case '/adm-cat-changegroup':
                $answer['text']= 'Выберите НОВУЮ группу категорий';
                $cat_gr_arr=category_group_list();
                $tmparr=array();
                foreach($cat_gr_arr as $key => $value) {
                    $tmparr[]=array(0 => array('text' => $value['name'],'callback_data' => "/adm-cat-changegroup-apply_".$cmdarr[1]."_".$value['id']));
                }
                $answer['reply_markup']=array("inline_keyboard"=>$tmparr);
                break;
            case '/adm-cat-changegroup-apply':
                $splitcmdarr=split("_",$cmdarr[1]);
                $result=category_changegroup($splitcmdarr[0],$splitcmdarr[1]);
                if ($result) {$answer['text']= "Смена привязки выполнена успешно";} else {$answer['text']= "Смена привязки НЕ выполнена";}
                set_last_msg($chat_id,"");
                break;

            case '/adm-cat-delete':
                $answer['text']= "Уверены?";
                $answer['reply_markup']=array(
                    "inline_keyboard" => array(
                        array(
                            array("text"  =>  "Да",     "callback_data" =>  '/adm-cat-delete-apply_'.$cmdarr[1].'_yes'),
                            array("text"  =>  "Нет",     "callback_data" =>  '/adm-cat-delete-apply_'.$cmdarr[1].'_no')
                        )
                    )
                );
                break;
            case '/adm-cat-delete-apply':
                $splitcmdarr=split("_",$cmdarr[1]);
                if ($splitcmdarr[1]=='yes') {
                    $result=category_delete($splitcmdarr[0]);
                    if ($result) {$answer['text']= "Группа категорий удалена";} else {$answer['text']= "Группа категорий НЕ удалена";}
                } else {
                    $answer['text']= "Операция отменена";
                }
                set_last_msg($chat_id,"");
                break;

            case '/adm-users-options':
                $answer['reply_markup']=$answer_buttons_set['adm-users-options'];
                //$userslist=users_list();
                $answer['text']= 'Выберите опцию';
                break;

            case '/adm-user-list':
                $answer['text']= 'Выберите пользователя';
                //$answer['reply_markup']=$answer_buttons_set['adm-users-options'];
                $usersarr=users_list();
                foreach($usersarr as $key => $user) {
                    $newusersarr[$user['departament']][$user['id']]=$user['name'];
                }
                $tmparr=array();
                foreach ($newusersarr as $key=>$value) {                   
                    foreach ($value as $key2=>$value2) {                   
                        $tmparr[]=array(0 => array('text' => $value2." (".get_departament_name($key).")",'callback_data' => "/adm-selecteduser-options_".$key2));
                    }
                }
                $answer['reply_markup']=array("inline_keyboard"=>$tmparr);
                break;
            case '/adm-user-add-seluser':
                $user_id=create_empty_user("Новый пользователь");
                $userinfo=user_info($user_id);
                foreach ($userinfo as $key => $value) {
                    $answer['text'].="\r\n".$key." -> ".$value;
                }
                $answer['text'].= "\r\nВыберите опцию";
                // Добавляем id пользователя в callback кнопки опций
                foreach ($answer_buttons_set['adm-selecteduser-options'] as $key1 => $value1) {
                    foreach ($value1 as $key2 => $value2) {
                        foreach ($value2 as $key3 => $value3) {
                            //print_r($value3);
                            $answer_buttons_set['adm-selecteduser-options'][$key1][$key2][$key3]['callback_data']=$value3['callback_data']."_".$cmdarr[1];
                        }
                    }
                }
                $answer['reply_markup']=$answer_buttons_set['adm-selecteduser-options'];
                break;
            case '/adm-selecteduser-options':
                $userinfo=user_info($cmdarr[1]);
                foreach ($userinfo as $key => $value) {
                    $answer['text'].="\r\n".$key." -> ".$value;
                }
                $answer['text'].= "\r\nВыберите опцию";
                // Добавляем id пользователя в callback кнопки опций
                foreach ($answer_buttons_set['adm-selecteduser-options'] as $key1 => $value1) {
                    foreach ($value1 as $key2 => $value2) {
                        foreach ($value2 as $key3 => $value3) {
                            //print_r($value3);
                            $answer_buttons_set['adm-selecteduser-options'][$key1][$key2][$key3]['callback_data']=$value3['callback_data']."_".$cmdarr[1];
                        }
                    }
                }
                $answer['reply_markup']=$answer_buttons_set['adm-selecteduser-options'];
                break;
            case '/adm-selecteduser-options-block':
                $row=user_update($cmdarr[1],'active',0);
                if ($row) {$answer['text']= 'Пользователь заблокирован';} else {$answer['text']= 'Пользователь НЕ заблокирован';}
                break;
            case '/adm-selecteduser-options-rename':
                set_last_msg($chat_id,'/adm-selecteduser-options-rename_'.$cmdarr[1]);
                $answer['text']= 'Введите новое имя пользователя';
                break;
            case '/adm-selecteduser-options-rename-apply':
                $splitcmdarr=split("_",$cmdarr[1]);
                $result=user_update($splitcmdarr[0],'name',$splitcmdarr[1]);
                if ($result) {$answer['text']= "Пользователь переименован в: ".$splitcmdarr[1];} else {$answer['text']= "Пользователь НЕ переименован";}
                set_last_msg($chat_id,"");
                break;
            case '/adm-selecteduser-options-newdepart':
                $answer['text']= 'Выберите подразделение';
                //$answer['reply_markup']=$answer_buttons_set['adm-users-options'];
                $listarr=departaments_list();
                $tmparr=array();
                foreach ($listarr as $key=>$value) {
                    $departament=departament_info($value['id']);
                    $tmparr[]=array(0 => array('text' => $value['name'],'callback_data' => "/adm-selecteduser-options-newdepart-apply_".$cmdarr[1]."_".$value['id']));
                }
                $answer['reply_markup']=array("inline_keyboard"=>$tmparr);
                break;
            case '/adm-selecteduser-options-newdepart-apply':
                $splitcmdarr=split("_",$cmdarr[1]);
                $result=user_update($splitcmdarr[0],'departament',$splitcmdarr[1]);
                if ($result) {$answer['text']= "Смена подразделения выполнена успешно";} else {$answer['text']= "Смена подразделения НЕ выполнена";}
                set_last_msg($chat_id,"");
                break;
            case '/adm-selecteduser-options-changegroup':
                $answer['text']= 'Выберите группу доступа';
                //$answer['reply_markup']=$answer_buttons_set['adm-users-options'];
                $listarr=usergroup_list();
                $tmparr=array();
                foreach ($listarr as $key=>$value) {
                    $tmparr[]=array(0 => array('text' => $value['name'],'callback_data' => "/adm-selecteduser-options-changegroup-apply_".$cmdarr[1]."_".$value['id']));
                }
                $answer['reply_markup']=array("inline_keyboard"=>$tmparr);
                break;
            case '/adm-selecteduser-options-changegroup-apply':
                $splitcmdarr=split("_",$cmdarr[1]);
                $result=user_update($splitcmdarr[0],'group',$splitcmdarr[1]);
                if ($result) {$answer['text']= "Смена группы доступа выполнена успешно";} else {$answer['text']= "Смена группы доступа НЕ выполнена";}
                set_last_msg($chat_id,"");
                break;
            case '/adm-selecteduser-options-changeid':
                set_last_msg($chat_id,'/adm-selecteduser-options-changeid_'.$cmdarr[1]);
                $answer['text']= 'Введите новый ID (пользователь должен открыть диалог с ботом и получить идентификатор, набрав команду /start):';
                break;
            case '/adm-selecteduser-options-changeid-apply':
                $splitcmdarr=split("_",$cmdarr[1]);
                $result=user_update($splitcmdarr[0],'telegram_id',$splitcmdarr[1]);
                if ($result) {$answer['text']= "Идентификатор изменен на: ".$splitcmdarr[1];} else {$answer['text']= "Идентификатор НЕ изменен";}
                set_last_msg($chat_id,"");
                break;












            case '/Мои':
                //$row=$db->fetch_assoc($db->query("SELECT * FROM `users` WHERE `telegram_id`='".$chat_id."' LIMIT 1"));
                $answer['text']= get_tasklist(array('chat_id'=>$chat_id,'engineer'=>$chat_id));
                    $answer['reply_markup']=array(
                        "keyboard" => array(
                            array(array("text" =>  "/Я - исп."),array("text" =>  "/Я - автор")),
                            array(array("text" =>  "/Все Текущие"),array("text" =>  "/Все Будущие"))
                        ),
                        "resize_keyboard" => true
                    );
                break;
            case '/Все':
                //$row=$db->fetch_assoc($db->query("SELECT * FROM `users` WHERE `telegram_id`='".$chat_id."' LIMIT 1"));
                $answer['text']= get_tasklist(array('chat_id'=>$chat_id,'filter'=>' OR `id`>0 '));
                    $answer['reply_markup']=array(
                        "keyboard" => array(
                            array(array("text" =>  "/Я - исп."),array("text" =>  "/Я - автор")),
                            array(array("text" =>  "/Все Текущие"),array("text" =>  "/Все Будущие"))
                        ),
                        "resize_keyboard" => true
                    );
                break;
            case '/Все Текущие':
                $answer['text']= get_tasklist(array('chat_id'=>$chat_id,'filter'=>' OR `start_notify` BETWEEN STR_TO_DATE(\'0000-00-00 00:00:00\', \'%Y-%m-%d %H:%i:%s\') AND STR_TO_DATE(\''.date("Y-m-d H:i:s",time()).'\', \'%Y-%m-%d %H:%i:%s\') '));
                    $answer['reply_markup']=array(
                        "keyboard" => array(
                            array(array("text" =>  "/Я - исп."),array("text" =>  "/Я - автор")),
                            array(array("text" =>  "/Все Текущие"),array("text" =>  "/Все Будущие"))
                        ),
                        "resize_keyboard" => true
                    );
                break;
            case '/Все Будущие':
                //$row=$db->fetch_assoc($db->query("SELECT * FROM `users` WHERE `telegram_id`='".$chat_id."' LIMIT 1"));
                
                $answer['text']= get_tasklist(array('chat_id'=>$chat_id,'filter'=>' OR `start_notify` NOT BETWEEN STR_TO_DATE(\'0000-00-00 00:00:00\', \'%Y-%m-%d %H:%i:%s\') AND STR_TO_DATE(\''.date("Y-m-d H:i:s",time()).'\', \'%Y-%m-%d %H:%i:%s\') '));
                    $answer['reply_markup']=array(
                        "keyboard" => array(
                            array(array("text" =>  "/Я - исп."),array("text" =>  "/Я - автор")),
                            array(array("text" =>  "/Все Текущие"),array("text" =>  "/Все Будущие"))
                        ),
                        "resize_keyboard" => true
                    );
                break;
            case '/Я - исп.':
                //$row=$db->fetch_assoc($db->query("SELECT * FROM `users` WHERE `telegram_id`='".$chat_id."' LIMIT 1"));
                $user=userinfo_by_chatid($chat_id);
                $answer['text']= get_tasklist(array('chat_id'=>$chat_id,'filter'=>' OR `engineer`=\''.$user['id'].'\' '));
                    $answer['reply_markup']=array(
                        "keyboard" => array(
                            array(array("text" =>  "/Я - исп."),array("text" =>  "/Я - автор")),
                            array(array("text" =>  "/Все Текущие"),array("text" =>  "/Все Будущие"))
                        ),
                        "resize_keyboard" => true
                    );
                break;
            case '/Я - автор':
                $user=userinfo_by_chatid($chat_id);
                $answer['text']= get_tasklist(array('chat_id'=>$chat_id,'filter'=>' OR `author`=\''.$user['id'].'\' '));
                    $answer['reply_markup']=array(
                        "keyboard" => array(
                            array(array("text" =>  "/Я - исп."),array("text" =>  "/Я - автор")),
                            array(array("text" =>  "/Все Текущие"),array("text" =>  "/Все Будущие"))
                        ),
                        "resize_keyboard" => true
                    );
                break;
            case '/По катег.':
                $answer['text']= get_tasklist(array('chat_id'=>$chat_id));
                    $answer['reply_markup']=array(
                        "keyboard" => array(
                            array(array("text" =>  "/Я - исп."),array("text" =>  "/Я - автор")),
                            array(array("text" =>  "/Все Текущие"),array("text" =>  "/Все Будущие"))
                        ),
                        "resize_keyboard" => true
                    );
                break;
            case '/По исп.':
                $answer['text']= get_tasklist(array('chat_id'=>$chat_id,'by_eng'=>true));
                    $answer['reply_markup']=array(
                        "keyboard" => array(
                            array(array("text" =>  "/Я - исп."),array("text" =>  "/Я - автор")),
                            array(array("text" =>  "/Все Текущие"),array("text" =>  "/Все Будущие"))
                        ),
                        "resize_keyboard" => true
                    );
                break;
            case '/Мой отдел':
                $answer['text']= get_tasklist(array('chat_id'=>$chat_id,'my_dep'=>true));
                    $answer['reply_markup']=array(
                        "keyboard" => array(
                            array(array("text" =>  "/Я - исп."),array("text" =>  "/Я - автор")),
                            array(array("text" =>  "/Все Текущие"),array("text" =>  "/Все Будущие"))
                        ),
                        "resize_keyboard" => true
                    );
                break;
            

           default:
                $answer['text']= 'Команда '.$value['message']['text'].' не распознана. Проверьте правильность ввода.';
        }
    }    
        //$answer['reply_markup']=array_merge($answer2['reply_markup'],$answer['reply_markup']);
        //print_r($answer['reply_markup']);
        return $answer;
    }

    function get_tasklist($params /* $chatid, $sort=null, $sort2=null,$engineer=null, $searchall=null */) {
        $db=new DB();

        $user=userinfo_by_chatid($params['chat_id']);
		$querystr=" SELECT  *
							FROM    `tickets`
							WHERE   `id`<0
								".$params['filter']."

		";
		//echo($querystr);
		$query=$db->query($querystr);
		$i=0;
		while($row=$db->fetch_assoc($query)){
            if ($params['showclosed']) {
                $tmpqueryarr[]=$row;
                $task_obj=new Task($row['id']);
                $task_arr[$row['id']]=$task_obj->taskinfo;
                $i++;
            } else {
                if (!is_last_state($row['status'])) {
                    $tmpqueryarr[]=$row;
                    $task_obj=new Task($row['id']);
                    $task_arr[$row['id']]=$task_obj->taskinfo;
                    $i++;
                }
            }
		}

        $groupfilter1='cat_location';
        $groupfilter2='cat_object';

		if (isset($groupfilter1)) {
			foreach($task_arr as $key => $value) {
				if (isset($groupfilter2)) {
					$task_arr_new[$value[$groupfilter1]][$value[$groupfilter2]][$key]=$value;
				} else {
					$task_arr_new[$value[$groupfilter1]][$key]=$value;
				}
			}
			$task_arr=array();
			foreach($task_arr_new as $key => $value) {
				if (isset($groupfilter2)) {
					foreach($value as $key2 => $value2) {
						foreach($value2 as $key3 => $value3) {
							$task_arr[$key3]=$value3;
						}
					}
				} else {
					foreach($value as $key2 => $value2) {
						$task_arr[$key2]=$value2;
						//echo($key." ".$key2."<br/>");
					}
				}
			}
		}
        $taskarr2=$task_arr_new;
        
        foreach ($taskarr2 as $key1=>$value1) {                   
            $catinfo=loc_info($key1);
            $spisok .= "\r\n\r\n<b>".$catinfo['name']." = = = =</b>";               
            foreach ($value1 as $key2=>$value2) {
                $catinfo=cat_info($key2);
                $spisok .= "\r\n\t\t<b>".$catinfo['name']." - - - -</b>";               
                foreach ($value2 as $key3=>$value3) {                   
                    $task_obj=new Task($key3);
                    $task=$task_obj->taskinfo;
                    
                    if ($task['author']==$user['id']) {$authflag='(автор)';} else {$authflag="";}
                    if ($task['engineer']==$user['id']) {$engflag='(исп-ль)';} else {$engflag="";}
                    if ($task['urgent']==1) {$urgentflag=" <b>!!!ПОЖАР!!!</b>";} else {$urgentflag="";}
                    if ($task['deadline']>0) {$deadlineflag=" <b>!ДЕДЛАЙН! </b>".substr($task['deadline'],11,5).' '.substr($task['deadline'],8,2).'.'.substr($task['deadline'],5,2).'.'.substr($task['deadline'],0,4);} else {$deadlineflag="";}
                    if (strlen($task['title'])>40) {$substr=mb_substr($task['title'], 0, 40, 'UTF-8')."...";} else {$substr=$task['title'];}
                    
                    $spisok .= "\r\n\t\t\t\t/".$task['id'].$urgentflag.$deadlineflag.$authflag.$engflag."\r\n\t\t\t\t\t\t".$substr."";
                    //$spisok .= "\r\n\t\t\t\t/".$task['id'];
                }
            }
        }
        $spisok .= "\r\nЧисло заявок: ".$i;
        
    
        
        return $spisok;
    }
    
    function get_last_msg($icq){
        $db=new DB();
        //$row=$db->fetch_assoc($db->query("SELECT `lastmsg` FROM `fbd_user_info` WHERE `icq`='$icq' LIMIT 1"));
        $user=userinfo_by_chatid($icq);
        $row=$db->fetch_assoc($db->query("SELECT `last_message_text` FROM `user_flags` WHERE `user_id`='".$user['id']."' LIMIT 1"));
        //print_r($row);
        if ($row) {
            return $row['last_message_text'];
        } else {
            return null;
        }
    }
    function set_last_msg($icq, $msg=null){
        $db=new DB();
        $user=userinfo_by_chatid($icq);
        //$row=$db->query("UPDATE `user_info` SET `lastmsg` = '$msg' WHERE `icq` = '$icq'");
	    $query = $db->query("
            INSERT INTO `user_flags` (
                `id`,
                `user_id`,
                `last_message_text`
            ) VALUES (
                NULL,
                '".$user['id']."',
                '".$msg."'
            )
            ON DUPLICATE KEY UPDATE
                `last_message_text`='".$msg."'
        ");
        
        return true;
    }
    function get_last_msgid($icq){
        $db=new DB();
        //$row=$db->fetch_assoc($db->query("SELECT `lastmsg` FROM `fbd_user_info` WHERE `icq`='$icq' LIMIT 1"));
        $user=userinfo_by_chatid($icq);
        $row=$db->fetch_assoc($db->query("SELECT `last_message_id` FROM `user_flags` WHERE `user_id`='".$user['id']."' LIMIT 1"));
        //print_r($row);
        if ($row) {
            return $row['last_message_id'];
        } else {
            return null;
        }
    }
    function set_last_msgid($icq, $msg=null){
        $db=new DB();
        $user=userinfo_by_chatid($icq);
        //$row=$db->query("UPDATE `user_info` SET `lastmsg` = '$msg' WHERE `icq` = '$icq'");
	    $query = $db->query("
            INSERT INTO `user_flags` (
                `id`,
                `user_id`,
                `last_message_id`
            ) VALUES (
                NULL,
                '".$user['id']."',
                '".$msg."'
            )
            ON DUPLICATE KEY UPDATE
                `last_message_id`='".$msg."'
        ");
        
        return true;
    }
    function get_last_telegramid(){
        $db=new DB();
        $row=$db->fetch_assoc($db->query("SELECT * FROM `fbd_config` WHERE `configid`='12' LIMIT 1"));
        return $row['value'];
    }
    function set_last_telegramid($lastid=0){
        $db=new DB();
        $row=$db->query("UPDATE `fbd_config` SET `value` = '$lastid' WHERE `fbd_config`.`configid` = 12");
        return true;
    }
    function get_chatid_list(){
        $db=new DB();
        $list['array']=array();
        $list['query']=$db->query("SELECT `icq` FROM `fbd_user_info`");
        while($list['row']=$db->fetch_assoc($list['query'])) { 
            if ($list['row']['icq'] and !in_array($list['row']['icq'],$list['array'])) $list['array'][]=$list['row']['icq'];
        }
        //print_r($list['array']);
        return $list['array'];
    }
    
    function get_locname($id){
        $db=new DB();
        $row=$db->fetch_assoc($db->query("SELECT `name` FROM `bd_custorg` WHERE `id`='$id' ORDER BY `id` DESC LIMIT 1"));
        return $row['name'];
    }
    function get_catname($id){
        $db=new DB();
        $row=$db->fetch_assoc($db->query("SELECT `name` FROM `bd_task_category` WHERE `categoryID`='$id' ORDER BY `categoryID` DESC LIMIT 1"));
        return $row['name'];
    }
    function get_engname($id){
        $db=new DB();
        $row=$db->fetch_assoc($db->query("SELECT `name` FROM `fbd_user` WHERE `userid`='$id' ORDER BY `userid` DESC LIMIT 1"));
        return $row['name'];
    }
    


    














?>