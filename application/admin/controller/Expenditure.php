<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use app\index\model\Expenditure\ExpenditureTypesModel;
use app\index\model\Expenditure\ExpenditureModel;
use app\index\model\Shop\ShopModel;
/**
	营业支出
*/
class Expenditure extends Adminbase
{
	//类型
	public function type_list(){
		if ($this->request->isAjax()) {
			$Expenditure = new ExpenditureTypesModel();
			$data = $this ->request ->post();
			$limit = $this->request->param('limit/d', 10);
	        $page = $this->request->param('page/d', 10);
	        $where = [];
			$where['delete_time'] = array('<=',0);
			$list = $Expenditure ->where($where)
					->page($page,$limit)
					->field('id,title,update_time')
					->order('sort asc,create_time desc')
					->select();
			$total =  $Expenditure->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		return $this->fetch();
	}

	public function add(){
		$Expenditure = new ExpenditureTypesModel();
		$data = $this ->request ->get();
		if( !empty($data['id']) ){
			$list = $Expenditure ->where('id',$data['id'])->find();
			$this ->assign('list',$list);
		}
		return $this->fetch();
	}

	//luoji处理
	public function doPost(){
		$data = $this ->request ->post();
		$Expenditure = new ExpenditureTypesModel();

		if( empty($data['title']) ){
			$this ->error('请输入标题');
		}
		$data['update_time'] = time();
		if( empty($data['id']) ){
			$data['user_id'] = session('admin_user_auth')['uid'];
			$data['create_time'] = time();
			$result = $Expenditure ->insert($data);
		}else{
			$result = $Expenditure ->where('id',$data['id'])->update($data);
		}

		if( $result ){
			return json(['code'=>1,'msg'=>'操作成功']);
		}else{
			return json(['code'=>0,'msg'=>'操作失败']);
		}
	}

	public function del(){

		$data = $this ->request ->param();
		if( empty($data['id']) ){
			$this ->error('缺少id');
		}
		$Expenditure = new ExpenditureTypesModel();
		$result = $Expenditure->where('id',$data['id'])->update(['delete_time'=>time()]);
		if( $result ){
			$this ->success('删除成功');
		}else{
			$this ->error('删除失败');
		}
	}

	//营业支出
	public function show_list(){
		if ($this->request->isAjax()) {
			$Expenditure = new ExpenditureModel();
			$data = $this ->request ->param();
			$limit = $this->request->param('limit/d', 10);
	        $page = $this->request->param('page/d', 10);
	        $data = $this ->request->param();
	        $where = [];
	        if ( !empty($data['shop_id']) ) {
				$where[] = ['shop_id','=',$data['shop_id']];
			}

	        if ( !empty($data['type_id']) )
            {
                $where[] = ['type_id','eq',$data['type_id']];
            }

	        if ( !empty($data['remarks']) )
            {
                $where[] = ['remarks','like','%'.$data['remarks'].'%'];
            }

			if ( !empty($data['start_time']) ) {
				$start_time = strtotime($data['start_time'].' 00:00:00');
				$where[] = ['time','EGT',$start_time];
			}
			if(isset($data['status']) && $data['status'] != '' ){
                $where[] = ['status',"=",$data['status']];
            }

			if ( !empty($data['end_time']) ) {
				$end_time = strtotime($data['end_time'].' 23:59:59');
				$where[] = ['time','ELT',$end_time];
			}

			$where[] = ['delete_time','ELT',0];
			$list = $Expenditure ->where($where)
					->page($page,$limit)
					->field('id,type_id,shop_id,user_id,price,remarks,create_time,status,is_admin,time')
					->order('id desc')
					->select()
                    ->append(['type']);
			$total =  $Expenditure->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		$whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('id,name')->select();
        $this ->assign('shop',$shop);
        $ExpenditureType = new ExpenditureTypesModel();
        $whereT = [];
        $whereT['delete_time'] = array('<=',0);
        $listType = $ExpenditureType ->where($whereT)
            ->field('id,title')
            ->order('sort asc,create_time desc')
            ->select();
        $this ->assign('type',$listType);
		return $this->fetch();
	}


	//营业添加
	public function add1(){
		$Expenditure = new ExpenditureModel();
		$Expendituret = new ExpenditureTypesModel();
		$data = $this ->request ->get();
		$type = $Expendituret->where(['delete_time'=>array('<=',0)]) ->select();	//类型

		$where = [];
		$where[] = ['status','eq',1];
		$where[] = ['code','neq',0];
		$shop = Db::name('shop') ->where($where)->select();
		
		if( !empty($data['id']) ){
			$list = Db::name('expenditure') ->where('id',$data['id'])->find();
			$list['time'] = date('Y-m-d H:i:s',$list['time']);
			$this ->assign('list',$list);
		}
		$this ->assign('types',$type);
		$this ->assign('shop',$shop);
		return $this->fetch();
	}


	//逻辑
	public function doPost1(){
		$data = $this ->request ->post();
		$Expenditure = new ExpenditureModel();
		if( empty($data['type_id']) || empty($data['price']) ){
            return json(['code'=>0,'msg'=>'请选择类型或输入金额']);
		}
		if( empty($data['time']) ){
            return json(['code'=>0,'msg'=>'请选择支出时间']);
        }
		$data['time'] = strtotime($data['time']);
		if ( empty($data['id']) ) {
			$data['user_id'] = session('admin_user_auth')['uid'];
			$data['create_time'] = time();
			$data['is_admin'] = 1;
			$result = $Expenditure ->insertGetId($data);
			$statisticsLog = array(
					'shop_id'	=>$data['shop_id'],
					'expenditure_id'	=>$result,
					'type'		=>9,
					'price'		=>$data['price'],
					'create_time'	=>$data['time'],
					'title'		=>Db::name('expenditure_types')->where('id',$data['type_id'])->value('title')
				);
			Db::name('statistics_log')->insert($statisticsLog);
		}else{
			$statisticsLog = array(
					'shop_id'	=>$data['shop_id'],
					'price'		=>$data['price'],
					'title'		=>Db::name('expenditure_types')->where('id',$data['type_id'])->value('title')
				);
			$result = $Expenditure ->where('id',$data['id'])->update($data);
			Db::name('statistics_log')->where(['expenditure_id'=>$data['id']])->update($statisticsLog);
		}
		if( $result ){
			return json(['code'=>1,'msg'=>'操作成功']);
		}else{
			return json(['code'=>0,'msg'=>'操作失败']);
		}
	}

	//
	public function del1(){
		$data = $this ->request ->get();
		if ( empty($data['id']) ) {
			$this ->error('缺少id');
		}
		$this ->assign('id',$data['id']);
		return $this->fetch();
	}

	public function de(){
		$Expenditure = new ExpenditureModel();
		$data = $this ->request ->post();
		if ( empty($data['id']) ) {
			$this ->error('缺少id');
		}
		if ( empty($data['delete_why']) ) {
			$this ->error('请选择原因');
		}
		$result = $Expenditure ->where('id',$data['id'])
					->update(['delete_time'=>time(),'delete_user_id'=>session('admin_user_auth')['uid'],'delete_why'=>$data['delete_why']]);

		$info = Db::name('expenditure') ->where('id',$data['id'])->find();
		$statisticsLog = array(
				'shop_id'	=>$info['shop_id'],
				'expenditure_id'	=>$data['id'],
				'type'		=>9,
				'price'		=>'-'.$info['price'],
				'data_type'		=>2,
				'create_time'	=>time(),
				'title'		=>'删除'
			);
		Db::name('statistics_log') ->where('expenditure_id',$data['id'])->delete();
		// Db::name('statistics_log')->insert($statisticsLog);
		if( $result ){
			$this ->success('删除成功',url('/admin/Expenditure/show_list'));
		}else{
			$this ->error('删除失败');
		}
	}

	//批量对账
	public function edit_status(){
		$data = $this ->request ->param();
		if( count($data)<=0 ){
			return json(['code'=>0,'msg'=>'请先选择']);
		}
		$ids = implode(',',$data['ids']);
		$where[] = ['id','in',$ids];
		$result = Db::name('expenditure')->where($where)->setField('status',1);
		if( $result ){
			return json(['code'=>1,'msg'=>'对账成功']);
		}else{
			return json(['code'=>0,'msg'=>'对账失败']);
		}
	}

	//对账
	public function duizhang(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			$this ->error('id参数错误');
		}
		$result = Db::name('expenditure')->where('id',$data['id'])->setField('status',1);
		if( $result ){
			$this ->success('对账成功');
		}else{
			$this ->error('对账失败');
		}
	}
	//对账
	public function quxiao(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			$this ->error('id参数错误');
		}
		$result = Db::name('expenditure')->where('id',$data['id'])->setField('status',0);
		if( $result ){
			$this ->success('操作成功');
		}else{
			$this ->error('操作失败');
		}
	}
}