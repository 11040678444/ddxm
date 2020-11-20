<?php
/*
    订单模型
*/
namespace app\wxshop\model\order;
use think\Model;
use think\Cache;
use think\Db;

class OrderRefundModel extends Model
{
	protected $table = 'ddxm_order_refund';
}