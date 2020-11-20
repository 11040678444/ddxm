<?php

// +----------------------------------------------------------------------
// | 商品分类
// +----------------------------------------------------------------------
namespace app\mall\model\items;

use think\Model;
use think\Db;

class BrandModel extends Model
{
    protected $table = 'ddxm_brand';

    public function getCreatetimeAttr($val){
        if( $val == 0 ){
            return 0;
        }else{
            return date('Y-m-d H:i:s',$val);
        }
    }

    public function getThumbAttr($val){
        $url = "http://picture.ddxm661.com/".$val;
        return "<img src='".$url."' alt=''>";
    }

    public function getUpdateTimeAttr($val){
        if( $val == 0 ){
            return 0;
        }else{
            return date('Y-m-d H:i:s',$val);
        }
    }

    //创建人
    public function getUserIdAttr($val){
        if( $val==0 ){
            return '0';
        }
        return Db::name('admin')->where('userid',$val)->value('username');
    }
}