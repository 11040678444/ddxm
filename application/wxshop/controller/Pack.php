<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/17 0017
 * Time: 下午 14:40
 */

namespace app\wxshop\controller;
use app\wxshop\model\comment\StPack;
use app\wxshop\controller\Base;

class Pack extends Base
{
    /**
     * 获取活动列表
     */
    public function getPackItemInfo()
    {
        try{
            if(request()->isPost())
            {
                //数据验证
                dataValidate(request()->param(),[
                    'id|活动主键ID'=>'require'
                ]);

                $data = (new StPack())->getPackItemInfo(input('id'));

                return_succ($data,'ok');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}