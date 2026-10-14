<?php

namespace app\ai\service;

use app\ai\model\AiMoldsModel;
use app\ai\model\AiRoleModel;

/**
 * AI模型服务类
 * 处理AI模型选择和配置
 */
class AIModelService
{
    /**
     * 获取所有有效模型
     * @return array
     */
    public static function getValidModels()
    {
        return AiMoldsModel::where('isvalid', 1)->order('sequenceid asc,isimage asc,id asc')->select();
    }

    /**
     * 获取默认模型
     * @return array|false
     */
    public static function getDefaultModel()
    {
        return AiMoldsModel::where('isvalid', 1)->order('sequenceid asc')->find();
    }

    /**
     * 构建模型项
     * @param array $model 模型数据
     * @param int $isimage 图片类型
     * @return array|null
     */
    public static function buildModelItem($model, $isimage)
    {
        $title = $model['modelname'];

        switch ($isimage) {
            case 0:
                break;
            case 1:
                $title = '画图-' . $model['modelname'];
                break;
            case 2:
                $title = '改图-' . $model['modelname'];
                break;
            case 4:
                $title = '识图-' . $model['modelname'];
                break;
            default:
                return null;
        }

        $priceText = empty($model['modelprice']) ? '免费' : $model['modelprice'] . '积分';

        return [
            'title' => $title,
            'modelid' => $model['id'],
            'modelvalue' => $model['modelvalue'],
            'modelisimage' => $isimage,
            'modelprice' => $model['modelprice'],
            'priceText' => $priceText
        ];
    }

    /**
     * 处理角色数据
     * @return string
     */
    public static function processRoleData()
    {
        $role_data = AiRoleModel::where('isvalid', 1)->order('id asc')->select();
        $roleArray = [['title' => '默认', 'rolevalue' => '']];

        foreach ($role_data as $row) {
            $roleArray[] = [
                'title' => $row['rolename'],
                'rolevalue' => $row['rolevalue']
            ];
        }

        return json_encode($roleArray, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取模型信息
     * @param int $modelId 模型ID
     * @return array|false
     */
    public static function getModelById($modelId)
    {
        return AiMoldsModel::where('id', $modelId)->find();
    }
}
