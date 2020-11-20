<?php

// +----------------------------------------------------------------------
// | 商品属性
// +----------------------------------------------------------------------
namespace app\mall\model\setting;

use think\Model;
use think\Db;

class BannerModel extends Model
{
    protected $table = 'ddxm_banner';

    public function getImgAttr($val,$data){
        $url =  "http://picture.ddxm661.com/".$data['thumb'];
        return "<img src=".$url." >";
    }
}