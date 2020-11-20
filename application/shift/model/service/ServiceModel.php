<?php
namespace app\shift\model\service;

use app\shift\model\ShiftbaseModel;
use think\Model;
use think\Cache;
use think\Db;

class ServiceModel extends ShiftbaseModel
{	
	protected $table = 'tf_service';

	public function getStandardPriceAttr($val){
		if( empty($val) ){
			return '';
		}
		return json_decode($val,true);
	}
}