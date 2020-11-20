<?php
/**
 * Author: chenjing
 * Date: 2018/1/10
 * Description:
 */

namespace app\admin\model\order;


use app\admin\model\BaseModel;

class OrderLogsModel extends BaseModel
{
    protected $table = 'tf_order_logs';

    //修改器
    public function getOrderStatusAttr($value)
    {
        $status = [
            0 =>'待发货',
            1 => '待收货',
            2 => '确认收货',
            -1 => '申请退货',
            -2 => '退货完成',
            -5 => '申请退款',
            -6 => '退款完成',
            -7 => '已取消',
            8 => '配送中',
            9 => '待处理'
        ];
        return ['status' => $value,'text' => $status[$value]];
    }

    // public function getSendStatusAttr($value)
    // {
    //     $status = [
    //         0 => '未发货',
    //         1 => '已发货',
    //     ];
    //     return $status[$value];
    // }

    public function getPayStatusAttr($value)
    {
        $status = [
            0 => '未支付',
            1 => '已支付',
        ];
        return $status[$value];
    }

    public function getAddtimeAttr($value)
    {
        if ($value) {
            return date('Y年m月d日 H:i',$value);
        }
        return '暂无';
    }

    public function getOrderLogs($order_id)
    {
        return collection($this->where(['order_id'=>$order_id])->select())->toArray();
    }
}