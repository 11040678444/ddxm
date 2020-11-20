<?php

// +----------------------------------------------------------------------
// | 商品
// +----------------------------------------------------------------------
namespace app\admin\model\itemout;

use think\Model;
use think\Db;

class OrderRefundModel extends Model
{
	protected $table = 'ddxm_order_refund';

	//获取订单列表
	public function getOrderList($data){
		$where = [];
		if( !empty($data['shop_id']) ){
			$where[] = ['b.shop_id','=',$data['shop_id']];
		}
		if( !empty($data['start_time']) ){
			$start_time = strtotime($data['start_time'].' 00:00:00');
			$where[] = ['a.add_time','>=',$start_time];
		}
		if( !empty($data['end_time']) ){
			$end_time = strtotime($data['end_time'].' 00:00:00');
			$where[] = ['a.dealwith_time','>=',$end_time];
		}
		if( !empty($data['name']) ){
			$where[] = ['a.r_sn|a.o_sn','like','%'.$data['name'].'%'];
		}
		$where[] = ['b.member_id','neq',0];
		$where[] = ['a.r_status','=',1];
		$where[] = ['b.type','in','1,7'];
		$list = $this 
			->alias('a')
			->join('order b','a.order_id=b.id')
			->where($where)
			->order('a.dealwith_time desc')
			->field('a.id,b.shop_id,b.member_id,a.r_sn,a.o_sn,a.r_amount,a.r_number,b.user_id,b.is_admin,a.dealwith_time,a.order_id,a.creator,a.dealwith_time');
		return $list;
	}

	//拼装订单信息
	public function getMessageAttr($val,$data){
		$shop = Db::name('shop')->where('id',$data['shop_id'])->value('name');
		$sn = $data['sn'];
		$message = "<p>订单号：".$data['o_sn']."</p>
				<p>退货单号：".$data['r_sn']."</p>
				<p>退货仓库：".$shop."</p>";
		return $message;
	}
	//拼装会员信息
	public function getMemberAttr($val,$data){
		$member_id = Db::name('order')->where('id',$data['order_id'])->value('member_id');
		$member = Db::name('member')->where('id',$member_id)->field('mobile,nickname')->find();
		$sn = $data['sn'];
		$message = "<p>会员名称：".$member['nickname']."</p>
				<p>会员手机号：".$member['mobile']."</p>";
		return $message;
	}
	//拼装退货信息
	public function getShopAttr($val,$data){
		$message = "<p>出库人：".$data['creator']."</p>
				<p>出库时间：".date('Y-m-d H:i:s',$data['dealwith_time'])."</p>";
		return $message;
	}
	//拼接商品信息
	public function getItemListAttr($val,$data){
		$item1 = Db::name('order_refund_goods')->where('refund_id',$data['id'])->column('r_subtitle');
		$item = $item1;
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p>".$value."</p>";
		}
		return $tt;
	}
	//拼接单价信息
	public function getPriceListAttr($val,$data){
		$item = [];
		$item1 = Db::name('order_refund_goods')->where('refund_id',$data['id'])->column('r_price');
		$item = $item1;
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p> ￥".$value." 元</p>";
		}
		return $tt;
	}

	//拼接退货数量
	public function getNumListAttr($val,$data){
		$item = [];
		$item1 = Db::name('order_refund_goods')->where('refund_id',$data['id'])->column('r_num');
		$item = $item1;
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p> ".$value." </p>";
		}
		return $tt;
	}
	//拼接退货总金额
	public function getAllPriceAttr($val,$data){
		$item = [];
		$item1 = Db::name('order_refund_goods')->where('refund_id',$data['id'])->field('r_num,r_price')->select();
		foreach ($item1 as $key => $value) {
			array_push($item, $value['r_num']*$value['r_price']);
		}
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p> ￥".$value."元 </p>";
		}
		return $tt;
	}

}
