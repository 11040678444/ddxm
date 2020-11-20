<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/30 0030
 * Time: 下午 17:32
 */

namespace app\common\controller;
use think\Controller;

class Shop extends Controller
{
    /**
     * 仓库查询
     */
    public function getShop()
    {
        try{
            if(request()->isPost())
            {
                $list = db('shop')->field('id,name,level')->whereBetween('level',[1,2])->select();
                return_succ($list,'ok');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

}