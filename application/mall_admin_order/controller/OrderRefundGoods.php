<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/28 0028
 * Time: 上午 11:15
 */

namespace app\mall_admin_order\controller;
use app\common\controller\OrderBase;
use app\mall_admin_order\model\OrderRefundGoods as RefundGoods;

class OrderRefundGoods extends OrderBase
{
    /**
     * 处理退单订单流程
     */
    public function operateRefundGoods()
    {
        try{
            //数据验证
            dataValidate(request()->param(),[
                'operate_type|操作类型'=>'require',
                'id|退货主键ID'=>'require',
                'order_id|order主键ID'=>'require',
                'os_id|订单服务主键ID'=>'require'
            ]);

            //实例化对象
            $refundGoods = new RefundGoods();

            $res = $refundGoods->operateOrder(input('operate_type'),input('id'),input('order_id'),input('os_id'));

            !empty($res) ?  return_succ([],'操作成功') : return_error('操作失败');
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**
     * 拒绝退货
     */
    public function refuseRefundGoods()
    {
        try{
            //数据验证
            dataValidate(request()->param(),['id|退货主键id'=>'require','r_desc|拒绝理由'=>'require']);

            $refundGoods = new RefundGoods();
            $res = $refundGoods->operate(['status'=>2,'r_desc'=>input('r_desc')],['id'=>input('id')]);
            empty($res) ? return_error('拒绝失败'):return_succ([],'添加成功');
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}