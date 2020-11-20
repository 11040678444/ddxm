<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/30 0030
 * Time: 下午 18:02
 */

namespace app\mall_admin_order\model;

use think\Model;

class OrderExpress extends Model
{
    /**
     * 添加发货订单物流
     * @param $data 新增数据
     * @return bool
     */
    public function setOrderExpress($data)
    {
        try{
            $res = OrderExpress::saveAll($data);
            return $res;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}