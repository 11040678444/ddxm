<?php

namespace app\stock\controller;

use app\stock\model\shop\ShopModel;
use think\Controller;
/**
 * 库存查询控制器
 */
class Shop extends Controller
{
    /***
     * 仓库列表
     */
    public function get_list(){
        $data = $this ->request ->param();
        $list = (new ShopModel()) ->getList($data);
        return $list;
    }
}
