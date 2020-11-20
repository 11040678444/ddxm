<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/24 0024
 * Time: 下午 18:00
 *
 * 订单服务类
 */

namespace app\mall_admin_order\controller;
use app\common\controller\OrderBase;
use think\Db;
use app\mall_admin_order\model\OrderService as Service;

class OrderService extends OrderBase
{
    /**
     * 添加订单中间表数据
     * @param  $kill 1外部调用，0默认内部调用
     * @throws \think\exception\PDOException
     */
    public function addService($kill=0,$data=[])
    {
        if(request()->isPost())
        {
            $data = empty($data)? request()->param() : $data;
            $data['kill'] = $kill;

            $model = new  Service();
            $model->startTrans();
            try{
                //数据验证
                dataValidate($data,[
                    'order_id|订单关联id'=>'require',
                    'shop_id|仓库关联id'=>'require',
                    'os_type|类型'=>'require',
                    'goods_id|商品详细关联id'=>'require',//多件商品已数组形式
                ]);

                $res = $model->addService($data);
                if($res)
                {
                    $model->commit();
                    return !empty($kill) ? $res :return_succ([],'操作成功');
                }else{
                    $model->rollback();
                   return !empty($kill) ? $res :return_error('操作失败');
                }
            }catch (\Exception $e){
                $model->rollback();
                return !empty($data['kill']) ? false :return_error('系统错误:'.$e->getMessage());
            }
        }
    }
}