<?php

namespace app\finance\library;

use Closure;
use think\Collection;
use think\Exception;

class CashAppManager
{
    /**
     * @var
     */
    protected $app;

    /**
     * @var array
     */
    protected $disks = [];

    /**
     * @var array
     */
    protected $customCreators = [];

    /**
     * cashAppManager constructor.
     * @param $app
     */
    public function __construct()
    {
        $this->app = app();
        $this->init();
    }

    private function init(){
        $allPayApp = config('payment.cashApp');
        foreach ($allPayApp as $key => $payApp){
            $callback = function ($app,$config)use($payApp){
                return new $payApp($app,$config);
            };
            $this->extend($key,$callback);
        }

    }

    /**
     * @param null $key
     * @return bool|mixed
     */
    public function drive($key = null)
    {
        return $this->disk($key);
    }

    /**
     * @param null $key
     * @return bool|mixed
     */
    public function disk($key = null)
    {
        //$key = $key ?: $this->getDefaultDriver();

        return $this->disks[$key] = $this->get($key);
    }

    /**
     * @param $key
     * @return bool|mixed
     */
    protected function get($key)
    {
        return isset($this->disks[$key]) ? $this->disks[$key] : $this->resolve($key);
    }

    /**
     * @param $key
     * @return bool
     */
    protected function resolve($key)
    {
        $config = $this->getConfig($key);
        if (isset($this->customCreators[$key])) {
            return $this->callCustomCreator($key,$config);
        }
        $driverMethod = 'create'.ucfirst($key).'Driver';
        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($key);
        } else {
            throw new Exception("Driver [{$key}] is not supported.");
        }
    }

    /**
     * @param $key
     * @param array $config
     * @return bool
     */
    protected function callCustomCreator($key,array $config=[])
    {
        $driver = $this->customCreators[$key]($this->app, $config);

        if ($driver instanceof CashInterface) {
            return $driver;
        }
        return false;
    }

    /**
     * @param $key
     * @return array
     */
    protected function getConfig($key,$tenant_id=null)
    {
        return [];
        return $this->getConf($key,$tenant_id) ?: [];
    }

    /**
     * @param $driver
     * @param Closure $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    public function allPayAppKey(){
        return array_keys($this->customCreators);
    }
    public function findPayApp($pay_app_key){
        $allPayAppKey=$this->allPayAppKey();
        return in_array($pay_app_key,$allPayAppKey) ? true : false;
    }
    public function getPayApp($pay_app_key){
        $allPayApp=$this->allPayApp()->where('status',true)->where('key',$pay_app_key)->shift();
        return $allPayApp;
    }

    public function allPayApp(){
        $keys = array_keys($this->customCreators);
        $allPayApp = array_map(function ($creator,$key){
            $driver = $this->get($key);
            $config = $driver->getConfig();
            $pay=[
                'key'=>$driver->getKey(),
                'name'=>$driver->getName(),
                'type'=>$driver->getType(),
                'callback'=>$driver->getCallback(),
                'status'=>true,
                //'config'=> !empty($config),
                //'rate'=> $config['setting']['rate'] ?? 0,
                //'sort'=> $config['sort'] ?? 999,
            ];
            return $pay;
        },$this->customCreators,$keys);
        $allPayApp = array_combine($keys,$allPayApp);

        $allPay=Collection::make($allPayApp);
        return $allPay;
    }
    public function selectPayApp(){
        $allPayApp=$this->allPayApp()->where('status',true)->sortBy('sort');
        return $allPayApp;
    }
   /* public function selectBackendPayApp($tenant_id=null){
        $allPayApp = $this->selectPayApp($tenant_id)
            ->map(function ($payApp, $payAppKey) {
                if ($payApp['type'] == 'offline' || ($payApp['type'] == 'online' && $payApp['callback'] == true)) {
                    return $payApp;
                }
            })->filter();

        return $allPayApp;
    }
    public function selectFrontendPayApp($tenant_id=null,$targetType='online'){
        $allPayApp = $this->selectPayApp($tenant_id)
            ->map(function ($payApp, $payAppKey)  use ($targetType) {
                if($payApp['type'] == $targetType){
                    return $payApp;
                }
            })->filter();

        return $allPayApp;
    }
    public function findPayApp($pay_app_key,$tenant_id=null){
        $allPayApp=$this->allPayApp($tenant_id)->where('status',true)->where('key',$pay_app_key)->first();
        return $allPayApp;
    }

    function setConf($key,$conf,$tenant_id=null){
        return setConf('payment',$key,$conf,$tenant_id);
    }
    function getConf($key,$tenant_id=null){
        return getConf('payment',$key,$tenant_id);
    }
    public function selectPayAppType($tenant_id=null,$delivery=null){
        $type=[
            'offline'=>[
                'value'=>'offline',
                'label'=>trans('payment::system.payApp.type.offline'),
            ],
            'online'=>[
                'value'=>'online',
                'label'=>trans('payment::system.payApp.type.online'),
            ],

        ];
        if (!isset($tenant_id))  $tenant_id=getUser(null,'tenantId');
        $codEnable = (bool) getConf('payment', 'cod_enable', $tenant_id);

        if ($codEnable){
            $type['COD'] = [
                'value'=>'COD',
                'label'=>trans('payment::system.payApp.type.cod'),
            ];

            if (!is_null($delivery)){
                try{
                    if ($delivery instanceof Methods){
                        $deliveryInfo=$delivery;
                    }else{
                        $deliveryInfo=app(MethodsRepositoryEloquent::class)->findWhere(['id'=>$delivery])->first();
                    }

                    $deliveryInfo=$deliveryInfo->getAttributes();
                    if($deliveryInfo['has_cod']==false && isset($type['COD'])){
                        unset($type['COD']);
                    }
                }catch (\Exception $e){

                }

            }
        }


        return collect($type)->values();
    }*/
}