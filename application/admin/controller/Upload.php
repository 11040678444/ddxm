<?php
namespace app\admin\controller;

use app\admin\common\model\UploadModel;
use Qiniu\Auth as Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Qiniu\Config;
class Upload
{
    /***
     * 上传图片
     * @return \think\response\Json
     * @throws \Exception
     */
    public function upload(){
        header('Content-Type:application/json; charset=utf-8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Max-Age:86400'); // 允许访问的有效期
        header('Access-Control-Allow-Headers:*');
        header('Access-Control-Allow-Methods:OPTIONS, GET, POST, DELETE');
        $file = request()->file('file');
        $Upload = new UploadModel();
        $res = $Upload ->upload($file);
        return json($res);
    }

    /***
     * 删除图片
     * @return \think\response\Json
     */
    public function del(){
        header('Content-Type:application/json; charset=utf-8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Max-Age:86400'); // 允许访问的有效期
        header('Access-Control-Allow-Headers:*');
        header('Access-Control-Allow-Methods:OPTIONS, GET, POST, DELETE');
        $file = request()->file('file');
        $Upload = new UploadModel();
        $res = $Upload ->delImg($file);
        return json($res);
    }

    public function video(){
        $vname = $_FILES['file']['type'];
        //获取文件的名字
        $key = $_FILES['file']['name'];
        $filePath=$_FILES['file']['tmp_name'];
        $Upload = new UploadModel();
        $res = $Upload ->uploadVideo($vname,$key,$filePath);
        return json($res);
    }
}
