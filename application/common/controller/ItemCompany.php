<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/30 0030
 * Time: 下午 20:00
 *
 * 快递查询
 */

namespace app\common\controller;
use think\Controller;

class ItemCompany extends Controller
{
    /**
     * 获取物流公司
     */
    public function getItemCompany()
    {
        try{
            if(request()->isPost())
            {
                $res = db('item_company')->field('title,code')->where(['status'=>1])->select();
                return_succ($res,'ok');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}