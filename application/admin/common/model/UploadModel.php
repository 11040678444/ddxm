<?php

// +----------------------------------------------------------------------
// | 上传图片
// +----------------------------------------------------------------------
namespace app\admin\common\model;

use think\Model;
use think\Db;

use Qiniu\Auth as Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Qiniu\Config;

class UploadModel extends Model
{

    /***
     * 上传图片
     * @param $file
     * @return array
     * @throws \Exception
     */
    public function upload($file){
        // 要上传图片的本地路径
        $filePath = $file->getRealPath();
        $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
        // 上传到七牛后保存的文件名
        $key =substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = config('qiniu')['accesskey'];
        $secretKey = config('qiniu')['secretkey'];
        require_once APP_PATH . '/../vendor/qiniu/autoload.php';
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 要上传的空间
        $bucket = "ddxm-item";
        $domain = "picture.ddxm661.com";
        $token = $auth->uploadToken($bucket);

        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err !== null) {
            return ["code"=>1,"msg"=>$err,"data"=>""];
        } else {
            //返回图片的完整URL
            return  ["code"=>0,"msg"=>"上传完成","data"=>$ret];
        }
    }

    /***
     * 上传图片
     * @param $file
     * @return \think\response\Json
     * @throws \Exception
     */
    public function upload1($file){
        // 要上传图片的本地路径
        $filePath = $file;
        $ext = pathinfo($file)['extension'];  //后缀

        // 上传到七牛后保存的文件名
        $key =substr(md5($file),0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;

        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = config('qiniu')['accesskey'];
        $secretKey = config('qiniu')['secretkey'];
        require_once APP_PATH . '/../vendor/qiniu/autoload.php';
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 要上传的空间
        $bucket = "ddxm-item";
        $domain = "picture.ddxm661.com";
        $token = $auth->uploadToken($bucket);

        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err !== null) {
            return ["code"=>1,"msg"=>$err,"data"=>""];
        } else {
            //返回图片的完整URL
            return  ["code"=>0,"msg"=>"上传完成","data"=>$ret];
        }
    }

    /***
     * 删除图片
     * @param $delFileName
     * @return array
     */
    public function delImg($delFileName)
    {
        if( $delFileName ==null){
            return ['code'=>0,'msg'=>'参数不正确'];die;
        }
        require_once APP_PATH . '/../vendor/qiniu/autoload.php';
        $auth = new Auth("ChbjC0NsNlFawXdmV9GXZtaoU5rfq5ZS9d919Z1n","Fnd1ud7q77V7qlLlW0uqFna24RD2B-AI_2Jrd0IH");
        $config = new \Qiniu\Config();
        $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
        $res = $bucketManager->delete("ddxm-item",$delFileName);
        if (is_null($res)) {
            return ["code"=>1,"msg"=>'删除成功',"data"=>""];
        }else{
            return ["code"=>0,"msg"=>'删除失败,网络错误',"data"=>""];
        }
    }

    /***
     * 上传视频
     * @param $file
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function uploadVideo($vname,$key,$filePath){
        //获取token值
        $accessKey = config('qiniu')['accesskey'];
        $secretKey = config('qiniu')['secretkey'];
        // 初始化签权对象
        require_once APP_PATH . '/../vendor/qiniu/autoload.php';
        $auth = new Auth($accessKey, $secretKey);
        $bucket = "ddxm-item";
        // 生成上传Token
        $token = $auth->uploadToken($bucket);
        $uploadMgr = new UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);

        // 获取视频的时长
        // 第一步先获取到到的是关于视频所有信息的json字符串
        $shichang = file_get_contents('http://picture.ddxm661.com/'.$key.'?avinfo');
        // 第二部转化为对象
        $shi =json_decode($shichang,true);
        // 第三部从中取出视频的时长
        $chang = $shi['format']['duration'];
        // 获取封面
        $fengmian = 'http://picture.ddxm661.com/'.$key.'?vframe/jpg/offset/1';
        $ret['fengmian'] = $fengmian;
        $ret['video_time'] = $chang;
        if ($err !== null) {
            return ["code"=>1,"msg"=>$err,"data"=>""];
        } else {
            //返回图片的完整URL
            return  ["code"=>0,"msg"=>"上传完成","data"=>$ret];
        }
    }
}