<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/27 0027
 * Time: 下午 17:32
 */

namespace app\mall_admin_order\controller;
//use app\common\controller\OrderBase;
use app\mall_admin_order\model\OrderSendGoods as SendGoods;
use think\Controller;


class OrderSendGoods extends Controller
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
                'id|待发货商品主键ID'=>'require',
                'order_id|order主键ID'=>'require',
                'os_id|订单服务主键ID'=>'require'
            ]);

            //实例化对象
            $sendGoods = new SendGoods();

            $res = $sendGoods->operateOrder(input('operate_type'),input('id'),input('order_id'),input('os_id'));

            !empty($res) ?  return_succ([],'操作成功') : return_error('操作失败');
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**
     * 设置订单仓库
     */
    public function setOrderShop()
    {
        try{
            if(request()->isPost())
            {
                //数据验证
                dataValidate(request()->param(),['id|商品主键ID'=>'require','shop_id'=>'require']);

                //更新
                $sendGoods = new SendGoods();
                $res = $sendGoods->operate(['shop_id'=>input('shop_id')],['id'=>input('id')]);
                empty($res) ? return_error('设置仓库失败') : return_succ('设置仓库成功');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}