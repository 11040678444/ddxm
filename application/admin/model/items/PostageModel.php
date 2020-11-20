<?php

// +----------------------------------------------------------------------
// | 商品单位
// +----------------------------------------------------------------------
namespace app\admin\model\items;

use think\Model;
use think\Db;

class PostageModel extends Model
{
    protected $table = 'ddxm_postage';

    public function getCreateTimeAttr($val){
        if( $val == 0 ){
            return 0;
        }else{
            return date('Y-m-d H:i:s',$val);
        }
    }
}