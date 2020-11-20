<?php

// +----------------------------------------------------------------------
// | 商品分类
// +----------------------------------------------------------------------
namespace app\mall\model\items;

use think\Model;
use think\Db;

class CategoryModel extends Model
{
	protected $table = 'ddxm_item_category';

	public function getAddtimeAttr($val){
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

	//更新人
	public function getUpdateIdAttr($val){
		if( $val==0 ){
			return '0';
		}
		return Db::name('admin')->where('userid',$val)->value('username');
	}

	/***
     *分类
     */
	public function getPidNameAttr($val,$data){
	    $pid = $data['pid'];
	    if( $pid == 0 ){
	        return '顶级分类';
        }
	    return $this ->where('id',$pid) ->value('cname');
    }
}