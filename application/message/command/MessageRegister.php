<?php


namespace app\message\command;


use app\message\process\MessageRegisterProcess;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Env;

class MessageRegister extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('messageregister')->setDescription('register module message');
        // 设置参数

    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $output->writeln('message register begin');
        $modulesList = getModulesCatalog(Env::get('app_path'));
        $service = new MessageRegisterProcess();
        foreach ($modulesList as $module){
            if ($module == 'install') continue;
            $output->writeln($module . ' actions');
            $service->setModule($module)->setCommand($this)->setPath()->handle();
        }
        $output->writeln('message register end');
    }

}