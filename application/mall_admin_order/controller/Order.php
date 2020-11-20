<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/26 0026
 * Time: 下午 23:00
 */

namespace app\mall_admin_order\controller;
use app\common\controller\OrderBase;
use app\mall_admin_order\model\Order as OrderModel;
use  app\mall_admin_order\model\OrderExpress;

class Order extends OrderBase
{
    /**
     * 设置订单物流
     */
    public function setOrderExpress()
    {
        try{
            dataValidate(request()->param(),[
                'title|快递名称'=>'require',
                'sn|物流号'=>'require',
                'code|物流编号'=>'require',
                'order_id|订单id'=>'require',
                'id|订单商品明细ID'=>'require'
            ]);

            //订单商品明细ID多条与单条数据处理
            $datas = [];
            if(is_array(input('id')))
            {
                //多商品
                foreach (input('id') as $key=>$value)
                {
                    $datas[]=[
                        'title'=>input('title'),
                        'sn'=>input('sn'),
                        'code'=>input('code'),
                        'add_time'=>time(),
                        //'operator' 这里等接入登录后取session信息
                        'order_id'=>input('order_id'),
                        'order_goods_id'=>$value
                    ];
                }
            }else{
                //一件商品
               $datas[]=[
                   'title'=>input('title'),
                   'sn'=>input('sn'),
                   'code'=>input('code'),
                   'add_time'=>time(),
                   //'operator' 这里等接入登录后取session信息
                   'order_id'=>input('order_id'),
                   'order_goods_id'=>input('order_goods_id')
               ];
            }

            $orderExp = new OrderExpress();
            $orderExp->startTrans();
            $res = $orderExp->setOrderExpress($datas);
            if(!$res){$orderExp->rollback();return false;}
            //empty($res)?return_error('操作失败'):return_succ([],'操作成功');
            $orderExp->commit();
            return $res;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**
     * 修改订单金额
     */
    public function upOrderAmount()
    {
        try{
            if(request()->isPost())
            {
                dataValidate(request()->param(),[
                    'id|订单ID'=>'require',
                    'amount|需修改价格'=>'require',
                    'old_amount|订单原价'=>'require'
                ]);

                $order = new  OrderModel();
                $res = $order->upOrder(request()->param());

                !empty($res) ? return_succ([],'修改订单金额成功') : return_error('操作失败');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}