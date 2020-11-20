<?php
/*
    分销订单模型
*/
namespace app\wxshop\model\order;
use app\wxshop\model\order\OrderinfoModel;
use think\Model;
use think\Db;

class OrderRetailModel extends Model
{
    protected $table = 'ddxm_order_retail';

    /***
     * 订单信息
     * @param $val
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderInfoAttr($val,$data){
        $orderId = $data['order_id'];
        $order = (new OrderinfoModel())
            ->where('id',$orderId)
            ->field('id,sn,member_id,add_time')
            ->find() ->append(['status','member_name','item_list'])->toArray();
        $order['all_item_num'] = count($order['item_list']);
        foreach ( $order['item_list'] as $k=>$v ){
            if( $data['order_goods_id'] != 0 ){
                if( $data['order_goods_id'] != $v['id'] ){
                    unset($order['item_list'][$k]);
                }
            }
        }
        return $order;
    }

    /***
     * 时间
     */
    public function getTimeAttr( $val ){
        return date('m月d日 H:i');
    }

    /***
     * 获取一个订单的总佣金
     */
    public function getPriceAttr($val,$data){
        $where = [];
        $where[] = ['member_id','eq',$data['member_id']];
        $where[] = ['order_id','eq',$data['order_id']];
        return $this ->where($where)->sum('price');
    }
}