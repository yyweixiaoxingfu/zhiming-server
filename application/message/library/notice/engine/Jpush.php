<?php

namespace app\message\library\notice\engine;

use JPush\Client;
use think\Log;

class Jpush extends Server
{
    public function send($registrationIds,$title,$message,$options){
        $config = $this->config;
        $this->app_key = $config['app_key'];
        $this->master_secret = $config['app_secret'];
        $this->client = new Client($this->app_key,$this->master_secret);
        try{
            $push = $this->client->push();
            $response = $push
                ->addRegistrationId($registrationIds)
                ->setPlatform('all')
                ->setNotificationAlert($message)
                ->iosNotification($message, [
                    'sound' => 'default',
                    'content-available' => true,
                    'badge' => '+1',
                    'extras' => [
                        'msg_type' => $options['type'] ?? 0
                    ]
                ])
                ->androidNotification($message,[
                    'extras' => [
                        'msg_type' => $options['type'] ?? 0
                    ]
                ])
                ->message($message, [
                    'title' => $title,
                    'content_type' => 'text',
                    'extras' => [
                        'msg_type' => $options['type'] ?? 0
                    ]
                ])
                ->send();
            $this->logData = $response;
        }catch (\Exception $e){
            $this->error = $e->getMessage();
            return false;
        }
        return true;
    }
}
