<?php
/*
	服务卡模型
*/
namespace app\index\model\ticket;

use think\Model;
use think\Cache;
use think\Db;

class ticketModel extends Model
{
	protected $table = 'ddxm_ticket_card';

	//获取门店列表 $shop_id 根据传递的XX-token获取的门店id
	public function ticket_list(){
		return db::name("ticket_shop as s")
		->join("ddxm_ticket_card a","s.card_id = a.id");		
	}
	public function getShopCode($val){
		return db::name("shop")->where("id",intval($val))->value("code");
	}
	public function getpay($val){
		$status = [
            0 =>'待发货',
            1 => '待收货',
            2 => '确认收货',
            -1 => '申请退款',
            -2 => '退货退款',
            -7 => '已取消',
            8 => '配送中',
            9 => '待处理'
        ];
        return $status[$value];
	}
}