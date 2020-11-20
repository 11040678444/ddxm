<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/17 0017
 * Time: 上午 11:48
 * 消费抵扣记录（充值送抵扣金额活动）
 */
namespace app\wxshop\model\st_recharge;
use think\Model;

class StRechargeFlow extends Model
{

    /**
     * 添加数据
     * @param $data 数据集
     * @return bool
     */
   public function setRechargeFlow($data)
   {
       $res = StRechargeFlow::allowField(true)->saveAll($data);
       return $res;
   }

    /**
     * 抵扣金额使用记录
     * @param $member_id
     * @return array
     */
   public function getRechargeFlow($member_id)
   {

       $where[] = ['srf.type','in','1,2'];
       $where[] = ['srf.member_id','eq',$member_id];

       $field = 'from_unixtime(srf.create_time,"%Y-%m-%d %H:%i") create_time,sum(srf.discount_price) discount_price,srf.type,o.sn';

       $list = StRechargeFlow::alias('srf')
             ->field($field)
             ->join('order o','srf.order_id = o.id','left')
             ->where($where)
             ->order('create_time desc')
             ->group('srf.order_id,srf.create_time')
             ->paginate(10);

       return $list->toArray();
   }

   protected function getTypeAttr($val)
   {
       $type = array(
           '1'		=>'-',//使用
           '2'		=>'+'//退回
       );

       return $type[$val];
   }
}