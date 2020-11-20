<?php

// +----------------------------------------------------------------------
// | 商品分类
// +----------------------------------------------------------------------
namespace app\mall\model\seckill;

use think\Model;
use think\Db;

class SeckillModel extends Model
{
    protected $table = 'ddxm_seckill';

    public function getStartTimeAttr($val){
        return date('Y-m-d H:i:s',$val);
    }
    public function getEndTimeAttr($val){
        return date('Y-m-d H:i:s',$val);
    }
    public function getCreateTimeAttr($val){
        return date('Y-m-d H:i:s',$val);
    }
    public function getNumAttr($val){
        if( $val == 0 ){
            return '无限制';
        }
        return $val;
    }
}