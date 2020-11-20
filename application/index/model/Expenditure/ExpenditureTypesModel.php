<?php
/*
	订单控制器
*/
namespace app\index\model\Expenditure;

use think\Model;
use think\Cache;
use think\Db;

class ExpenditureTypesModel extends Model
{
	protected $table = 'ddxm_expenditure_types';

	public function getCreateTimeAttr($val){
		return date('Y-m-d H:i:s',$val);
	}
	public function getUpdateTimeAttr($val){
		return date('Y-m-d H:i:s',$val);
	}
}