<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/24 0024
 * Time: 下午 16:30
 *
 * 订单商品详细
 */

namespace app\mall_admin_order\controller;
use app\common\controller\OrderBase;
use think\Validate;

class OrderGoods extends OrderBase
{
    /**
     * 查询订单详细
     * @param order_id 订单id
     */
    public function getOrderDetail()
    {
        try{
            if(request()->isPost())
            {
                $validate=new Validate([
                    'order_id|订单id'=>'require'
                ]);
                if (!$validate->check(request()->param())){
                    return return_error($validate->getError());
                }

                $model = model('OrderGoods');

                //调用查询
                $data = $model::getOrderDetail(input('order_id'));

                return_succ($data,'ok');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}