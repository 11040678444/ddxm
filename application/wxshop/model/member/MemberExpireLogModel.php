<?php

//限时余额使用记录
namespace app\wxshop\model\member;
use think\Model;
use think\Db;
class MemberExpireLogModel extends Model
{
    protected $table = 'ddxm_member_expire_log';

    public function getCreateTimeAttr($val){
        return date('m-d H:i:s',$val);
    }
}