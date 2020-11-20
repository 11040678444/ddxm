<?php


namespace app\mall\controller;

use app\common\controller\Adminbase;

/**
 * 超时
 * Class OvertimeSet
 * @package app\mall\controller
 */
class OvertimeSet extends Adminbase
{

    public function index(){

        $pa = input('post.');
        if(!empty($pa)){
           //更改数据
            $data = [
                "set_waitpay_time"=>$pa['set_waitpay_time'],
                "set_group_waitpay_time"=>$pa['set_group_waitpay_time'],
                "set_rob_waitpay_time"=>$pa['set_rob_waitpay_time'],
                "set_takedelivery_time"=>$pa['set_takedelivery_time'],
                "set_ca_takedelivery_time"=>$pa['set_ca_takedelivery_time'],
                "set_comment_time"=>$pa['set_comment_time'],
                "set_assemble_fail_time"=>$pa['set_assemble_fail_time'],
                "set_assemble_success_time"=>$pa['set_assemble_success_time'],
                "set_retai_order_time"=>$pa['set_retai_order_time'],
                "update_time"=>time(),
            ];
             $flad = db('overtime_set')->where('id',1)->update($data);
             if($flad == false){
                 $this->error('保存失败！', url('mall/Overtime_set/index'),10000000000000000);
             }else{
                 $this->success('保存成功！', url('mall/Overtime_set/index'),10000000000000000);
             }
            return $this->fetch();
        }
        $data = db('overtime_set')->where('id',1)->find();
        $this->assign('data', $data);
        return $this->fetch();
    }
}