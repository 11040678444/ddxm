<?php

// +----------------------------------------------------------------------
// | 商品
// +----------------------------------------------------------------------
namespace app\admin\model\itemout;

use think\Model;
use think\Db;

class OrderModel extends Model
{
	protected $table = 'ddxm_order';

	//获取出库单订单列表
	public function getOrderList($data){
		$where = [];
		if( !empty($data['shop_id']) ){
			$where[] = ['shop_id','=',$data['shop_id']];
		}
		if( !empty($data['time']) ){
			$start_time = strtotime($data['time']);
			$where[] = ['paytime','>=',$start_time];
		}
		if( !empty($data['end_time']) ){
			$end_time = strtotime($data['end_time']);
			$where[] = ['paytime','<=',$end_time];
		}
		if( !empty($data['name']) ){
			$where[] = ['sn','like','%'.$data['name'].'%'];
		}
		$where[] = ['type','in','1,7'];
		$list = $this 
			->where($where);
		return $list;
	}

	//拼装订单信息
	public function getMessageAttr($val,$data){
		$shop = Db::name('shop')->where('id',$data['shop_id'])->value('name');
		$sn = $data['sn'];
		$status = array(
                1 => '微信',
                2 => '支付宝',
                3 => '余额',
                4 => '银行卡',
                5 => '现金支付',
                6 => '美团',
                7 => '赠送',
                8 => '门店自用',
                9 => '兑换',
                10 => '包月服务',
                11 => '定制疗程',
                99 => '异常充值'
            );
		$message = "<p>订单号：".$sn."</p>
				<p>支付方式：".$status[$data['pay_way']]."</p>
				<p>出货仓库：".$shop."</p>";
		return $message;
	}

	//拼接商品信息
	public function getItemListAttr($val,$data){
		$item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('subtitle');
		$item = $item1;
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p>".$value."</p>";
		}
		return $tt;
	}

	//拼接单价信息
	public function getPriceListAttr($val,$data){
		$item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('real_price');
		$item = $item1;
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p> ￥".$value." 元</p>";
		}
		return $tt;
	}

	//拼接单价信息
	public function getAllPriceListAttr($val,$data){
		$item1 = Db::name('order_goods')->where('order_id',$data['id'])->field('real_price,num')->select();
		foreach ($item1 as $key => $value) {
			$item1[$key]['all_price'] = $value['num']*$value['real_price'];
		}
		$item = $item1;
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p> ￥".$value['all_price']." 元</p>";
		}
		return $tt;
	}

	//拼接数量信息
	public function getNumListAttr($val,$data){
		$item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('num');
		$item = $item1;
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p> X".$value."</p>";
		}
		return $tt;
	}

	//拼接成本
	public function getCostListAttr($val,$data){
		$item = [];
		if( $data['type'] == 1 ){
			$item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('all_oprice');
			foreach ($item1 as $key => $value) {
				array_push($item, $value);
			}
		}

		if( $data['type'] == 7 ){
			$item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('all_oprice');
			foreach ($item1 as $key => $value) {
				array_push($item, $value);
			}
		}
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p> ￥".$value." 元</p>";
		}
		return $tt;
	}

	//拼接出库信息
	public function getOutShopAttr($val,$data){
		$time = date('Y-m-d H:i:s',$data['paytime']);
		$message = "<p>出库人员：".$data['waiter']."</p>
				<p>出库时间：".$time."</p>";
		return $message;
	}

	//拼接会员信息
	public function getMemberInfoAttr($val,$data){
		$member_id = $data['member_id'];
		if( !empty($member_id) ){
			$me = Db::name('member')->where('id',$member_id)->field('mobile,nickname')->find();
			$message = "<p>会员名称：".$me['nickname']."</p>
				<p>手机号码：".$me['mobile']."</p>";
		}else{
			$message = "散客用户";
		}
		return $message;
	}

	//转换时间
	public function getOvertimeAttr($val){
		return date('Y-m-d H:i:s',$val);
	}

	//获取退货单
	public function getOutOrder($data){
		$where = [];
		$where[] = ['a.order_status','eq','-2'];
		$where[] = ['a.type','=',1];
		$where[] = ['b.r_status','=',1];
		if( !empty($data['shop_id']) ){
			$where[] = ['b.shop_id','=',$data['shop_id']];
		}
		if( !empty($data['start_time']) ){
			$start_time = strtotime($data['start_time'].' 00:00:00');
			$where[] = ['b.add_time','>=',$start_time];
		}
		if( !empty($data['end_time']) ){
			$end_time = strtotime($data['end_time'].' 00:00:00');
			$where[] = ['b.dealwith_time','>=',$end_time];
		}
		if( !empty($data['name']) ){
			$where[] = ['b.r_sn','like','%'.$data['name'].'%'];
		}

		$list = $this 
			->alias('a')
			->where($where)
			->join('order_refund b','a.id=b.order_id')
			->field('b.id,a.shop_id,a.member_id,b.r_sn,b.r_amount,b.r_number,b.dealwith_time')
			->order('b.add_time desc');
		return $list;

	}
}