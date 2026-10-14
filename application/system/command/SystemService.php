<?php

namespace app\system\command;

use app\system\processes\ServiceProcess;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Env;

class SystemService extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('systemservice')->setDescription('sync module service');
        // 设置参数
        
    }

    protected function execute(Input $input, Output $output)
    {
    	// 指令输出
    	$output->writeln('systemservice begin');
        $modulesList = getModulesCatalog(Env::get('app_path'));
        $serviceClass=ServiceProcess::class;
        $serviceInstance = new $serviceClass();

        foreach ($modulesList as $module){
            if ($module == 'install') continue;
            $output->writeln($module . ' services');
            $serviceInstance->setModule($module)->setCommand($this)->setPath()->handle();
        }

        $output->writeln('systemservice end');
    }

    public function output(){
        return $this->output;
    }
}
