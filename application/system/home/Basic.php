<?php
namespace app\system\home;
use app\index\controller\Home;
use app\system\model\RegionModel;
use think\Image;
use app\admin\model\Attachment as AttachmentModel;

/**
 * @title 系统级别接口
 */
class Basic extends Home
{
    /**
     * @title 获取配置
     * @author bh
     * @url /system/basic/conf
     * @method POST
     */
    public function conf(){
        $basicConfig=[];
        foreach(servicelist('frontend.getBasicConfig') as $object) {
            if(!is_object($object)) continue;
            if (method_exists($object, 'handle') ) {
                $object->handle($basicConfig);
            }
        }
        return returnData(1,'操作成功', $basicConfig);
    }

    /**
     * @title 获取地区联动
     * @author bh
     * @url /system/basic/getRegion
     * @method POST
     * @header name:Authorization require:0 default:1455523e6d407a3c desc:token
     * @param name:region type:string require:0 default: desc:上级地区编号
     */
    public function getRegion()
    {
        $post_data = SafeFilter(input('post.'));
        $info = RegionModel::getRegion($post_data,$msg );
        if (!$info)  return returnData(0,$msg ?? '操作失败');
        return returnData(1,'操作成功',$info);
    }

    /**
     * @title 根据code解析地区，并获取兄弟节点
     * @param name:region type:string require:1 default: desc:地区编号
     * @author bh
     * @url /system/basic/modifyRegion
     * @method POST
     * @header name:Authorization require:0 default:1455523e6d407a3c desc:token
     */
    public function modifyRegion()
    {
        $post_data = SafeFilter(input('post.'));
        $result = $this->validate($post_data,'RegionValidate.modify');
        if(true !== $result) {
            return returnData(0,$result);
        }
        $code = $post_data['region'];

        $info = RegionModel::modifyRegion($code);
        if (!$info)  return returnData(0,$msg ?? '操作失败');
        return returnData(1,'操作成功',$info);
    }

    /**
     * @title 单图上传
     * @author zxn
     * @url /system/basic/uploadImage
     * @method POST
     * @param name:img type:file require:1 default: desc:上传字段
     */
    public function uploadImage()
    {
        if ($this->request->isPost()) {
            $file = $this->request->file('img');
            if (empty($file)) {
                return json($this->uploadErr('请选择要上传的图片'));
            }
            $res = $this->uploadCommon($file,'images');
            return json($res);
        }
    }

    /**
     * @title 单文件上传
     * @author zxn
     * @url /system/basic/uploadFile
     * @method POST
     * @param name:file type:file require:1 default: desc:上传字段
     */
    public function uploadFile()
    {
        if ($this->request->isPost()) {
            $file = $this->request->file('file');
            if (empty($file)) {
                return json($this->uploadErr('请选择要上传的文件'));
            }
            $res = $this->uploadCommon($file,'files');
            return json($res);
        }
    }

    /**
     * @title 多图上传
     * @author zxn
     * @url /system/basic/uploadImageBatch
     * @method POST
     * @param name:imgs type:array require:1 default: desc:上传字段每项类型是file
     */
    public function uploadImageBatch()
    {
        if ($this->request->isPost()) {
            $files = $this->request->file('imgs');
            if (empty($files)) {
                return json($this->uploadErr('请选择要上传的图片'));
            }
            $res = [];
            foreach ($files as $file){
                $sdf = $this->uploadCommon($file,'images');
                if ($sdf['code']!=1){
                    return json($res);
                }
                $res[] = [
                    'id' => $sdf['id'],
                    'path' => $sdf['path'],
                ];
            }
            return returnData(1,'上传成功',$res);
        }
    }

    /**
     * @title 多文件上传
     * @author zxn
     * @url /system/basic/uploadFileBatch
     * @method POST
     * @param name:files type:array require:1 default: desc:上传字段每项类型是file
     */
    public function uploadFileBatch()
    {
        if ($this->request->isPost()) {
            $files = $this->request->file('files');
            if (empty($files)) {
                return json($this->uploadErr('请选择要上传的文件'));
            }
            $res = [];
            foreach ($files as $file){
                $sdf = $this->uploadCommon($file,'files');
                if ($sdf['code']!=1){
                    return json($res);
                }
                $res[] = [
                    'id' => $sdf['id'],
                    'path' => $sdf['path'],
                ];
            }
            return returnData(1,'上传成功',$res);
        }
    }

    protected function uploadCommon($file,$dir = '')
    {
        // 附件大小限制
        $size_limit = $dir == 'images' ? config('upload_image_size') : config('upload_file_size');
        $size_limit = $size_limit * 1024;
        // 附件类型限制
        $ext_limit = $dir == 'images' ? config('upload_image_ext') : config('upload_file_ext');
        $ext_limit = $ext_limit != '' ? parse_attr($ext_limit) : '';
        // 缩略图参数

        if ($file_exists = AttachmentModel::get(['md5' => $file->hash('md5')])) {
            if ($file_exists['driver'] == 'local') {
                $file_path = PUBLIC_PATH . $file_exists['path'];
            } else {
                $file_path = $file_exists['path'];
            }
            $path = 'http://' . $_SERVER['HTTP_HOST'] . $file_path;
            $path = str_replace("uploads//", "uploads/", $path);;
            // 附件已存在
            return $this->uploadSucc($path, $file_exists['id']);
        }
        // 判断附件大小是否超过限制
        if ($size_limit > 0 && ($file->getInfo('size') > $size_limit)) {
            return $this->uploadErr('附件过大');
        }

        $file_name = $file->getInfo('name');
        $file_ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
        $error_msg = '';
        if ($ext_limit == '') {
            $error_msg = '获取文件信息失败！';
        }
        if ($file->getMime() == 'text/x-php' || $file->getMime() == 'text/html') {
            $error_msg = '禁止上传非法文件！';
        }
        if (preg_grep("/php/i", $ext_limit)) {
            $error_msg = '禁止上传非法文件！';
        }
        if (!preg_grep("/$file_ext/i", $ext_limit)) {
            $error_msg = '附件类型不正确！';
        }

        if ($error_msg != '') {
            // 上传错误
            return $this->uploadErr( $error_msg);
        }

        $info = $file->move(config('upload_path') . DIRECTORY_SEPARATOR . $dir);
        if ($info) {
            // 图片宽度
            $img_width = '';
            // 图片高度
            $img_height = '';
            if ($dir == 'images') {
                $img = Image::open($info);
                $img_width = $img->width();
                $img_height = $img->height();
            }
            // 获取附件信息
            $file_info = [
                'name' => $file->getInfo('name'),
                'mime' => $file->getInfo('type'),
                'path' => 'uploads/' . $dir . '/' . str_replace('\\', '/', $info->getSaveName()),
                'ext' => $info->getExtension(),
                'size' => $info->getSize(),
                'md5' => $info->hash('md5'),
                'sha1' => $info->hash('sha1'),
                'width' => $img_width,
                'height' => $img_height,
            ];
            // 写入数据库
            if ($file_add = AttachmentModel::create($file_info)) {
                $file_path = PUBLIC_PATH . $file_info['path'];
                $path = 'http://' . $_SERVER['HTTP_HOST'] . $file_path;
                $path = str_replace("uploads//", "uploads/", $path);;
                return $this->uploadSucc($path, $file_add['id']);
            } else {
                return $this->uploadErr('上传失败');
            }
        } else {
            return $this->uploadErr('上传失败');
        }
    }

    private function uploadSucc($file_path = '', $file_id = '')
    {
        return array(
            'code' => 1,
            'msg' => '上传成功',
            'id' => $file_id,
            'path' => $file_path
        );

    }

    private function uploadErr($msg = '')
    {
        return array(
            'code' => 0,
            'info' => $msg
        );
    }

}
