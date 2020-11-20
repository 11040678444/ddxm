<?php
/*
	订单模型
*/
namespace app\index\model\Service;

use think\Model;
use think\Cache;
use think\Db;

class Service extends Model
{
    protected $table = 'ddxm_service';
    public function order_list($shop_id,$data){
    	$where['shop_id'] = intval($shop_id);
        $where['type'] = !isset($data['type'])?1:intval($data['type']);
        return $this->where($where)
                ->field("sn,shop_id as shop,member_id,old_amount,amount,pay_way,overtime as time,waiter,order_status as order_list_status,id")
                ;
    }
    public function getService($val){

    }

}
