<?php
/*
    订单模型
*/
namespace app\wxshop\model\member;
use think\Model;
use think\Cache;
use think\Db;

class MemberTicketModel extends Model
{
    protected $table = 'ddxm_member_ticket';

    public function getCreateTimeAttr($val){
        return date('Y-m-d H:i:s',$val);
    }
    public function getReceiveExpireTimeAttr($val){
        return date('Y-m-d H:i:s',$val);
    }
    public function getReceiveTimeAttr($val){
        if( $val==0 ){
            return '未领取';
        }
        return date('Y-m-d H:i:s',$val);
    }
    public function getUseExpireTimeAttr($val){
        if( $val == 0 ){
            return '未领取';
        }
        return date('Y-m-d H:i:s',$val);
    }
}