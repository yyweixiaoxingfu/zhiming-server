<?php


namespace app\message\job;


use app\message\library\MessageServer;
use think\queue\Job;

class SendMessage
{
    public function fire(Job $job,$data)
    {
        // 有些消息在到达消费者时,可能已经不再需要执行了
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
        if(!$isJobStillNeedToBeDone){
            $job->delete();
            return;
        }

        //print("<info>job Data is: ".var_export($data,true)."</info> \n");
        $isJobDone = $this->doSend($data);
        if ($isJobDone) {
            // 如果任务执行成功， 记得删除任务
            $job->delete();
            //print("<info>Job has been done and deleted at". date('Y-m-d H:i:s') ."</info>\n");
        }else{
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                //print("<warn>Hello Job has been retried more than 3 times!"."</warn>\n");
                $job->delete();
                return ;
            }
            $job->release(10); //$delay为延迟时间，表示该任务延迟10秒后再执行
        }
    }

    private function checkDatabaseToSeeIfJobNeedToBeDone($data){
        return true;
    }

    private function doSend($data)
    {
        try{
            $service = new MessageServer();
            $service->doSend($data['driver'],$data['target'],$data['template'],$data['tplparams'],$data['options']??[]);
        }catch (\Exception $e){
            print("<error>".$e->getMessage()."</error>\n");
            return false;
        }
        return true;
    }

}