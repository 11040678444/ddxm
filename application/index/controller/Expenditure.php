<?php
namespace app\index\controller;


use think\Controller;
use think\Db;
use think\Query;
use think\Request;
use app\index\model\Expenditure\ExpenditureTypesModel;
use app\index\model\Expenditure\ExpenditureModel;


/**
	财务系统->门店支出
*/
class Expenditure extends Base
{

	/**
		进销存类型列表
	*/
	public function typesList(){
		$Expenditure = new ExpenditureTypesModel();
		$data = $this ->request ->post();
		if( empty($data['page']) ){
			$data['page'] = '';
		}
		$where['delete_time'] = array('<=',0);
		$list = $Expenditure ->where($where)->page($data['page'])->order('sort asc')->field('id,title,update_time')->select();
		$count = $Expenditure ->where($where)->count();
		return json(['code'=>'200','msg'=>'查询成功','count'=>$count,'data'=>$list]);
	}


	/**
		进销存->添加类型
	*/
	public function addTypes(){
		$Expenditure = new ExpenditureTypesModel();
		$data = $this ->request ->post();
		if( empty($data['title']) ){
			return json(['code'=>'-3','msg'=>'缺少参数:类型名称','data'=>'']);
		}

		if( empty($data['sort']) ){
			$data['sort'] = 99;
		}

		$data['update_time'] = time();
		if( empty($data['id']) ){
			$data['user_id'] = $this ->getUserInfo()['id'];
			$data['create_time'] = time();
			$result = $Expenditure ->insert($data);
		}else{
			$result = $Expenditure ->where('id',$data['id'])->update($data);
		}
		
		if( $result ){
			return json(['code'=>'200','msg'=>'操作成功','data'=>'']);
		}else{
			return json(['code'=>'500','msg'=>'服务器内部错误','data'=>'']);
		}
	}

	/**
		进销存->删除类型
	*/
	public function deleteType(){
		$Expenditure = new ExpenditureTypesModel();
		$data = $this ->request ->post();
		if( empty($data['id']) ){
			return json(['code'=>'-3','msg'=>'缺少参数:类型id','data'=>'']);
		}

		$result = $Expenditure ->where('id',$data['id'])->update(['delete_time'=>time()]);
		if( $result ){
			return json(['code'=>'200','msg'=>'删除成功','data'=>'']);
		}else{
			return json(['code'=>'500','msg'=>'服务器内部错误','data'=>'']);
		}
	}


	//进销存营业支出
	/**
		营业支出列表
	*/
	public function expenditure_list(){
		$data = $this ->request ->post();
		$Expenditure = new ExpenditureModel();
		$where = [];
		if ( !empty($data['shop_id']) ) {
			// $where['shop_id'] = $data['shop_id'];
			$where[] = ['shop_id','=',$data['shop_id']];
		}

		if( !empty($data['type_id']) ){
			// $where['type_id'] = $data['type_id'];
			$where[] = ['type_id','=',$data['type_id']];
		}

		if ( !empty($data['start_time']) ) {
			$start_time = strtotime($data['start_time']);
			// $where['create_time'] = array('EGT',$start_time);
			$where[] = ['create_time','>=',$start_time];
		}

		if ( !empty($data['end_time']) ) {
			$end_time = strtotime($data['end_time']);
			// $where['create_time'] = array('ELT',$end_time);
			$where[] = ['create_time','<=',$end_time];
		}

		// $where['delete_time'] = array('<=',0);
		$where[] = ['delete_time','<=',0];
		if( empty($data['page']) ){
			$data['page'] = '';
		}
		$list = $Expenditure 
				->where($where)
				->page($data['page'])
				->order('create_time desc')
				->field('id,type_id,shop_id,user_id,price,remarks,create_time,is_admin')
				->select()
				->append(['type']);
		$count = $Expenditure 
				->where($where)
				->count();
		return json(['code'=>'200','msg'=>'查询成功','count'=>$count,'data'=>$list]);
	}


	/**
	营业支出添加
	*/
	public function expenditure_add(){
		$data = $this ->request ->post();
		$Expenditure = new ExpenditureModel();

		if( empty($data['type_id']) || empty($data['price']) ){
			return json(['code'=>'-3','msg'=>'缺少参数类型或者金额','data'=>'']);	
		}

		if ( empty($data['id']) ) {
			$data['user_id'] = $this ->getUserInfo()['id'];
			$data['create_time'] = time();
			$data['time'] = time();
			$result = $Expenditure ->insertGetId($data);
			$statisticsLog = array(
				'shop_id'	=>$data['shop_id'],
				'expenditure_id'	=>$result,
				'type'		=>9,
				'price'		=>$data['price'],
				'create_time'	=>time(),
				'title'		=>Db::name('expenditure_types')->where('id',$data['type_id'])->value('title'),
			);
			Db::name('statistics_log')->insert($statisticsLog);
		}else{
			$time = Db::name('expenditure')->where('id',$data['id'])->value('create_time');
			$start_time = strtotime(date('Y-m-d',$time).' 00:00:00');
			$end_time = strtotime(date('Y-m-d',$time).' 23:59:59');
			if( time() < $start_time || time() > $end_time ){
				return json(['code'=>10,'msg'=>'只允许编辑当天的支出']);
			}
			$statisticsLog = array(
				'shop_id'	=>$data['shop_id'],
				'price'		=>$data['price'],
			);
			$result = $Expenditure ->where('id',$data['id'])->update($data);
			Db::name('statistics_log')->where(['expenditure_id'=>$data['id']])->update($statisticsLog);
		}
		if( $result ){
			return json(['code'=>'200','msg'=>'操作成功','data'=>'']);
		}else{
			return json(['code'=>'500','msg'=>'服务器内部错误','data'=>'']);
		}
	}


	/**
	营业删除
	*/
	public function expenditure_delete(){
		$Expenditure = new ExpenditureModel();
		$data = $this ->request ->post();
		if( empty($data['id']) ){
			return json(['code'=>'-3','msg'=>'缺少参数:类型id','data'=>'']);
		}

		if ( empty($data['delete_why']) ) {
			return json(['code'=>'-3','msg'=>'缺少参数:删除原因','data'=>'']);
		}
		$info =  Db::name('expenditure') ->where('id',$data['id'])->find();
		if( $info['shop_id'] != $this ->getUserInfo()['shop_id'] ){
			return json(['code'=>'-3','msg'=>'仅限删除自己添加的营业支出','data'=>'']);
		}

		$start_time = strtotime(date('Y-m-d',$info['create_time']).' 00:00:00');
		$end_time = strtotime(date('Y-m-d',$info['create_time']).' 23:59:59');
		if( time() < $start_time || time() > $end_time ){
			return json(['code'=>'-3','msg'=>'仅限删除当天添加的营业支出','data'=>'']);	
		}

		$result = $Expenditure ->where('id',$data['id'])->update(['delete_time'=>time(),'delete_why'=>$data['delete_why'],'delete_user_id'=>$this ->getUserInfo()['id']]);
		// dump($result);die;
		if( $result ){

			$statisticsLog = array(
				'shop_id'	=>$info['shop_id'],
				'expenditure_id'	=>$data['id'],
				'type'		=>9,
				'price'		=>'-'.$info['price'],
				'data_type'		=>2,
				'create_time'	=>time(),
				'title'		=>'删除'
			);
			// Db::name('statistics_log')->insert($statisticsLog);
			Db::name('statistics_log') ->where('expenditure_id',$data['id'])->delete();
			return json(['code'=>'200','msg'=>'删除成功','data'=>'']);
		}else{
			return json(['code'=>'500','msg'=>'服务器内部错误','data'=>'']);
		}
	}
}