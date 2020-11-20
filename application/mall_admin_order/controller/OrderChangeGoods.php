<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/28
 * Time: 0:11
 */

namespace app\mall_admin_order\controller;
use app\mall_admin_order\model\OrderChangeGoods as changeGoodes;

class OrderChangeGoods
{
    /**
     * 处理待发货订单流程
     */
    public function operateSendGoods()
    {
        try{
            //数据验证
            dataValidate(request()->param(),[
                'operate_type|操作类型'=>'require',
                'id|换货主键ID'=>'require',
                'order_id|order主键ID'=>'require',
                'os_id|订单服务主键ID'=>'require'
            ]);

            //实例化对象
            $changeGoodes = new changeGoodes();

            $res = $changeGoodes->operateOrder(input('operate_type'),input('id'),input('order_id'),input('os_id'));

            !empty($res) ?  return_succ([],'操作成功') : return_error('操作失败');
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**
     * 拒绝换货
     */
    public function refuseRefundGoods()
    {
        try{
            //数据验证
            dataValidate(request()->param(),['id|换货主键id'=>'require','e_desc|拒绝理由']);

            $changeGoodes = new changeGoodes();
            $res = $changeGoodes->operate(['status'=>2,'e_desc'=>input('e_desc')],['id'=>input('id')]);
            empty($res) ? return_error('拒绝失败'):return_succ([],'添加成功');
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}