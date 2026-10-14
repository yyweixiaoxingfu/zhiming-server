<?php

namespace app\system\command;

use app\system\model\RegionModel;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;

class SystemRegion extends Command
{
    private $url = 'https://www.mca.gov.cn/mzsj/xzqh/2022/202201xzqh.html';//http://www.mca.gov.cn/article/sj/tjbz/a/2017/201703/201705051139.html';
    // 直辖市
    private $city = array('北京市', '天津市', '上海市', '重庆市');

    protected function configure()
    {
        // 指令配置
        $this->setName('systemregion')
            ->addArgument('url',Argument::OPTIONAL, "region url", $this->url)
            ->setDescription('sync region');
        // 设置参数
        
    }

    protected function execute(Input $input, Output $output)
    {
    	// 指令输出
    	$url =  $input->getArgument('url');
        $output->writeln("Get RegionString From Url ...");
        $str = $this->getString($url);
        $output->writeln("Get RegionArr ...");
        $list = $this->getList($str);
        $output->writeln("Get RegionStructuring ...");
        $struct = $this->structuring($list);
        $output->writeln("Build Region Into Database ...");
        $sql = $this->build($struct);
        $output->writeln("Build Region Success!");
    }
    /**
     * 构建sql
     */
    private function build($arr)
    {
        if (empty($arr)) {
            throw new \Exception('城市数据为空');
        }
        $data = array();
        $id = 0;
        foreach ($arr as $pro) {
            $pid = ++$id;
            if (in_array($pro['name'], $this->city)) {
                $pro['code'] = 'Z'.$pro['code'];
                $pro['name'] = str_replace('市','', $pro['name']);
            }
            $data[] = array(
                'id' => $pid,
                'pid' => 0,
                'path' =>',',
                'code' => $pro['code'],
                'name' => $pro['name'],
                'sort' => $pid,
            );
            foreach ($pro['sub'] as $city) {
                $pid2 = ++$id;
                $data[] = array(
                    'id' => $pid2,
                    'pid' => $pid,
                    'path' =>','.$pro['code'].',',
                    'code' => $city['code'],
                    'name' => $city['name'],
                    'sort' => $pid2,
                );
                foreach ($city['sub'] as $area) {
                    $pid3=++$id;
                    $data[] = array(
                        'id' => $pid3,
                        'pid' => $pid2,
                        'path' =>','.$pro['code'].','.$city['code'].',',
                        'code' => $area['code'],
                        'name' => $area['name'],
                        'sort' => $pid3,
                    );
                }
            }
        }

        $len = count($data);
        $regionRepository = app(RegionModel::class);
        Db::name('system_region')->delete(true);
        //$bar = $this->output->createProgressBar($len);

        foreach ($data as $k => $v) {
            $filter=['code'=>$v['code'],'pid'=>$v['pid']];
            $regionRepository->updateOrCreate($filter,$v);
            echo $v['code'];
            //$bar->advance();
        }
        echo PHP_EOL;
        //$bar->finish();
        //$regionRepository->cached();
        return true;
    }
    /**
     * 结构化城市数据
     * @param  array $list 列表数据
     * @return array       ['code'=>, 'name'=>'', 'sub'=> ['code', 'name'=>'', sub'=>[...]]]
     */
    private function structuring($list)
    {
        if (empty($list)) {
            throw new Exception('列表为空');
        }
        // 省份
        $province = array_filter($list, function($v) {
            return $v['code'] % 10000 === 0;
        });

        // 城市 & 地区
        foreach ($province as &$pro) {
            if (in_array($pro['name'], $this->city)) {
                $citys = array($pro);
                $area = array_filter($list, function ($v) use($pro) {
                    return !strncmp($v['code'], $pro['code'], 2)
                        && $v['code'] % 10000 !== 0;
                });
                $citys[0]['municipal'] = true;
                $citys[0]['sub'] = $area;
            } else {
                $citys = array_filter($list, function($v) use($pro) {
                    return !strncmp($v['code'], $pro['code'], 2)
                        && $v['code'] % 100 === 0
                        && $v['code'] % 10000 !== 0;
                });
                foreach ($citys as &$city) {
                    $area = array_filter($list, function($v) use($city) {
                        return !strncmp($v['code'], $city['code'], 4)
                            && $v['code'] % 100 !== 0;
                    });
                    $city['sub'] = $area;
                }
                // 判断省辖市
                $pro_str2 = substr($pro['code'],0,2);
                $pro_str = $pro_str2.'90';
                $zhi_area = array_filter($list, function($v) use($pro_str) {
                    return !strncmp($v['code'], $pro_str, 4)
                        && $v['code'] % 100 !== 0;
                });
                if(!empty($zhi_area)){
                    $citys[] = [
                        'code' => $pro_str.'00',
                        'name' => '省辖市',
                        'sub' => $zhi_area
                    ];
                }
                // 港澳台地区
                if(empty($citys)){
                    $citys = [[
                        'code' => $pro_str2.'01',
                        'name' => $pro['name'],
                        'sub' => [[
                            'code' => $pro_str2.'0101',
                            'name' => $pro['name'],
                        ]]
                    ]];
                }
            }
            $pro['sub'] = $citys;
        }
        return $province;
    }


    private function getString($url)
    {
        return file_get_contents($url);
    }

    private function getList($str)
    {
        $pattern = '/<td class=xl\d+>(\d+)<\/td>\n*\s*<td class=xl\d+>(.+?)<\/td>/';
        if (!preg_match_all($pattern, $str, $arr)) {
            throw new Exception('正则匹配失败');
        }

        // $result: ['code'=>110000, 'name'=>'北京市']
        $result = array();
        for ($i=0; $i < count($arr[1]); $i++) {
            $result[] = array('code' => $arr[1][$i], 'name' => $this->stripTags($arr[2][$i]));
        }
        return $result;
    }

    private function stripTags($str)
    {
        return trim(strtr($str, array(
            "<span style='mso-spacerun:yes'>" => '',
            "</span>" => '',
        )), '  ');
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }
    public function getUrl()
    {
        return $this->url;
    }

    public function output(){
        return $this->output;
    }
}
