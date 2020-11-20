<?php
/**
 * 订单管理->待发货订单
 */
namespace app\mall_admin_order\controller;

use app\common\controller\OrderBase;
use think\Validate;

class OrderList extends OrderBase
{
    /*
     * 获取订单列表
     * @param order_status 订单状态
     */
    public function getOrderList()
    {
        try{
            if(request()->isPost())
            {
                //数据验证
                dataValidate(request()->param(),[
                   'order_status|订单状态'=>'require',
                   //'search_param|查询类型'=>'require'
               ]);

                //组装条件
                $where[] =['o.isdel','eq',0];
                $where[] =['o.pay_status','eq',1];
                //$where[] =['o.type','in','1,2,7'];
                $where[] =['o.type','eq','1'];
                $where[] =['o.order_triage','eq',0];

                //如果是搜索查询拼接查询条件
                if(!empty(input('search_param')))
                {
                    $param = input('search_param');
                    !empty($param['sn']) ? $where[]=['o.sn','eq',$param['sn']] : '';//订单编号
                    !empty($param['mobile']) ? $where[]=['o.mobile','eq',$param['mobile']] : '';//收货人电话
                    !empty($param['pay_status']) ? $where[]=['o.pay_status','eq',$param['pay_status']] : '';//支付状态
                    !empty($param['pay_way']) ? $where[]=['o.pay_way','eq',$param['pay_way']] : '';//支付类型
                    !empty($param['s_subtitle']) ? $where[]=['osg.s_subtitle','like',$param['s_subtitle']] : '';//商品名称
                    !empty($param['order_distinguish']) ? $where[]=['o.order_distinguish','eq',$param['order_distinguish']] : '';//订单类型
                    !empty($param['realname']) ? $where[]=['o.realname','eq',$param['realname']] : '';//收货人名称
                    !empty($param['start_time']) ? $where[]=['o.add_time','between',$param['start_time'],!empty($param['end_time'])?$param['end_time']:time()] : '';//订单时间

                    //所有订单列表包含以下条件
                    if(input('order_status') == 'all')
                    {
                        !empty($param['shop_id']) ? $where[]=['osg.shop_id','eq',$param['shop_id']] : '';//门店
                        !empty($param['order_status']) ? $where[]=['o.order_status','eq',$param['order_status']] : '';//订单状态
                    }
                }

                //实例化模型对象
                $order = model('Order');
                //调用模型查询，并处理结果
                $list = $order->getOrder(input('order_status'),$where);

                return_succ($list,'ok');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

}
