<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use app\admin\model\help\HelpModel;
use think\Db;

use Qiniu\Auth as Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Qiniu\Config;

/**
	门店帮助管理
*/
class Help extends Adminbase
{
	public function test()
    {
        if(request()->isPost()){
            $file = request()->file('file');
            // 要上传图片的本地路径
            $filePath = $file->getRealPath();
            $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
 
            // 上传到七牛后保存的文件名
            $key =substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
            require_once APP_PATH . '/../vendor/qiniu/autoload.php';
            // 需要填写你的 Access Key 和 Secret Key
            $accessKey = "ChbjC0NsNlFawXdmV9GXZtaoU5rfq5ZS9d919Z1n";
            $secretKey = "Fnd1ud7q77V7qlLlW0uqFna24RD2B-AI_2Jrd0IH";
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
            // dump($ret);die;
            if ($err !== null) {
                return json(["code"=>1,"msg"=>$err,"data"=>""]);die;
            } else {
                //返回图片的完整URL
                return  json(["code"=>0,"msg"=>"上传完成","data"=>array('src'=>'http://picture.ddxm661.com/'.$ret['key'])]);die;
            }
        }
    }

	public function index(){
		if ($this->request->isAjax()) {
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $where = [];
            $where['delete_time'] = array('<=','0');
            $list = Db::name('help')->where($where)->page($page,$limit)->order('sort asc')->select();
            foreach ($list as $key => $value) {
            	$list[$key]['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
            }
            $total =  Db::name('help')->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		return $this->fetch();
	}

	/**
		添加、编辑
	*/
	public function add(){
		$data = $this ->request->param();
		if( !empty($data['id']) ){
			$list = Db::name('help')->where('id',$data['id'])->find();
			$this ->assign('list',$list);
			return $this->fetch();
		}else{
			return $this->fetch();
		}
	}


	/**
		添加编辑  的逻辑处理
	*/
	public function doPost(){
		$data = $this ->request ->post();
		unset($data['file']);
		if ( empty($data['title']) || empty($data['content']) ) {
			// $this ->error('请输入标题或内容');
			$result = array('code'=>'-1','msg'=>"请输入标题或内容");
			return $result;
		}
		if( empty($data['sort']) ){
			unset($data['sort']);
		}

		if ( empty($data['id']) ) {
			$data['user_id'] = session('admin_user_auth')['uid'];
			$data['create_time'] = time();
			$result = Db::name('help')->insert($data);
		}else{
			$data['update_time'] = time();
			$result = Db::name('help')->update($data);
		}


		if( $result ){
			// $this ->success('操作成功');
			$result = array('code'=>'1','msg'=>"操作成功");
			return $result;
		}else{
			// $this ->error('操作失败');
			$result =  array('code'=>'0','msg'=>"操作失败");
			return $result;
		}
	}

	/**
		删除
	*/
	public function del(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			$this ->error('缺少id');
		}

		$list = Db::name('help')->where('id',$data['id'])->find();
		// if( session('admin_user_auth')['uid'] != $list['user_id'] ){
		// 	$this ->error('只允许删除自己添加的信息');
		// }

		$start_time = strtotime(date('Y-m-d')." 00:00:00");
		$end_time = strtotime(date('Y-m-d')." 23:59:59");

		if ( $list['delete_time'] >0 ) {
			$this ->error('该信息已被删除');
		}

		// if ( $list['create_time'] <$start_time || $list['create_time']>$end_time  ) {
		// 	$this ->error('只能删除今日添加的内容');
		// }

		$result = Db::name('help')->where('id',$data['id'])->update(['delete_id'=>session('admin_user_auth')['uid'],'delete_time'=>time()]);
		if( $result ){
			$this ->success('删除成功');
		}else{
			$this ->error('删除失败');
		}
	}
}