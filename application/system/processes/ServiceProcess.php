<?php

namespace app\system\processes;



use app\system\model\ServiceModel;
use app\system\library\XML2Array;
use think\facade\Env;

class ServiceProcess extends BaseProcess{

    /**
     * @var module
     */
    public $module;

    /**
     * @var FIle $file
     */
    public  $file;
    public $tip='services';
    public $type='Services';

    /**
     * 设置解析XML
     * @var str
     */
    public $path;


    /**
     * 设置路径
     * @set path
     */
    public function setPath(){
        $this->path = Env::get('app_path') . $this->getModule();//Module::getModulePath($this->getModule()).'/Policies/';
        return $this;
    }
    public function getOrder(){
        return 30;
    }

    public function getMaxSort($key){
        $rsService = ServiceModel::where(['key'=>$key])->order('sort desc')->field('sort')->find();
        $max = isset($rsService['sort']) ? (int)$rsService['sort']+1 : 9999;
        return $max > 65535 ? 65535 : $max;
    }
    public function getFileContent(){
        $result = [];
        $file=$this->getFile();
        if(!empty($file)){
            $tmps = XML2Array::createArray(file_get_contents($file));

            if(is_array($tmps)){
                $result[key($tmps)]=$tmps[key($tmps)];
                $result[key($tmps)]['version']=md5_file($file);
            }
        }
        return $result;
    }
    /*
     *启用模块构建集合
     * @param $module
     */
    public function handle(){
        //$this->command->info("\n................ maintain services");
        $collection = $this->getFileContent();
        $ServicesRepository=new ServiceModel();
        if(isset($collection['services']['service']) && is_array($collection['services']['service']) && isset($collection['services']['version']) )
        {
            $schema = [];
            $schema['key'] = $this->getModule().'_services';
            $schema['module'] = $this->getModule();
            $schema['value'] = json_encode($collection['services']['service']);
            $schema['version']   = $collection['services']['version'];
            //判断数据表版本
            $fileVersion = $this->version($schema, 'version','services');
            //dd($collection);
            if ( (isset($fileVersion) && ($fileVersion->version != $schema['version']))|| empty($fileVersion)){
                //格式化数组 保持统一多维
                if(!isset($collection['services']['service'][0])){
                    $collectionServices=$collection['services']['service'];
                    $collection['services']['service']=array();
                    $collection['services']['service'][]=$collectionServices;
                }
                foreach($collection['services']['service'] as $aService){

                    if(isset($aService['@attributes']['id']) && isset($aService['class'])){

                        $sKey=$aService['@attributes']['id'];

                        $sName=isset($aService['@attributes']['optname'])?$aService['@attributes']['optname']:'';

                        //格式化数组 保持统一多维
                        if(!isset($aService['class'][0])){
                            $arrayClass=$aService['class'];
                            $aService['class']=array();
                            $aService['class'][]=$arrayClass;
                        }
                        foreach( (array)$aService['class'] as $class){
                            $service=array(
                                'module'=>$this->getModule(),
                                'key'=>$sKey,
                                'namespace'=>isset($class['@value'])?$class['@value']:$class,
                                'name'=>$sName,
                                'addon'=>isset($class['@attributes'])?json_encode($class['@attributes']):null,
                                'sort'=>isset($class['@attributes']['sort'])?$class['@attributes']['sort']:$this->getMaxSort($sKey),
                                'version'=>$collection['services']['version'],
                            );
                            $rsService = $ServicesRepository->where(['namespace'=>$service['namespace'],'key'=>$service['key']])->find();
                            $outString = $service['key'].':'.$service['namespace'];
                            $this->command->output()->writeln($outString);
                            if(!$rsService){
                                $ServicesRepository->create($service);
                            }
                            else{
                                //只有当前模块才允许更新
                                if($rsService['module']==$service['module']){
                                    $ServicesRepository->update($service,['id'=>$rsService->id]);
                                }

                            }
                        }  //endforeach $aService['class']
                    }
                }   //endforeach $collection['services']['service']
                //更新shcema信息

                $this->version($schema, 'updateOrCreate','services');
                //去除脏数据
                $ServicesRepository->clear($this->getModule(),$schema['version']);
            }

        }else{
            //去除脏数据
            $ServicesRepository->clear($this->getModule());
            $schema['key'] = $this->getModule().'_services';
            $this->version($schema, 'delete','services');
        }
        if(method_exists($ServicesRepository,'cached')){
            $ServicesRepository->cached();
        }
    }

    public function __destruct()
    {
        parent::__destruct();
        $ServicesRepository=new ServiceModel();
        $ServicesRepository->whereNotIn('module',$this->moduleAll)->delete();
    }
}
