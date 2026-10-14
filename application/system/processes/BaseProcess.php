<?php

namespace app\system\processes;

use app\system\library\XML2Array;
use app\system\model\VersionModel;

/**
 * Class CollectionRepositoryEloquent
 * @package namespace Ecross\Foundation\Repositories\Backend;
 */
class BaseProcess
{
    public $module,$command,$path,$moduleAll,$type,$tip;

    /**
     * 设置模块
     * @set module
     */
    public function setModule($module=false){
        $this->module = $module;
        $this->moduleAll[] = $module;
        return $this;
    }

    public function getModule(){
        return $this->module;
    }

    /*
     *  @param obj command
     * 设置命令行输出对象
    */
    public function setCommand($command=false){
        $this->command=$command;
        return $this;
    }

    public function getCommand(){
        return $this->command;
    }

    /**
     * 设置路径
     * @set path
     */
    public function setPath(){
        return $this;
    }

    public function getPath(){
        return $this->path;
    }

    /**
     * Get the  file
     *
     * @return array
     */
    public function getFile(){
        if(!empty($this->getPath()) && is_dir($this->getPath())){
            $fileName=$this->type.'.xml';
            $file=$this->getPath().'/'.$fileName;
            if(file_exists($file)){
                return $file;
            }
        }
        return '';
    }
    /**
     * Get the  fileValue
     *
     * @return array
     */
    public function getFileContent()
    {
        $result = [];
        $file=$this->getFile();

        if(!empty($file)){
            $tmps = XML2Array::createArray(file_get_contents($file));
            if(is_array($tmps)){
                $result[key($tmps)]['content']=$tmps[key($tmps)];
                $result[key($tmps)]['version']=md5_file($file);
            }
        }
        return $result;
    }

    /*
     *启用模块构建权限菜单
     * @param $module
     */
    public function handle(){}

    /*
     * 禁用模块删除权限菜单
     * old->disableingModuleMenu
     * @param $module
     */
    public function destory($module){}

    /**
     * @param array $cache
     * @return mixed
     */
    public function getCachePrefix(){}

    /**
     *创建权限缓存
     * old->setMenuCache
     */
    public function cached(){}

    /**
     * 记录版本
     * @param $schema
     * @param string $type
     * @return mixed
     */
    public function version($schema,$operate='updateOrCreate',$type){
        $version=new VersionModel();
        if($operate=='updateOrCreate'){
            $schema['type']=$type;
            $version->updateOrCreate(['key'=>$schema['key'],'type'=>$type],$schema);
        }elseif($operate=='delete'){
            $version->where(['key'=>$schema['key'],'type'=>$type])->delete();
        }elseif($operate=='version'){
            return $version->getVersion($schema['key'],$type,$this->getModule());
        }
    }

    public function __destruct()
    {
        $version=new VersionModel();
        $tip = $this->tip;
        $version->whereNotIn('module',$this->moduleAll)->where('type',$tip)->delete();
    }
}
