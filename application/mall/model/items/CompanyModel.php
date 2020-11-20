<?php

// +----------------------------------------------------------------------
// | 商品分类
// +----------------------------------------------------------------------
namespace app\mall\model\items;

use think\Model;
use think\Db;

class CompanyModel extends Model
{
    protected $table = 'ddxm_item_company';

    public function getCreateTimeAttr($val){
        if( $val == 0 ){
            return 0;
        }else{
            return date('Y-m-d H:i:s',$val);
        }
    }

    public function getUpdateTimeAttr($val){
        if( $val == 0 ){
            return 0;
        }else{
            return date('Y-m-d H:i:s',$val);
        }
    }

    //更新人
    public function getUpdateIdAttr($val){
        if( $val==0 ){
            return '0';
        }
        return Db::name('admin')->where('userid',$val)->value('username');
    }


}