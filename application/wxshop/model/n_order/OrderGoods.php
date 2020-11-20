<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/11 0011
 * Time: 下午 14:56
 * 订单商品表(RenKunHong 作于订单列表使用)
 */
namespace app\wxshop\model\n_order;
use think\Model;

class OrderGoods extends Model
{
    protected $table = 'ddxm_n_order_goods';
    protected $autoWriteTimestamp = true;
    protected $resultSetType = 'collection';

    /***
 *获取商品的发货状态 og_send_status_name
 * @param $val
 * @return string
 */
    public function getOgSendStatusNameAttr($val)
    {
        $status = [0=>'待发货',1=>'部分发货',2=>'已发货'];
        return $status[$val];
    }

    /***
     *获取商品的退货状态 og_return_status_name
     * @param $val
     * @return string
     */
    public function getOgReturnStatusNameAttr($val)
    {
        $status = [0=>'未退货',1=>'部分退货',2=>'已退货'];
        return $status[$val];
    }

    public function getOgGoodsPicAttr($val)
    {
        return config('config.qiNiu_picture').$val;
    }
}