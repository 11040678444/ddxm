<?php
// +----------------------------------------------------------------------
// | 订单发货
// +----------------------------------------------------------------------
namespace app\mall_admin_order\controller;
use app\common\controller\OrderBase;
use app\mall_admin_order\model\send_item\OrderGoods;

class OrderSendItem extends OrderBase
{
    /***
     * @param array $postData
     * @return bool|false|string
     * 发货,只计算商品成本、只进入股东数据
     */
    public function orderSendItem($postData=array())
    {
        $data = !empty($postData) ? $postData : ($this ->request ->param());
        if( empty($data['type']) ){
            return json_encode(['code'=>300,'msg'=>'type为空']);
        }
        $res = (new OrderGoods()) ->sendItem($data);
        $result = json_decode($res,true);
        if( $result['code'] == 200 ){
            return true;
        }
        return $res;
    }

    /***
     * @param array $postData
     * @return bool|false|string
     * 退货,只退商品成本、只退入股东数据
     */
    public function orderRefundItem($postData=array()){
        $data = !empty($postData) ? ($postData) : ($this ->request ->param());
        $data = $this ->request ->param();
        $res = (new OrderGoods()) ->refundItem( $data );
        $result = json_decode($res,true);
        if( $result['code'] == 200 ){
            return true;
        }
        return $res;
    }
}