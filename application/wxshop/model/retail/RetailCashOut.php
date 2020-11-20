<?php
namespace app\wxshop\model\retail;
use think\Model;
use think\Db;

class RetailCashOut extends Model
{
    public function getStateAttr($val){
        $arr = [
            0   =>'申请中',
            1   =>'已提现',
            2   =>'已拒绝',
        ];
        return $arr[$val];
    }

    public function getTimeAttr($val){
        return date('m月d日 H:i',$val);
    }
}