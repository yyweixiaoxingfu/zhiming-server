<?php

namespace app\message\process;

use app\message\model\MessageActionModel;
use app\system\library\XML2Array;
use app\system\model\ServiceModel;
use app\system\processes\BaseProcess;
use think\facade\Env;

class MessageRegisterProcess extends BaseProcess
{
    public $module;
    public $file;
    public $path;
    public $tip  = 'msgreg';
    public $type = 'MessageRegister';


    /**
     * 设置路径
     * @set path
     */
    public function setPath()
    {
        $this->path = Env::get('app_path') . $this->getModule();//Module::getModulePath($this->getModule()).'/Policies/';
        return $this;
    }

    public function getFileContent()
    {
        $result = [];
        $file   = $this->getFile();
        if (!empty($file)) {
            $tmps = XML2Array::createArray(file_get_contents($file));

            if (is_array($tmps)) {
                $result[key($tmps)]            = $tmps[key($tmps)];
                $result[key($tmps)]['version'] = md5_file($file);
            }
        }
        return $result;
    }

    /**
     * 启用模块构建集合
     * @param $module
     */
    public function handle()
    {
        $collection = $this->getFileContent();
        $ServicesRepository = new MessageActionModel();
        if (isset($collection['actions']['action']) && is_array($collection['actions']['action']) && isset($collection['actions']['version'])) {
            $schema            = [];
            $schema['key']     = $this->getModule() . '_msgreg';
            $schema['module']  = $this->getModule();
            $schema['value']   = json_encode($collection['actions']['action']);
            $schema['version'] = $collection['actions']['version'];
            //判断数据表版本
            $fileVersion = $this->version($schema, 'version', 'msgreg');
            //dd($collection);
            if ((isset($fileVersion) && ($fileVersion->version != $schema['version'])) || empty($fileVersion)) {
                //格式化数组 保持统一多维
                if (!isset($collection['actions']['action'][0])) {
                    $collectionServices                  = $collection['actions']['action'];
                    $collection['actions']['action']   = array();
                    $collection['actions']['action'][] = $collectionServices;
                }
                foreach ($collection['actions']['action'] as $item) {
                    if (isset($item['@attributes']['id'])) {
                        $sKey = $item['@attributes']['id'];
                        $service   = array(
                            'module' => $this->getModule(),
                            'action' => $sKey,
                            'desc' => $item['desc'] ?? '',
                            'title' => $item['title'] ?? '',
                            'content' => $item['content'] ?? '',
                            'keywords_desc' => $item['keywords_desc'] ?? '',
                            'is_queue' => $item['is_queue'] ?? 0,
                            'sms_enabled' => $item['sms_enabled'] ?? 0,
                            'email_enabled' => $item['email_enabled'] ?? 0,
                            'info_enabled' => $item['info_enabled'] ?? 0,
                            'notice_enabled' => $item['notice_enabled'] ?? 0,
                            'version' => $collection['actions']['version'],
                        );
                        $rsService = $ServicesRepository->where(['action' => $sKey])->find();
                        if (!$rsService) {
                            $ServicesRepository->create($service);
                        } else {
                            //只有当前模块才允许更新
                            if ($rsService['module'] == $service['module']) {
                                $ServicesRepository->update($service, ['id' => $rsService->id]);
                            }
                        }
                    }
                }
                $this->version($schema, 'updateOrCreate', 'msgreg');
                //去除脏数据
                $ServicesRepository->clear($this->getModule(), $schema['version']);
            }
        } else {
            //去除脏数据
            $ServicesRepository->clear($this->getModule());
            $schema['key'] = $this->getModule() . '_msgreg';
            $this->version($schema, 'delete', 'msgreg');
        }
        if (method_exists($ServicesRepository, 'cached')) {
            $ServicesRepository->cached();
        }
    }

    public function __destruct()
    {
        parent::__destruct();
        $ServicesRepository = new MessageActionModel();
        $ServicesRepository->whereNotIn('module', $this->moduleAll)->delete();
    }

}