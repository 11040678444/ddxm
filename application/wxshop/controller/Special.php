<?php
namespace app\wxshop\controller;

use app\wxshop\controller\Base;
use app\mall_admin_market\model\special\SpecialType;
use app\mall_admin_market\model\special\SpecialItem;
/**
专题
 */
class Special extends Base
{
    //获取专题类型
    public function getTypeList()
    {
        $data = $this ->request ->param();
        $list = (new SpecialType()) ->getTypeList( $data );
        return_succ($list,'获取成功');
    }

    //根据类型id获取商品列表
    public function getItemList()
    {
        $data = $this ->request ->param();
        $res = (new SpecialItem()) ->getItemList( $data );
        return_succ($res,'获取成功');
    }
}