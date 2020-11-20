<?php
namespace app\index\controller;


use think\Controller;
use think\Db;
use think\Query;
use think\Request;

class Help extends Base
{
	public function index(){
		$data = $this ->request ->param();
		if( empty($data['page']) ){
			$data['page'] = '';
		}
		$where = [];
		if ( !empty($data['content']) ){
			$content = $data['content'];
			$where[] = ['title|content','like',"%$content%"];
		}
		$where[] = ['delete_time','ELT',0];
		$list = Db::name('help')->where($where)->field('id,title,content')->page($data['page'])->select();
		return json(['code'=>200,'msg'=>'请求成功','data'=>$list]);
	}
}