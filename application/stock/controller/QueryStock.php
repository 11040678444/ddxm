<?php

namespace app\stock\controller;

use app\common\controller\Backendbase;
use app\stock\model\shop\ShopItem;
/**
 * 库存查询控制器
 */
class QueryStock extends Backendbase
{
    /***
     * 库存查询
     */
    public function index(){
        $data = $this ->request ->param();
        $res = (new ShopItem()) ->stock_list($data);
        return $res;
    }
}
