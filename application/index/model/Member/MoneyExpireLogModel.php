<?php
/*
	订单控制器
*/
namespace app\index\model\Member;

use think\Model;
use think\Cache;
use think\Db;

class MoneyExpireLogModel extends Model
{
    protected $table = 'ddxm_money_expire_log';

    public function getCraeteTimeAttr($val){
        return date('Y-m-d H:i:s',$val);
    }

    public function getPayWayAttr($val){
        return '余额';
    }

    public function getNicknameAttr($val,$data){
        if( !$val ){
            return $val;
        }else{
            return $data['wechat_nickname'];
        }
    }

    public function getShopAttr($val){
        return Db::name('shop')->where('id',$val)->value('name');
    }
}