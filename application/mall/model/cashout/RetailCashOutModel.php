<?php

// +----------------------------------------------------------------------
// | 商品分类
// +----------------------------------------------------------------------
namespace app\mall\model\cashout;

use think\Model;
use think\Db;

class RetailCashOutModel extends Model
{
    protected $table = 'ddxm_retail_cash_out';

    public function getCreateTimeAttr( $val ){
        return date('Y-m-d H:i:s',$val);
    }
    public function getUpdateTimeAttr( $val ){
        if( $val == 0 ){
            return '未处理';
        }
        return date('Y-m-d H:i:s',$val);
    }
    public function getMemberAttr($val){
        return Db::name('member')->where('id',$val)->value('mobile');
    }

    public function getAdminIdAttr($val){
        if( $val == 0 ){
            return '未处理';
        }
        return Db::name('admin')->where('userid',$val)->value('nickname');
    }
}