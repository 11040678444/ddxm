<?php
namespace app\shift\model\item;

use app\shift\model\ShiftbaseModel;
use think\Model;
use think\Cache;
use think\Db;

class ItemModel extends ShiftbaseModel
{	
	protected $table = 'tf_item';

	//获取商品的1级分类
	public function getTtAttr($val,$data){
		if( $data['type'] == 0 ){
			return 0;
		}
		$db = Db::connect($this->connection);
		return $db->name('item_category')->where('id',$data['type'])->value('pid');
	}

	//获取第一张图片
	public function getPicAttr($val,$data){
		if( empty($data['pics']) ){
			return '';
		}
		$arr = explode(',',$data['prics']);
		return $arr['0'];
	}
}