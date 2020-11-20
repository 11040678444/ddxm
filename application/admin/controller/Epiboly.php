<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
// use app\index\model\Shop\ShopModel;
use app\admin\model\epiboly\EpibolyModel;
use think\Db;

/**
	外包分润管理
*/
class Epiboly extends Adminbase
{
	public function index(){
		if ($this->request->isAjax()) {
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $where = [];
            $where['delete_time'] = array('<=','0');
            $Epiboly = new EpibolyModel();
            $list = $Epiboly->where($where)->page($page,$limit)->order('create_time desc')->select();
            foreach ($list as $key => $value) {
            	$list[$key]['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
            }
            $total =  $Epiboly->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		return $this->fetch();
	}

	public function add(){
		$id = $this ->request ->param('id');
		if( !empty($id) ){
			$list = Db::name('epiboly')->where('id',$id)->find();
			$list['time'] = date('Y-m-d H:i:s',$list['time']);
			$this ->assign('list',$list);
		}
		$where = [];
		$where[] = ['status','=',1];
		$where[] = ['code','neq',0];
		$shop = Db::name('shop')->where($where)->select();
		$this ->assign('shop',$shop);
		return $this->fetch();
	}

	public function doPost(){
		$data = $this ->request ->post();
		$Epiboly = new EpibolyModel();
		
		if( empty($data['type']) || empty($data['price']) ){
			return json(['code'=>0,'msg'=>'请选择类型或输入金额']);
		}
        if( empty($data['time'])  ){
            return json(['code'=>0,'msg'=>'请选择分润时间']);
        }
		$data['time'] = strtotime($data['time']);
		if ( empty($data['id']) ) {
			$data['user_id'] = session('admin_user_auth')['uid'];
			$data['create_time'] = time();
			$data['update_time'] = time();
			$result = $Epiboly ->insertGetId($data);
			if( $data['type'] == 1 ){	//外包商品
				$statisticsLog_type = 6;
				$title = '商品外包分润';
			}else{
				$statisticsLog_type = 7;
				$title = '服务外包分润';
			}
			$statisticsLog = array(
					'shop_id'	=>$data['shop_id'],
					'epiboly_id'	=>$result,
					'type'		=>$statisticsLog_type,
					'pay_way'	=>0,
					'price'		=>$data['price'],
					'create_time'	=>$data['time'],
					'title'		=>$title
				);
			Db::name('statistics_log')->insert($statisticsLog);
		}else{
			$data['update_time'] = time();
			$result = $Epiboly ->where('id',$data['id'])->update($data);
			if( $data['type'] == 1 ){	//外包商品
				$statisticsLog_type = 6;
				$title = '商品外包分润';
			}else{
				$statisticsLog_type = 7;
				$title = '服务外包分润';
			}
			$statisticsLog = array(
					'shop_id'	=>$data['shop_id'],
					'type'		=>$statisticsLog_type,
					'price'		=>$data['price'],
					'create_time'	=>$data['time'],
					'title'		=>$title
				);
			Db::name('statistics_log')->where(['epiboly_id'=>$data['id']])->update($statisticsLog);
		}

		if( $result ){
			// $this ->success('操作成功',url('/admin/Epiboly/index'));
			return json(['code'=>1,'msg'=>'操作成功']);
		}else{
			// $this ->error('操作失败');
			return json(['code'=>0,'msg'=>'操作失败']);
		}
	}

	public function del(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			$this ->error('缺少id');
		}
		$info = Db::name('epiboly')->where(['id'=>$data['id']])->find();
		if( $info['type'] == 1 ){	//外包商品
				$statisticsLog_type = 6;
			}else{
				$statisticsLog_type = 7;
			}
		$result = Db::name('epiboly')->where(['id'=>$data['id']])->update(['delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
		$statisticsLog = array(
					'shop_id'	=>$info['shop_id'],
					'epiboly_id'	=>$data['id'],
					'type'		=>$statisticsLog_type,
					'pay_way'	=>0,
					'price'		=>$info['price'],
					'data_type'	=>2,
					'create_time'	=>time()
				);
		Db::name('statistics_log') ->where('epiboly_id',$data['id'])->delete();
		// Db::name('statistics_log')->insert($statisticsLog);
		if( $result ){
			$this ->success('删除成功');
		}else{
			$this ->error('删除失败');
		}
	}
}