<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/28 0028
 * Time: 下午 13:52
 *
 * 商品仓库库存
 */

namespace app\mall_admin_order\model;


use think\Model;

class ShopItem extends Model
{
    /**修改库存
     * @param $shop_id 仓库主键id
     * @param $item_id 商品主键id
     * @param $param 增减判断参数 0Inc,=1Dec
     * @param $num 数量
     * @return int|true
     */
    public function upItemStock($shop_id,$item_id,$param,$num)
    {
        try{
            $db = $this->where(['shop_id'=>$shop_id,'item_id'=>$item_id]);
            $res = empty($param) ? $db->setInc('stock',$num) :$db->setDec('stock',$num);
            return $res;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}