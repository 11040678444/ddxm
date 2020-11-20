<?php

namespace app\wxshop\model\item;
use think\Model;
use think\Db;

/***
 * 购物车模型
 * Class StatisticsLogModel
 * @package app\wxshop\model\statistics
 */
class CartModel extends Model
{
    protected $table = 'ddxm_shopping_cart';

    /***
     * 商品图片
     * @param $val
     * @return string
     */
    public function getPicAttr($val){
        if( $val == '' ){
            return '';
        }
        return "http://picture.ddxm661.com/".$val;
    }

    /***
     * 分区
     * @param $val
     * @param $data
     * @return mixed|string
     */
    public function getMoldAttr($val,$data){
        $val = $data['mold_id'];
        $title = Db::name('item_type')->where('id',$val)->value('title');
        if( $title ){
            return $title;
        }
        return '熊猫自营';
    }
}